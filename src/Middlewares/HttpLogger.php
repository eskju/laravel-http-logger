<?php

namespace Eskju\HttpLogger\Middlewares;

use Closure;
use Eskju\HttpLogger\LogProfile;
use Eskju\HttpLogger\LogWriter;
use Illuminate\Http\Request;

class HttpLogger
{
    protected $logProfile;
    protected $logWriter;

    public function __construct(LogProfile $logProfile, LogWriter $logWriter)
    {
        $this->logProfile = $logProfile;
        $this->logWriter = $logWriter;
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->logProfile->shouldLogRequest($request)) {
            $this->logWriter->logRequest($request, $response);
        }

        return $response;
    }
}
