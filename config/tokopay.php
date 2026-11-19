<?php

return [
    'enabled' => env('TOKOPAY_ENABLED', false),
    'merchant_id' => env('TOKOPAY_MERCHANT_ID', ''),
    'secret' => env('TOKOPAY_SECRET', ''),
    'channels' => array_filter(array_map('trim', explode(',', env('TOKOPAY_CHANNELS', '')))),
    'payment_hosts' => ['pay.tokopay.id'],
];