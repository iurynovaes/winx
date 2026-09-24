<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Connection
    |--------------------------------------------------------------------------
    |
    | Host and index used by product search.
    |
    */

    'host' => env('ELASTICSEARCH_HOST', 'http://127.0.0.1:9200'),

    'index' => env('ELASTICSEARCH_INDEX', 'products'),

];
