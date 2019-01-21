<?php

namespace Eskju\HttpLogger;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DefaultLogWriter implements LogWriter
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     *
     * @throws \Exception
     */
    public function logRequest(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $time = (microtime(true) - LARAVEL_START);
        $threshold = config('logging.requests.threshold');

        if ($time < $threshold) {
            return;
        }

        switch (config('logging.requests.driver')) {
            case 'FILE':
                $this->logToFile();
                break;

            case 'REMOTE':
                $this->logToURL();
                break;

            case 'NONE':
                break;

            default:
                throw new \Exception('unknown log driver "' . config('logging.requests.driver') . '"');
        }
    }

    /**
     * log into daily files
     */
    private function logToFile()
    {
        Log::channel('requests')->info($this->getMessage());
    }

    /**
     * return a formatted message string
     *
     * @return string
     */
    private function getMessage()
    {
        $time = number_format(microtime(true) - LARAVEL_START, 5) . 's';
        $method = strtoupper($this->request->getMethod());
        $uri = $this->request->getPathInfo();
        $bodyAsJson = json_encode($this->request->except(config('http-logger.except')));

        $files = array_map(function (UploadedFile $file) {
            return $file->getClientOriginalName();
        }, iterator_to_array($this->request->files));

        return $time . ' | ' . $method . ' ' . $uri . ' - Body: ' . $bodyAsJson . ' - Files: ' . implode(', ', $files);
    }

    private function logToURL()
    {
        try {
            $url = config('logging.requests.remote_url');
            $params = ['json' => $this->getJson()];

            $client = new Client();
            $client->post($url, $params);
        } catch (\Exception $exception) {
            $this->logToFile();
            dd($exception->getMessage());
        }
    }

    /**
     * return a json object
     *
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
        ];
    }
}