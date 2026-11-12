<?php

return [
    'secret' => env('JWT_SECRET'),
    'issuer' => env('JWT_ISSUER', 'lumen-api'),
    'ttl' => (int) env('JWT_TTL', 3600),
];
