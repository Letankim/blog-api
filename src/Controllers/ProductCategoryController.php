<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ProductCategoryModel;
use Respect\Validation\Exceptions\ValidationException;

class ProductCategoryController
{
    private ProductCategoryModel $model;

    public function __construct()
    {
        $this->model = new ProductCategoryModel();
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $categories = $this->model->getAllProductCategories($params);

            return $this->jsonResponse($response, 200, [
    'data'       => $categories['data'],
    'pagination' => $categories['pagination'],
    'success'    => 'Lấy danh mục bài viết thành công'
]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, [
                'error' => 'Lỗi hệ thống',
                'details' => $e->getMessage()
            ]);
        }
    }

    public function getById(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID danh mục']);
        }

        try {
            $category = $this->model->getProductCategoryById($id);
            if (!$category) {
                return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy danh mục']);
            }

            return $this->jsonResponse($response, 200, [
                'data' => $category,
                'success' => 'Lấy danh mục thành công'
            ]);
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

            $result = $this->model->createProductCategory($data);

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
        $id = $args['id'] ?? null;
        if (!$id) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID danh mục']);
        }

        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->updateProductCategory($id, $data);

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
        $id = $args['id'] ?? null;
        if (!$id) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID danh mục']);
        }

        try {
            $result = $this->model->deleteProductCategory($id);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    private function jsonResponse(Response $response, int $status, array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $response->getBody()->write($json);
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}