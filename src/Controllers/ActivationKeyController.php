<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ActivationKeyModel;
use Slim\Psr7\Response as SlimResponse;

class ActivationKeyController
{
    private ActivationKeyModel $model;

    public function __construct()
    {
        $this->model = new ActivationKeyModel();
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $keys = $this->model->getAll($params);

           return $this->jsonResponse($response, 200, [
            'data'       => $keys['data'],
            'pagination' => $keys['pagination'],
            'success'    => 'Lấy danh sách người dùng thành công'
        ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getById(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID']);
            }

            $key = $this->model->getByIdActivationKey($id);
            if (!$key) {
                return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy key']);
            }

            return $this->jsonResponse($response, 200, [
                'data' => $key,
                'success' => 'Lấy key thành công'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function getKeysByOrder(Request $request, Response $response, array $args): Response
    {
        try {
            $orderId = $args['order_id'] ?? null;
            if (!$orderId) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu order_id']);
            }

            $result = $this->model->getKeysByOrderId($orderId);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, ['error' => $e->getMessage()]);
        }
    }

    public function getKeysByUser(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            if (!$user) {
                return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
            }

            $params = $request->getQueryParams();

            $result = $this->model->getKeysByUserId($user->id, $params);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, ['error' => $e->getMessage()]);
        }
    }

    public function resetKeyByUser(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            if (!$user) {
                return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
            }

            $keyId = $args['key_id'] ?? null;
            if (!$keyId) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu key_id']);
            }

            $data = $request->getParsedBody();
            $reason = $data['reason'] ?? 'Reset để sử dụng';

            $result = $this->model->resetKeyByUser($user->id, $keyId, $reason);
            $statusCode = isset($result['error']) ? 400 : 200;

            return $this->jsonResponse($response, $statusCode, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, ['error' => $e->getMessage()]);
        }
    }


     public function getKeysByOrderForUser(Request $request, Response $response, array $args): Response
    {
        try {
               $user = $request->getAttribute('user');
            if (!$user) {
                return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
            }
            $orderId = $args['order_id'] ?? null;
            if (!$orderId) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu order_id']);
            }

            $result = $this->model->getKeysByOrderAndUser($orderId, $user->id);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, ['error' => $e->getMessage()]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->createKey($data);

            return $this->jsonResponse($response, 201, $result);
        } catch (\Respect\Validation\Exceptions\ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function createKeysForOrder(Request $request, Response $response): Response
    {
        try {
            $body = $request->getParsedBody();
            $model = new ActivationKeyModel();
            $result = $model->createKeysForOrder($body);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }


    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->updateKey($id, $data);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Respect\Validation\Exceptions\ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function updateActiveStatus(Request $request, Response $response, array $args): Response
{
    try {
        $id = $args['id'] ?? null;
        if (!$id) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID key']);
        }

        $data = $request->getParsedBody();
        $active = isset($data['active']) ? (int)$data['active'] : null;

        if ($active === null) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu trường active']);
        }

        $result = $this->model->updateKeyActiveStatus($id, $active);
        $statusCode = isset($result['error']) ? 400 : 200;

        return $this->jsonResponse($response, $statusCode, $result);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 500, ['error' => $e->getMessage()]);
    }
}


    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID']);
            }

            $result = $this->model->deleteKey($id);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function createMultipleKeysByAdmin($request, $response)
{
    try {
        $data = $request->getParsedBody();

        $model = new ActivationKeyModel();
        $result = $model->createMultipleKeysByAdmin($data);

        return $this->jsonResponse($response, 200, $result);
    } catch (\Throwable $e) {
        error_log("createMultipleKeysByAdmin ERROR: " . $e->getMessage());
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