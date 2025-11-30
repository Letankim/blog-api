<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\BagrModel;
use Respect\Validation\Exceptions\ValidationException;

class BagrController
{
    private BagrModel $model;

    public function __construct()
    {
        $this->model = new BagrModel();
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $result = $this->model->getAll($params);

            return $this->jsonResponse($response, 200, [
                'data'       => $result['data'],      
                'pagination' => $result['pagination'], 
                'success'    => 'Lấy danh sách liên hệ thành công'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function getById(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id || !is_numeric($id)) return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID liên hệ']);

        try {
            $bagr = $this->model->getByIdBagr((int)$id);
            if (!$bagr) return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy liên hệ']);
            return $this->jsonResponse($response, 200, ['data' => $bagr, 'success' => 'Lấy liên hệ thành công']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $data = $request->getParsedBody();
            if (!is_array($data)) return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);

            if ($user && isset($user->id)) {
                $data['user_id'] = $user->id;
            }

            $result = $this->model->createBagr($data);

            return $this->jsonResponse($response, 201, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, ['error' => 'Dữ liệu không hợp lệ', 'details' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;
        if (!$id || !is_numeric($id)) return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID liên hệ']);

        try {
            $result = $this->model->deleteBagr((int)$id);
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