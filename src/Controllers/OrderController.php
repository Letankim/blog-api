<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\OrderModel;
use Respect\Validation\Exceptions\ValidationException;

class OrderController
{
    private OrderModel $model;

    public function __construct()
    {
        $this->model = new OrderModel();
    }

    public function getAll(Request $request, Response $response): Response
{
    try {
        $params = $request->getQueryParams();
        $result = $this->model->getAll($params);

        return $this->jsonResponse($response, 200, [
            'data'       => $result['data'],
            'pagination' => $result['pagination'],
            'success'    => 'Lấy danh sách đơn hàng thành công'
        ]);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}

public function getOrderItems(Request $request, Response $response, array $args): Response
{
    try {
        $user = $request->getAttribute('user');
        if (!$user || !isset($user->id)) {
            return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
        }

        $orderId = $args['id'] ?? null;
        if (!$orderId) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID đơn hàng']);
        }

        $role = $user->role ?? 'user';
        $order = $this->model->getOrderById($orderId);

        if (!$order) {
            return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy đơn hàng']);
        }

        if ($role !== 'admin' && $order['user_id'] !== $user->id) {
            return $this->jsonResponse($response, 403, ['error' => 'Không có quyền truy cập đơn hàng này']);
        }

        $items = $this->model->getOrderItemsByOrderId($orderId);

        return $this->jsonResponse($response, 200, [
            'data' => $items,
            'success' => 'Lấy danh sách sản phẩm trong đơn hàng thành công'
        ]);
    } catch (\Throwable $e) {
        error_log("getOrderItems() ERROR: " . $e->getMessage());
        return $this->jsonResponse($response, 500, ['error' => 'Lỗi máy chủ nội bộ']);
    }
}

public function getOrderById(Request $request, Response $response, array $args): Response
{
    try {
        $user = $request->getAttribute('user');
        if (!$user || !isset($user->id)) {
            return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
        }

        $orderId = $args['id'] ?? null;
        if (!$orderId) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID đơn hàng']);
        }

        $role = $user->role ?? 'user';
        $order = $this->model->getOrderById($orderId);

        if (!$order) {
            return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy đơn hàng']);
        }

        if ($role !== 'admin' && $order != null && $order['customer']['id'] !== $user->id) {
    return $this->jsonResponse($response, 403, ['error' => 'Không có quyền truy cập đơn hàng này']);
}


        return $this->jsonResponse($response, 200, [
            'data' => $order,
            'success' => 'Lấy chi tiết đơn hàng thành công'
        ]);

    } catch (\Throwable $e) {
        error_log("getOrderById() ERROR: " . $e->getMessage());
        return $this->jsonResponse($response, 500, ['error' => 'Lỗi máy chủ nội bộ']);
    }
}


public function getHistory(Request $request, Response $response): Response
{
    try {
        $user = $request->getAttribute('user');
        if (!$user || !isset($user->id)) {
            return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
        }

        $params = $request->getQueryParams();
        $result = $this->model->getHistory($user->id, $params); 

        return $this->jsonResponse($response, 200, [
            'data'       => $result['data'],
            'pagination' => $result['pagination'],
            'success'    => 'Lấy lịch sử mua hàng thành công'
        ]);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}

public function create(Request $request, Response $response): Response
{
    try {
        $user = $request->getAttribute('user');
        if (!$user) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        $data = $request->getParsedBody();

        $required = ['payment_method', 'items'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->jsonResponse($response, 400, ['error' => "Thiếu $field"]);
            }
        }

        $input = [
            'user_id' => $user->id,
            'voucher_id' => $data['voucher_id'] ?? null,
            'items' => $data['items'],
            'customer_info' => $data['customer_info'] ?? [] 
        ];

        $result = $this->model->createOrderWithPayment($input, $data['payment_method']);

        return $this->jsonResponse($response, 201, $result);

    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}

public function getOrderByIdByCheck(Request $request, Response $response, array $args): Response
{
    try {
        $user = $request->getAttribute('user');
        if (!$user || !isset($user->id)) {
            return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
        }
        $data = $request->getParsedBody();
        $orderCode = $data['order_code'] ?? null;
        if (!$orderCode) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu orderCode đơn hàng']);
        }

        $role = $user->role ?? 'user';
        $order = $this->model->getOrderByIdOrderCode($orderCode);

        if (!$order) {
            return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy đơn hàng']);
        }

        if ($role !== 'admin' && $order != null && $order['customer']['id'] !== $user->id) {
    return $this->jsonResponse($response, 403, ['error' => 'Không có quyền truy cập đơn hàng này']);
}


        return $this->jsonResponse($response, 200, [
            'data' => $order,
            'success' => 'Lấy chi tiết đơn hàng thành công'
        ]);

    } catch (\Throwable $e) {
        error_log("getOrderById() ERROR: " . $e->getMessage());
        return $this->jsonResponse($response, 500, ['error' => 'Lỗi máy chủ nội bộ']);
    }
}

    public function paymentCallback(Request $request, Response $response): Response
{
    $query = $request->getQueryParams();

    $payOSData = [
        'orderCode' => $query['orderCode'] ?? null,
        'code'      => $query['code'] ?? null,
        'status'    => $query['status'] ?? null
    ];

    $result = $this->model->handlePaymentCallback($payOSData);

    $statusCode = $result['success'] ? 200 : 400;
    return $this->jsonResponse($response, $statusCode, $result);
}

    public function cancelOrder(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        if (!$user) {
            return $this->jsonResponse($response, 401, ['error' => 'Unauthorized']);
        }

        $data = $request->getParsedBody();

        $result = $this->model->cancelOrder($data['order_id'], $user->id);

        $statusCode = $result['success'] ? 200 : 400;
        return $this->jsonResponse($response, $statusCode, $result);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID đơn hàng']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->updateOrder($id, $data);

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

    public function updateStatus(Request $request, Response $response, array $args): Response
{
    try {
        $id = $args['id'] ?? null;
        $body = $request->getParsedBody();
        $status = $body['status'] ?? null;

        if (!$id || !$status) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID hoặc trạng thái']);
        }

        $result = $this->model->updateStatus($id, $status);
        return $this->jsonResponse($response, 200, $result);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}


    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID đơn hàng']);
            }

            $result = $this->model->deleteOrder($id);

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