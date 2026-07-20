<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\VoucherModel;
use Respect\Validation\Exceptions\ValidationException;

class VoucherController
{
    private VoucherModel $model;

    public function __construct()
    {
        $this->model = new VoucherModel();
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $vouchers = $this->model->getAll($params);
            return $this->jsonResponse($response, 200, [
                'data'       => $vouchers['data'],
                'pagination' => $vouchers['pagination'],
                'success' => 'Lấy danh sách voucher thành công'
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
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID voucher']);
            }

            $voucher = $this->model->getByIdVoucher($id);
            if (!$voucher) {
                return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy voucher']);
            }

            return $this->jsonResponse($response, 200, [
                'data' => $voucher,
                'success' => 'Lấy voucher thành công'
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

            $result = $this->model->createVoucher($data);

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
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID voucher']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->updateVoucher($id, $data);

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
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID voucher']);
            }

            $result = $this->model->deleteVoucher($id);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

     public function checkVoucher(Request $request, Response $response): Response
{
    try {
        $data = $request->getParsedBody();
         $user = $request->getAttribute('user');
        $code   = $data['code']   ?? null;
        $userId = $user->id;
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0;

        if (!$code || !$userId || $amount <= 0) {
            return $this->jsonResponse($response, 400, [
                'error' => 'Thiếu dữ liệu: cần có code, user_id và amount hợp lệ'
            ]);
        }

        $result = $this->model->checkVoucher($code, $userId, $amount);

        if (isset($result['error'])) {
            return $this->jsonResponse($response, 400, $result);
        }

        return $this->jsonResponse($response, 200, $result);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 500, ['error' => $e->getMessage()]);
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