<?php
use Slim\Routing\RouteCollectorProxy;

$app->group('/api/v1', function (RouteCollectorProxy $group) {
    $group->get('/chat/history/{session_id}', \App\Controllers\ChatController::class . ':getHistory');
    $group->post('/chat', \App\Controllers\ChatController::class . ':chat');
});