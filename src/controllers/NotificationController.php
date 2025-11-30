<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\NotificationModel;
use Respect\Validation\Exceptions\ValidationException;

class NotificationController
{
    private NotificationModel $model;

    public function __construct()
    {
        $this->model = new NotificationModel();
    }

    public function getAll(Request $request, Response $response): Response
{
    try {
        $user = $request->getAttribute('user');
        if (!$user || !isset($user->id)) {
            return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
        }

        $params = $request->getQueryParams();
        $result = $this->model->getAllForUser($user->id, $params);

        return $this->jsonResponse($response, 200, [
            'data'       => $result['data'],
            'pagination' => $result['pagination'],
            'success'    => 'Lấy thông báo thành công'
        ]);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}

    public function markRead(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID thông báo']);
            }

            $message = $this->model->markRead($id);

            return $this->jsonResponse($response, 200, ['success' => $message]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function markUnread(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID thông báo']);
            }

            $message = $this->model->markUnread($id);

            return $this->jsonResponse($response, 200, ['success' => $message]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $id = $this->model->createNotification($data);

            return $this->jsonResponse($response, 201, [
                'success' => 'Tạo thông báo thành công',
                'id' => $id
            ]);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
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