<?php

return [

    /*
     * Define if logs are written to FILE or REMOTE
     */
    'driver' => env('LOGGING_REQUESTS_DRIVER', 'FILE'),

    /*
     * In case you selected 'driver'=FILE, define an URL
     */
    'remote_url' => env('LOGGING_REQUESTS_REMOTE_URL'),

    /*
     * You might want to define a threshold to log long requests only
     */
    'threshold' => env('LOGGING_REQUESTS_THRESHOLD', 0),

    /*
     * The log profile which determines whether a request should be logged.
     * It should implement `LogProfile`.
     */
    'log_profile' => \Eskju\HttpLogger\DefaultLogProfile::class,

    /*
     * The log writer used to write the request to a log.
     * It should implement `LogWriter`.
     */
    'log_writer' => \Eskju\HttpLogger\DefaultLogWriter::class,

    /*
     * Filter out body fields which will never be logged.
     */
    'except' => [
        'password',
        'password_confirmation',
    ],

];
