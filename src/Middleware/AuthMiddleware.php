<?php

namespace App\Middleware;

use App\Config\Settings;
use App\Models\UserModel;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private $requiredRole;
    private $userModel;

    public function __construct($requiredRole = 'user')
    {
        $this->requiredRole = $requiredRole;
        $this->userModel = new UserModel();
    }

    public function __invoke(Request $request, Handler $handler): \Psr\Http\Message\ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        if (!$token) {
            return $this->jsonResponse(new Response(), 401, ['error' => 'Yêu cầu xác thực tài khoản']);
        }

        try {
            $secret = Settings::load()['JWT_SECRET'];
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            $user = $this->userModel->getUserById($decoded->id);
            if (!$user) {
                return $this->jsonResponse(new Response(), 401, ['error' => 'Tài khoản không tồn tại']);
            }

            if ($user['status'] !== 'active') {
                return $this->jsonResponse(new Response(), 403, [
                    'error' => 'Tài khoản bị khóa hoặc chưa kích hoạt',
                    'status' => $user['status']
                ]);
            }

            if (!$user['is_verified']) {
                return $this->jsonResponse(new Response(), 403, [
                    'type'=> 'verified',
                    'error' => 'Tài khoản chưa xác minh email'
                ]);
            }

            if ($this->requiredRole === 'admin' && ($user['role'] ?? 'user') !== 'admin') {
                return $this->jsonResponse(new Response(), 403, ['error' => 'Không có quyền truy cập (chỉ admin)']);
            }

            if ($this->requiredRole === 'admin' && (!isset($decoded->role) || $decoded->role !== 'admin')) {
                return $this->jsonResponse(new Response(), 403, ['error' => 'Không có quyền truy cập (chỉ admin)']);
            }

            $request = $request->withAttribute('user', $decoded);
        } catch (\Exception $e) {
            return $this->jsonResponse(new Response(), 401, ['error' => 'Token không hợp lệ hoặc hết hạn']);
        }

        return $handler->handle($request);
    }

    private function jsonResponse(Response $response, int $status, array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($json);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json;charset=utf-8');
    }
}