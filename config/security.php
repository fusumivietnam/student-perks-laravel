<?php

return [
    'trusted_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_HOSTS', '')),
    ))),

    'trusted_proxies' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))),
        fn (string $proxy): bool => $proxy !== '' && $proxy !== '*',
    )),
];
