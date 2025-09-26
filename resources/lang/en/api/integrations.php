<?php

return [
    App\Lib\Integrations\HostingServers\SampleHostingServer::class => [
        "title" => "Sample Hosting Server",
        "description" => "Sample Hosting Server - create your own integration",
        "fields" => [
            'api_url' => [
                'label' => 'API URL',
            ],
            'api_key' => [
                'label' => 'API Key',
            ]
        ],
        "config" => [
            'plan' => [
                'label' => 'Plan',
                'tooltip' => 'Plan'
            ],
            'space_quota' => [
                'label' => 'Space Quota',
                'tooltip' => 'Space Quota',
            ],
            'burst_up_php_workers' => [
                'label' => 'Burst Up PHP Workers',
                'tooltip' => 'Burst Up PHP Workers',
            ],
            'location' => [
                'label' => 'Location',
                'tooltip' => 'Location',
            ],
        ]
    ],
];