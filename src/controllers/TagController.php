<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\TagModel;
use Respect\Validation\Exceptions\ValidationException;

class TagController
{
    private TagModel $model;

    public function __construct()
    {
        $this->model = new TagModel();
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $tags = $this->model->getAll($params);
            return $this->jsonResponse($response, 200, [
                'data'       => $tags['data'],
               'pagination' => $tags['pagination'],
                'success' => 'Lấy danh sách tag thành công'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function getById(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID tag']);
            }

            $tag = $this->model->getByIdTag($id);
            if (!$tag) {
                return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy tag']);
            }

            return $this->jsonResponse($response, 200, [
                'data' => $tag,
                'success' => 'Lấy tag thành công'
            ]);
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

            $result = $this->model->createTag($data);

            return $this->jsonResponse($response, 201, $result);
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
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID tag']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->updateTag($id, $data);

            return $this->jsonResponse($response, 200, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID tag']);
            }

            $result = $this->model->deleteTag($id);

            return $this->jsonResponse($response, 200, $result);
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