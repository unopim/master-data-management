<?php

return [
    'azure' => [
        'driver'            => 'azure-storage-blob',
        'connection_string' => env('AZURE_STORAGE_CONNECTION_STRING'),
        'container'         => env('AZURE_STORAGE_CONTAINER', 'masterdata'),
        'prefix'            => env('AZURE_STORAGE_PREFIX', ''),
        'throw'             => false,
    ],
];
