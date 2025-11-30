<?php

use App\Controllers\DashboardController;
use Slim\Routing\RouteCollectorProxy;

$app->group('/api/v1/dashboard', function (RouteCollectorProxy $group) {
    $group->get('/overview', DashboardController::class . ':overview')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/revenue', DashboardController::class . ':revenueChart')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/top-buyers', DashboardController::class . ':topBuyers')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/top-cancellers', DashboardController::class . ':topCancellers')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/recent-orders', DashboardController::class . ':recentOrders')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/recent-comments', DashboardController::class . ':recentComments')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/top-products', DashboardController::class . ':topProducts')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/quick-stats', DashboardController::class . ':quickStats')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/key-stats', DashboardController::class . ':keyStats')->add(new App\Middleware\AuthMiddleware('admin'));;
    $group->get('/new-users', DashboardController::class . ':newUsersChart')->add(new App\Middleware\AuthMiddleware('admin'));;
});