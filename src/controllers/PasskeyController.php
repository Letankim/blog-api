<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\PasskeyModel;
use Exception;

class PasskeyController
{
    private PasskeyModel $model;

    public function __construct()
    {
        $this->model = new PasskeyModel();
    }

    public function startRegistration(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $data['user_id'] ?? null;
        $username = $data['username'] ?? null;
        $displayName = $data['display_name'] ?? $username;

        if (!$userId || !$username) {
            return $this->jsonResponse($response, 400, [
                'error' => 'Thiếu user_id hoặc username'
            ]);
        }

        try {
            $result = $this->model->startRegistration($userId, $username, $displayName);

            return $this->jsonResponse($response, 200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse($response, 500, [
                'error' => 'Không thể khởi tạo đăng ký Passkey',
                'details' => $e->getMessage()
            ]);
        }
    }

    public function finishRegistration(Request $request, Response $response): Response
    {
        $clientData = $request->getParsedBody();

        if (!is_array($clientData)) {
            return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
        }

        try {
            $user = $this->model->finishRegistration($clientData);

            return $this->jsonResponse($response, 201, [
                'success' => true,
                'message' => 'Đăng ký Passkey thành công',
                'user' => $user
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => 'Đăng ký Passkey thất bại',
                'details' => $e->getMessage()
            ]);
        }
    }

    public function startLogin(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? null;

        try {
            $result = $this->model->startLogin($email);

            return $this->jsonResponse($response, 200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse($response, 500, [
                'error' => 'Không thể khởi tạo đăng nhập',
                'details' => $e->getMessage()
            ]);
        }
    }

    public function finishLogin(Request $request, Response $response): Response
    {
        $clientData = $request->getParsedBody();

        if (!is_array($clientData)) {
            return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
        }

        try {
            $user = $this->model->finishLogin($clientData);

            return $this->jsonResponse($response, 200, [
                'success' => true,
                'message' => 'Đăng nhập bằng Passkey thành công',
                'user' => $user
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse($response, 401, [
                'error' => 'Đăng nhập thất bại',
                'details' => $e->getMessage()
            ]);
        }
    }

    public function revoke(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user'); 

        if (!$user) {
            return $this->jsonResponse($response, 401, [
                'error' => 'Chưa đăng nhập'
            ]);
        }

        try {
            $success = $this->model->revokePasskey($user->id);

            if ($success) {
                return $this->jsonResponse($response, 200, [
                    'success' => true,
                    'message' => 'Đã xóa Passkey thành công. Bạn có thể tạo lại bất kỳ lúc nào.'
                ]);
            }

            return $this->jsonResponse($response, 400, [
                'error' => 'Không tìm thấy Passkey để xóa'
            ]);

        } catch (Exception $e) {
            return $this->jsonResponse($response, 500, [
                'error' => 'Lỗi hệ thống khi xóa Passkey',
                'details' => $e->getMessage()
            ]);
        }
    }

    private function jsonResponse(Response $response, int $status, array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}