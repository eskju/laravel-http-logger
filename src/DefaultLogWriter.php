<?php

namespace Eskju\HttpLogger;

use GuzzleHttp\Client;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DefaultLogWriter implements LogWriter
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var array
     */
    private $queries = [];

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function logRequest(Request $request, $response)
    {
        $this->request = $request;
        $this->response = $response;
        $time = (microtime(true) - LARAVEL_START);
        $threshold = config('http-logger.threshold');

        if ($time < $threshold) {
            return;
        }

        switch (config('http-logger.driver')) {
            case 'FILE':
                $this->logToFile();
                break;

            case 'REMOTE':
                $this->logToURL();
                break;

            case 'NONE':
                break;

            default:
                throw new \Exception('unknown log driver "' . config('http-logger.driver') . '"');
        }
    }

    /**
     * log into daily files
     */
    private function logToFile()
    {
        Log::channel('requests')->info($this->getMessage());
        Log::channel('queries')->info(json_encode($this->queries));
    }

    /**
     * return a formatted message string
     * @return string
     */
    private function getMessage()
    {
        $time = number_format(microtime(true) - LARAVEL_START, 5) . 's';
        $method = strtoupper($this->request->getMethod());
        $uri = $this->request->getPathInfo();
        $bodyAsJson = json_encode($this->request->except(config('http-logger.except')));
        $files = [];

        foreach ($_FILES as $file) {
            if (is_array($file['name'])) {
                foreach ($file['name'] as $name) {
                    $files[] = $name;
                }
            } else {
                $files[] = $file['name'];
            }
        }

        return $time . ' | ' . $method . ' ' . $uri . ' - Body: ' . $bodyAsJson . ' - Files: ' . implode(', ', $files);
    }

    private function logToURL()
    {
        try {
            $url = config('http-logger.remote_url');
            $params = ['json' => $this->getJson(), 'verify' => false];

            $client = new Client();
            $client->post($url, $params);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            $this->logToFile();
        }
    }

    /**
     * return a json object
     * @return array
     */
    private function getJson()
    {
        return [
            'identifier' => REQUEST_ID,
            'time' => microtime(true) - LARAVEL_START,
            'ip' => $this->request->getClientIp(),
            'method' => strtoupper($this->request->getMethod()),
            'host' => $this->request->getSchemeAndHttpHost(),
            'uri' => $this->request->getPathInfo(),
            'header' => $this->request->headers,
            'body' => $this->request->except(config('http-logger.except')),
            'encoding' => $this->request->getEncodings(),
            'content_type' => $this->request->getContentType(),
            'response_code' => $this->response->getStatusCode(),
            'response_content' => $this->response->getContent(),
            'queries' => $this->queries,
            'user_id' => !auth()->guest() ? auth()->id : null
        ];
    }

    /**
     * @param $query
     */
    public function addQuery(QueryExecuted $query)
    {
        $id = md5($query->sql);

        if (!key_exists($id, $this->queries)) {
            $this->queries[$id] = [
                'time' => $query->time,
                'count' => 1,
                'sql' => $query->sql,
                'bindings' => $query->bindings,
            ];
        } else {
            $this->queries[$id]['count']++;
            $this->queries[$id]['time'] += $query->time;
        }
    }
}
