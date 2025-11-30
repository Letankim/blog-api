<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\MaterialModel;
use Respect\Validation\Exceptions\ValidationException;

class MaterialController
{
    private MaterialModel $model;

    public function __construct()
    {
        $this->model = new MaterialModel();
    }

   public function getAll(Request $request, Response $response): Response
{
    try {
        $params = $request->getQueryParams();
        $result = $this->model->getAll($params);

        return $this->jsonResponse($response, 200, [
            'data'       => $result['data'],
            'pagination' => $result['pagination'],
            'success'    => 'Lấy danh sách tài liệu thành công'
        ]);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, [
            'error' => $e->getMessage()
        ]);
    }
}

   public function getAllPublic(Request $request, Response $response): Response
{
    try {
        $params = $request->getQueryParams();
        $result = $this->model->getAll($params);
        $params['status'] = "published";
        return $this->jsonResponse($response, 200, [
            'data'       => $result['data'],
            'pagination' => $result['pagination'],
            'success'    => 'Lấy danh sách tài liệu thành công'
        ]);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, [
            'error' => $e->getMessage()
        ]);
    }
}
    public function getById(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID tài liệu']);

        try {
            $post = $this->model->getMaterialById($id);
            if (!$post) return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy tài liệu']);
            return $this->jsonResponse($response, 200, ['data' => $post, 'success' => 'Lấy tài liệu thành công']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

     public function getPublicById(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID tài liệu']);

        try {
            $post = $this->model->getMaterialById($id);
            if($post["status"] != "published") {
                $this->jsonResponse($response, 400, ['error' => "Tài liệu này không công khai."]);
            }
            if (!$post) return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy tài liệu']);
            return $this->jsonResponse($response, 200, ['data' => $post, 'success' => 'Lấy tài liệu thành công']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            if (!$user || !isset($user->id)) {
                return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $data['user_id'] = $user->id;
            $id = $this->model->createMaterial($data);

            return $this->jsonResponse($response, 201, [
                'success' => 'Tạo tài liệu thành công',
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

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID tài liệu']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $message = $this->model->updateMaterial($id, $data);

            return $this->jsonResponse($response, 200, ['success' => $message]);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

     public function updateStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID bình luận']);
            }

            $data = $request->getParsedBody();
            $status = $data['status'] ?? null;

            if (!$status) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu trường status']);
            }

            $result = $this->model->updateStatus($id, $status);

            return $this->jsonResponse($response, 200, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Trạng thái không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID tài liệu']);
            }

            $message = $this->model->deleteMaterial($id);

            return $this->jsonResponse($response, 200, ['success' => $message]);
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