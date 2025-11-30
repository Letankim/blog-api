<?php
use App\Controllers\UserController;
use Slim\Routing\RouteCollectorProxy;

$app->group('/api/v1/users', function (RouteCollectorProxy $group) {

    // === PUBLIC APIs ===
    $group->post('/register', UserController::class . ':register');
    $group->post('/login', UserController::class . ':login');
    $group->get('/auth/google/url', [UserController::class, 'getLoginUrl']);
    $group->get('/auth/google/callback', [UserController::class, 'handleGoogleCallback']);
    $group->get('/activate/{token}', UserController::class . ':activate');
    $group->post('/resend-activation', UserController::class . ':resendActivation');
    $group->post('/forgot-password', UserController::class . ':forgotPassword');
    $group->post('/reset-password', UserController::class . ':resetPassword');

    // === AUTHENTICATED USER APIs ===
    $auth = new App\Middleware\AuthMiddleware();
    $group->get('/profile', UserController::class . ':getProfile')->add($auth);
    $group->put('/profile', UserController::class . ':updateProfile')->add($auth);
    $group->post('/avatar', UserController::class . ':uploadAvatar')->add($auth);
    $group->post('/change-password-request', UserController::class . ':changePasswordRequest')->add($auth);
    $group->put('/change-password-otp', UserController::class . ':changePasswordOtp')->add($auth);

    // === ADMIN APIs ===
    $admin = new App\Middleware\AuthMiddleware('admin');
    $group->get('/list', UserController::class . ':getAll')->add($admin);
    $group->get('/{id}', UserController::class . ':getById')->add($admin);
    $group->put('/{id}', UserController::class . ':update')->add($admin);
    $group->put('/{id}/verify', UserController::class . ':adminActivateUser')->add($admin);
    $group->put('/{id}/request_verify', UserController::class . ':adminRequestActivation')->add($admin);
    $group->delete('/{id}', UserController::class . ':delete')->add($admin);
});