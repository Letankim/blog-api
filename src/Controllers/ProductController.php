<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ProductModel;
use Respect\Validation\Exceptions\ValidationException;

class ProductController
{
    private ProductModel $model;

    public function __construct()
    {
        $this->model = new ProductModel();
    }

    public function getAll(Request $request, Response $response): Response
{
    try {
        $params = $request->getQueryParams();
        $result = $this->model->getAll($params);

        return $this->jsonResponse($response, 200, [
            'data'       => $result['data'],
            'pagination' => $result['pagination'],
            'success'    => 'Lấy danh sách sản phẩm thành công'
        ]);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}

    public function getById(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID sản phẩm']);

        try {
            $product = $this->model->getByIdProduct($id);
            if (!$product) return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy sản phẩm']);
            return $this->jsonResponse($response, 200, ['data' => $product, 'success' => 'Lấy sản phẩm thành công']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            $result = $this->model->createProduct($data);
            return $this->jsonResponse($response, 201, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, ['error' => 'Dữ liệu không hợp lệ', 'details' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID sản phẩm']);

        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            $result = $this->model->updateProduct($id, $data);
            return $this->jsonResponse($response, 200, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, ['error' => 'Dữ liệu không hợp lệ']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id) return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID sản phẩm']);

        try {
            $result = $this->model->deleteProduct($id);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    private function jsonResponse(Response $response, int $status, array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $response->getBody()->write($json);
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}