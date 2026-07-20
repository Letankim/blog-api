<?php

use App\Models\ChatSessionModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Models\VoucherModel;
use App\Services\AIService;
use App\Services\PayOSService;
use Psr\Container\ContainerInterface;

$container->set(PayOSService::class, function (ContainerInterface $c) {
    $settings = $c->get('settings')['payos'];

    return new PayOSService(
        $settings['client_id'],
        $settings['api_key'],
        $settings['checksum_key'],
        $c->has('logger') ? $c->get('logger') : null
    );
});

$container->set(AIService::class, function ($c) {
    return new AIService(
        new ProductModel(), 
        new OrderModel(), 
        new UserModel(),
        new VoucherModel(),
        new ChatSessionModel(),

    );
});