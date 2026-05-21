<?php

declare(strict_types=1);

use BlissJaspis\QueryDetector\Outputs\Alert;
use BlissJaspis\QueryDetector\Outputs\Log;

return [
    /*
     * Enable or disable the query detection.
     * If this is set to "null", the app.debug config value will be used.
     */
    'enabled' => env('QUERY_DETECTOR_ENABLED', null),

    /*
     * Threshold level for the N+1 query detection. If a relation query will be
     * executed more than this amount, the detector will notify you about it.
     */
    'threshold' => (int) env('QUERY_DETECTOR_THRESHOLD', 1),

    /*
     * Here you can whitelist model relations.
     *
     * Use the relation method name (e.g. "posts"). The related model class is also accepted for convenience.
     */
    'except' => [
        // Author::class => [
        //    'posts',
        // ]
    ],

    /*
     * Paths excluded from the call stack (normalized with forward slashes).
     */
    'excluded_paths' => [
        // '/vendor/some/package',
    ],

    /*
     * Here you can set a specific log channel to write to
     * in case you are trying to isolate queries or have a lot
     * going on in the laravel.log. Defaults to laravel.log though.
     */
    'log_channel' => env('QUERY_DETECTOR_LOG_CHANNEL', 'daily'),

    /*
     * Define the output format that you want to use. Multiple classes are supported.
     * Available options are:
     *
     * Alert:
     * Displays an alert on the website
     * \BlissJaspis\QueryDetector\Outputs\Alert::class
     *
     * Console:
     * Writes the N+1 queries into your browsers console log
     * \BlissJaspis\QueryDetector\Outputs\Console::class
     *
     * Clockwork: (make sure you have the itsgoingd/clockwork package installed)
     * Writes the N+1 queries warnings to Clockwork log
     * \BlissJaspis\QueryDetector\Outputs\Clockwork::class
     *
     * Debugbar: (make sure you have the fruitcake/laravel-debugbar package installed)
     * Writes the N+1 queries into a custom messages collector of Debugbar
     * \BlissJaspis\QueryDetector\Outputs\Debugbar::class
     *
     * JSON:
     * Writes the N+1 queries into the response body of your JSON responses
     * \BlissJaspis\QueryDetector\Outputs\Json::class
     *
     * Log:
     * Writes the N+1 queries into the Laravel.log file
     * \BlissJaspis\QueryDetector\Outputs\Log::class
     */
    'output' => [
        Alert::class,
        Log::class,
    ],
];
