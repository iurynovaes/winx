<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Connection
    |--------------------------------------------------------------------------
    |
    | Full-text search and suggestions use this index when enabled.
    | The product listing stays on the database. Leave it disabled when
    | Elasticsearch is not running.
    |
    */

    'enabled' => filter_var(env('ELASTICSEARCH_ENABLED', false), FILTER_VALIDATE_BOOL),

    'host' => env('ELASTICSEARCH_HOST', 'http://127.0.0.1:9200'),

    'index' => env('ELASTICSEARCH_INDEX', 'products'),

];
