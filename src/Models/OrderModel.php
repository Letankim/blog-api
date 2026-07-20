<?php
namespace App\Models;

use App\Config\settings;
use App\Services\NotificationService;
use App\Services\PayOSService;
use PDO;
use PDOException;
use Respect\Validation\Validator as v;
use App\Validation\ValidationRules as rule;


class OrderModel extends BaseModel {

     private NotificationModel $notificationModel;
    private NotificationService $notify;


    public function __construct()
    {
         parent::__construct(); 
        $this->notificationModel = new NotificationModel();
        $this->notify = new NotificationService();
    }

    public function getAll(array $params): array
    {
        $joins = [
            'LEFT JOIN order_items oi ON orders.id = oi.order_id',
            'LEFT JOIN products p ON oi.product_id = p.id'
        ];
        
        $groupBy = 'orders.id';

        $result = $this->getAllWithPaginationAndFilter('orders', $params, $joins, '', $groupBy);

        $grouped = [];
        foreach ($result['data'] as $row) {
            $id = $row['id'] ?? null;
            if (!$id) continue;

            if (!isset($grouped[$id])) {
                $grouped[$id] = $row;
                $grouped[$id]['items'] = [];
            }

            if (!empty($row['product_id'])) {
                $grouped[$id]['items'][] = [
                    'product_name' => $row['product_name_at_purchase'],
                    'quantity'     => (int)$row['quantity'],
                    'price'        => (float)$row['price_at_purchase']
                ];
            }
        }

        $result['data'] = array_values($grouped);
        return $result; 
    }

    public function getOrderById(string $orderId, ?string $userId = null): ?array
{
    try {
        $sql = "
            SELECT 
                orders.*, 
                u.username AS customer_username,
                u.email AS customer_email,
                u.phone_number AS customer_phone,
                oi.id AS order_item_id,
                oi.product_id, 
                oi.quantity, 
                oi.price_at_purchase, 
                oi.product_name_at_purchase,
                p.name AS product_name,
                pi.image_url AS main_image,
                p.type
            FROM orders
            LEFT JOIN users u ON orders.user_id = u.id
            LEFT JOIN order_items oi ON orders.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE orders.id = :order_id
        ";

        $params = ['order_id' => $orderId];

        // Nếu có userId thì bắt buộc kèm theo điều kiện kiểm tra
        if ($userId !== null) {
            $sql .= " AND orders.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $sql .= "
            GROUP BY 
                orders.id, u.username, u.email, u.phone_number, 
                oi.id, oi.product_id, oi.quantity, oi.price_at_purchase, 
                oi.product_name_at_purchase, p.name, pi.image_url, p.type
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return null;
        }

        $order = [
            'id' => $rows[0]['id'],
            'order_code' => $rows[0]['order_code'],
            'total_price' => $rows[0]['total_price'],
            'status' => $rows[0]['status'],
            'payment_method' => $rows[0]['payment_method'],
            'created_at' => $rows[0]['created_at'],
            'customer' => [
                'id'=> $rows[0]['user_id'],
                'username' => $rows[0]['customer_username'],
                'email' => $rows[0]['customer_email'],
                'phone' => $rows[0]['customer_phone'],
            ],
            'items' => []
        ];

        foreach ($rows as $row) {
            if (!empty($row['product_id'])) {
                $order['items'][] = [
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name_at_purchase'] ?? $row['product_name'],
                    'quantity' => (int)$row['quantity'],
                    'price' => (float)$row['price_at_purchase'],
                    'image' => $row['main_image'],
                    'type' => $row['type'],
                ];
            }
        }

        return $order;

    } catch (PDOException $e) {
        error_log("getOrderById() ERROR: " . $e->getMessage());
        return null;
    }
}

    public function getOrderByIdOrderCode(string $orderCode, ?string $userId = null): ?array
{
    try {
        $sql = "
            SELECT 
                orders.*, 
                u.username AS customer_username,
                u.email AS customer_email,
                u.phone_number AS customer_phone,
                oi.id AS order_item_id,
                oi.product_id, 
                oi.quantity, 
                oi.price_at_purchase, 
                oi.product_name_at_purchase,
                p.name AS product_name,
                pi.image_url AS main_image,
                p.type
            FROM orders
            LEFT JOIN users u ON orders.user_id = u.id
            LEFT JOIN order_items oi ON orders.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE orders.order_code = :order_id
        ";

        $params = ['order_id' => $orderCode];

        if ($userId !== null) {
            $sql .= " AND orders.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $sql .= "
            GROUP BY 
                orders.id, u.username, u.email, u.phone_number, 
                oi.id, oi.product_id, oi.quantity, oi.price_at_purchase, 
                oi.product_name_at_purchase, p.name, pi.image_url, p.type
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return null;
        }

        $order = [
            'id' => $rows[0]['id'],
            'order_code' => $rows[0]['order_code'],
            'total_price' => $rows[0]['total_price'],
            'status' => $rows[0]['status'],
            'payment_method' => $rows[0]['payment_method'],
            'created_at' => $rows[0]['created_at'],
            'customer' => [
                'id'=> $rows[0]['user_id'],
                'username' => $rows[0]['customer_username'],
                'email' => $rows[0]['customer_email'],
                'phone' => $rows[0]['customer_phone'],
            ],
            'items' => []
        ];

        foreach ($rows as $row) {
            if (!empty($row['product_id'])) {
                $order['items'][] = [
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name_at_purchase'] ?? $row['product_name'],
                    'quantity' => (int)$row['quantity'],
                    'price' => (float)$row['price_at_purchase'],
                    'image' => $row['main_image'],
                    'type' => $row['type'],
                ];
            }
        }

        return $order;

    } catch (PDOException $e) {
        error_log("getOrderById() ERROR: " . $e->getMessage());
        return null;
    }
}


    public function getHistory(string $userId, array $params): array
    {
        $extraWhere = " AND orders.user_id = :user_id";
        $params['user_id'] = $userId;

        $joins = [
            'LEFT JOIN order_items oi ON orders.id = oi.order_id',
            'LEFT JOIN products p ON oi.product_id = p.id'
        ];
        $groupBy = 'orders.id';

        $result = $this->getAllWithPaginationAndFilter('orders', $params, $joins, $extraWhere, $groupBy);

        $grouped = [];
        foreach ($result['data'] as $row) {
            $id = $row['id'] ?? null;
            if (!$id) continue;

            if (!isset($grouped[$id])) {
                $grouped[$id] = $row;
                $grouped[$id]['items'] = [];
            }

            if (!empty($row['product_id'])) {
                $grouped[$id]['items'][] = [
                    'product_name' => $row['product_name_at_purchase'],
                    'quantity'     => (int)$row['quantity'],
                    'price'        => (float)$row['price_at_purchase']
                ];
            }

            if (isset($row['customer_info'])) {
                $grouped[$id]['customer_info'] = json_decode($row['customer_info'], true);
            }
        }

        $result['data'] = array_values($grouped);
        return $result; 
    }

    public function createOrder($data) {
        $this->validate($data, [
            'user_id'        => v::uuid(),
            'order_code'     => v::regex('/^[0-9]{6}$/')->length(6, 6),
            'total_price'    => v::number()->positive()->regex('/^\d+\.\d{2}$/'),
            'voucher_id'     => v::optional(),
            'status'         => v::in(['pending', 'completed', 'cancelled', 'failed']),
            'payment_method' => v::in(['bank_transfer', 'cash_on_delivery'])
        ]);

        $data['id'] = $this->generateUUID();
        $this->create('orders', $data);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->validate($item, [
                    'product_id' => v::uuid(),
                    'quantity' => v::intVal()->min(1),
                    'price_at_purchase' => v::decimal(2)->positive(),
                    'product_name_at_purchase' => v::stringType()->length(1, 255)
                ]);
                $item['order_id'] = $data['id'];
                $this->create('order_items', $item);
            }
        }

        return ['success' => 'Tạo đơn hàng thành công', 'id' => $data['id']];
    }

public function createOrderWithPayment(array $input, string $paymentMethod): array
{
    $this->beginTransaction();

    try {
        $stmt = $this->pdo->prepare("SELECT * FROM site_settings WHERE is_use = 1 LIMIT 1");
        $stmt->execute();
        $setting = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$setting) {
            throw new \Exception("Không tìm thấy cấu hình site đang được sử dụng");
        }

        $settingJson = [];
        if (!empty($setting['setting_json'])) {
            $decoded = json_decode($setting['setting_json'], true);
            $settingJson = $decoded ?: [];
        }

        $paymentConfig = $settingJson['payment'] ?? $settingJson;
        $paymentSupport = $paymentConfig['paymentSupport'] ?? [];
        $requirePaymentInfo = $paymentConfig['requirePaymentInfo'] ?? false;
        $requireUserFields = $paymentConfig['requireUserFields'] ?? [];

        if (empty($paymentSupport[$paymentMethod]) || !$paymentSupport[$paymentMethod]['enabled']) {
            throw new \Exception("Phương thức thanh toán '$paymentMethod' hiện không được hỗ trợ");
        }

        $customerInfo = $input['customer_info'] ?? [];
        if ($requirePaymentInfo) {
            foreach ($requireUserFields as $field) {
                if (empty($customerInfo[$field])) {
                    throw new \Exception("Thiếu thông tin bắt buộc: $field");
                }
            }
        }

        $userId = $input['user_id'];
        $voucherId = $input['voucher_id'] ?? null;
        $items = $input['items'];

        $totalPrice = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $product = $this->getProductById($item['product_id']);
            if (!$product || $product['status'] !== 'active') {
                throw new \Exception("Sản phẩm không tồn tại hoặc không khả dụng: {$item['product_id']}");
            }

            $quantity = (int)$item['quantity'];
            if ($quantity < 1) {
                throw new \Exception("Số lượng không hợp lệ");
            }

            if ($product['stock'] < $quantity) {
                throw new \Exception("Sản phẩm '{$product['name']}' không đủ hàng trong kho");
            }

            $price = (float)($product['sale_price'] > 0 ? $product['sale_price'] : $product['price']);
            $totalPrice += $price * $quantity;

            $orderItems[] = [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'price_at_purchase' => $price,
                'product_name_at_purchase' => $product['name']
            ];
        }

        $finalPrice = $totalPrice;
        if ($voucherId) {
            $voucher = $this->getVoucherById($voucherId);
            if (!$voucher || !$this->isVoucherValid($voucher, $userId, $totalPrice)) {
                throw new \Exception("Voucher không hợp lệ hoặc không áp dụng được");
            }

            $finalPrice = $this->applyVoucher($totalPrice, $voucher);
            if ($finalPrice < 0) $finalPrice = 0;
        }

        $orderId = $this->generateUUID();
        $orderCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $orderData = [
            'id' => $orderId,
            'user_id' => $userId,
            'order_code' => $orderCode,
            'total_price' => $totalPrice,
            'customer_info' => json_encode($customerInfo, JSON_UNESCAPED_UNICODE),
            'voucher_id' => $voucherId ?? null,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->create('orders', $orderData);

        foreach ($orderItems as $item) {
            $item['id'] = $this->generateUUID();
            $item['order_id'] = $orderId;
            $this->create('order_items', $item);
        }

        foreach ($orderItems as $item) {
            $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                ->execute([$item['quantity'], $item['product_id']]);
        }

        $checkoutUrl = null;

        switch ($paymentMethod) {
            case 'cash_on_delivery':
                break;

            case 'bank_transfer':
                $clientId = Settings::get('PAYOS.CLIENT_ID');
                $apiKey = Settings::get('PAYOS.API_KEY');
                $checksumKey = Settings::get('PAYOS.CHECKSUM_KEY');

                try {
                    $payOSService = new PayOSService($clientId, $apiKey, $checksumKey);

                    $payOSItems = array_map(fn($i) => [
                        'name' => $i['product_name_at_purchase'],
                        'quantity' => $i['quantity'],
                        'price' => (int)($i['price_at_purchase'] * 100)
                    ], $orderItems);

                    $checkoutUrl = $payOSService->createPaymentLink(
                        amount: (int)($finalPrice),
                        items: $payOSItems,
                        orderCode: (int)$orderCode,
                        description: "Thanh toán đơn hàng #$orderCode",
                        returnUrl: Settings::get('PAYOS.RETURN_URL') . "?orderCode=$orderCode",
                        cancelUrl: Settings::get('PAYOS.CANCEL_URL') . "?orderCode=$orderCode"
                    );

                    if (str_starts_with($checkoutUrl, 'ERROR')) {
                        throw new \Exception("Lỗi tạo link thanh toán: $checkoutUrl");
                    }
                } catch (\Throwable $e) {
                    $this->rollBack();
                    error_log("PayOSService init failed: " . $e->getMessage());
                    throw new \Exception("Failed to initialize PayOS: " . $e->getMessage());
                }
                break;

            default:
                throw new \Exception("Phương thức thanh toán '$paymentMethod' chưa được hỗ trợ xử lý.");
        }

        $this->commit();

        $notify = $this->notify; 

        register_shutdown_function(function() use ($orderData, $orderId, $orderCode, $notify) {
            try {
                $user = $this->getUserById($orderData['user_id']);
                $notify->sendToAdminChannel('order_created', [
                    'title' => 'Đơn hàng mới!',
                    'body'  => "Có đơn hàng #{$orderId} vừa được tạo.",
                    'data'  => ['orderId' => $orderId, 'orderCode' => $orderCode]
                ]);
             
                $notify->sendOrderToGuestChannel('order_created', [
                    'title' => 'Đơn hàng mới!',
                    'body'  => $user['username'] . " vừa đặt hàng vào lúc " . date('H:i d/m/Y') . ".",
                    'data'  => ['orderId' => $orderId, 'orderCode' => $orderCode]
                ]);

            } catch (\Exception $e) {
                error_log("Notification to admin failed: " . $e->getMessage());
            }
        });

        $result = [
            'success' => true,
            'order_id' => $orderId,
            'order_code' => $orderCode,
            'status' => 'pending'
        ];

        if (!empty($checkoutUrl)) {
            $result['checkout_url'] = $checkoutUrl;
        }

        return $result;

    } catch (\Exception $e) {
        $this->rollBack();
        error_log("Order creation failed: " . $e->getMessage());
        throw $e;
    }
}

    private function getProductById(string $id): ?array
    {
        return $this->getById('products', $id);
    }

    private function getVoucherById(string $id): ?array
    {
        return $this->getById('vouchers', $id);
    }

    private function getUserById(string $id): ?array
    {
        return $this->getById('users', $id);
    }


   private function isVoucherValid(array $voucher, string $userId, float $totalPrice): bool
{
    try {
        if (!isset($voucher['id']) || empty($voucher['id'])) {
            error_log("Voucher ID empty in isVoucherValid");
            return false;
        }
        if ($voucher['status'] !== 'active') return false;
        
        $expiresAt = $voucher['expires_at'] ?? null;
        if ($expiresAt && (strtotime($expiresAt) === false || strtotime($expiresAt) < time())) {
            error_log("Voucher expired: " . ($expiresAt ?? 'null'));
            return false;
        }
        if (($voucher['min_order_amount'] ?? 0) > $totalPrice) return false;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM voucher_usage WHERE voucher_id = ? AND user_id = ?");
        $stmt->execute([$voucher['id'], $userId]);
        $used = (int)$stmt->fetchColumn();

        $maxUses = $voucher['max_uses_per_user'] ?? 1;
        $valid = $used < $maxUses;
        error_log("Voucher usage check: used=$used, max=$maxUses, valid=$valid"); 
        return $valid;
    } catch (\PDOException $e) {
        error_log("PDO error in isVoucherValid: " . $e->getMessage());
        return false;  
    } catch (\Exception $e) {
        error_log("Error in isVoucherValid: " . $e->getMessage());
        return false;
    }
}

private function applyVoucher(float $total, array $voucher): float
{
    $discountValue = (float)($voucher['discount_value'] ?? 0);  
    if (!isset($voucher['discount_type'])) {
        error_log("Missing discount_type in voucher");
        return $total;
    }
    
    if ($voucher['discount_type'] === 'percent') {
        $discount = min(100, max(0, $discountValue)); 
        return $total * (1 - $discount / 100);
    }
    return max(0, $total - $discountValue); 
}

private function incrementVoucherUsage(string $voucherId, string $userId): void
{
    if (empty($voucherId) || empty($userId)) {
        error_log("Empty voucherId or userId in incrementVoucherUsage");
        return;  
    }
    
    try {
        $sql = "
            INSERT INTO voucher_usage (voucher_id, user_id, used_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE used_at = NOW()
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$voucherId, $userId]);
        error_log("Voucher usage incremented: voucher=$voucherId, user=$userId");
    } catch (\PDOException $e) {
        error_log("PDO error in incrementVoucherUsage: " . $e->getMessage() . " | SQL: " . $sql);
    }
}

public function handlePaymentCallback(array $payOSData): array
{
    $this->beginTransaction();

    try {
        $orderCode = $payOSData['orderCode'] ?? null;
        $code = $payOSData['code'] ?? null;
        $status = $payOSData['status'] ?? null;

        if (!$orderCode) {
            throw new \Exception("Thiếu orderCode trong callback");
        }

        $order = $this->getOrderByCode($orderCode);
        if (!$order) {
            throw new \Exception("Không tìm thấy đơn hàng: $orderCode");
        }

        if ($order['status'] !== 'pending') {
            throw new \Exception("Đơn hàng đã được xử lý trước đó: {$order['status']}");
        }

        if ($order['payment_method'] !== 'bank_transfer') {
            throw new \Exception("Đơn hàng không phải thanh toán credit card");
        }

        if ($code !== '00') {
            $this->rollbackPaymentFailure($order, 'failed');
            throw new \Exception("Thanh toán thất bại với code: $code");
        }

        switch ($status) {
            case 'PAID':
                $payOSService = new \App\Services\PayOSService(
                    Settings::get('PAYOS.CLIENT_ID'),
                    Settings::get('PAYOS.API_KEY'),
                    Settings::get('PAYOS.CHECKSUM_KEY')
                );

                $payment = $payOSService->verifyPayment((int)$orderCode);
                if (!$payment || $payment['status'] !== 'PAID') {
                    $this->rollbackPaymentFailure($order, 'failed');
                    throw new \Exception("PayOS xác nhận thanh toán thất bại");
                }

                if ((int)($payment['amount']) !== (int)$order['total_price']) {
                    $this->rollbackPaymentFailure($order, 'failed');
                    throw new \Exception("Số tiền thanh toán không khớp");
                }

                $this->update('orders', $order['id'], [
                    'status' => 'completed'
                ]);

                $orderItems = $this->getOrderItemsByOrderId($order['id']);
                $startTime = date('Y-m-d H:i:s');
                $expirationTime = date('Y-m-d H:i:s', strtotime('+4 months', strtotime($startTime)));
                $createdKeys = [];

                foreach ($orderItems as $item) {
                    $product = $this->getProductById($item['product_id']);
                    if ($product && $product['type'] === 'activation_key') {
                        for ($i = 0; $i < (int)$item['quantity']; $i++) {
                            $keyData = [
                                'key_value' => $this->generateActivationKey(),
                                'status' => 'unused',
                                'email' => null,
                                'buyer' => $order['user_id'],
                                'user_info' => null,
                                'active' => 1,
                                'device_id' => null,
                                'app_name' =>$product['app_name'] ?? '',
                                'usage_count' => 0,
                                'note' => null,
                                'type' => 'bán lẻ',
                                'logger' => null,
                                'reason_for_reset' => null,
                                'number_of_resets' => 0,
                                'account_history' => null,
                                'used_at' => null,
                                'start_time' => $startTime,
                                'expiration_time' => $expirationTime,
                                'order_id' => $order['id']
                            ];
                            $keyResult = $this->createKey($keyData);
                            $createdKeys[] = $keyResult['id'];
                        }

                        if (!empty($createdKeys)) {
                            $this->notificationModel->createNotification([
                                'user_id' => $order['user_id'],
                                'title' => 'Key kích hoạt đã được tạo',
                                'message' => 'Key kích hoạt cho đơn hàng #' . $orderCode . ' đã được tạo thành công. Vui lòng kiểm tra chi tiết đơn hàng để lấy key.',
                                'type' => 'key',
                                'related_id' => $order['id']
                            ]);
                        }
                    }
                }

                $this->notificationModel->createNotification([
                    'user_id' => $order['user_id'],
                    'title' => 'Đơn hàng #' . $orderCode . ' đã thanh toán thành công',
                    'message' => 'Đơn hàng của bạn đã được xác nhận thanh toán. Cảm ơn bạn đã mua hàng!',
                    'type' => 'order',
                    'related_id' => $order['id']
                ]);

                $this->commit();

                return [
                    'success' => true,
                    'message' => 'Thanh toán thành công',
                    'order_id' => $order['id'],
                    'transaction_id' => $payment['id']
                ];

            case 'CANCELLED':
                $this->rollbackPaymentFailure($order, 'cancelled');
                $this->notificationModel->createNotification([
                'user_id' => $order['user_id'],
                'title' => 'Đơn hàng #' . $orderCode . ' đã bị hủy',
                'message' => 'Đơn hàng của bạn đã bị hủy. Nếu bạn có thắc mắc, vui lòng liên hệ hỗ trợ.',
                'type' => 'order',
                'related_id' => $order['id']
            ]);
                $this->commit();
                return [
                    'success' => true,
                    'message' => 'Thanh toán đã bị hủy',
                    'order_id' => $order['id'],
                    'status' => 'cancelled'
                ];

            default:
                $this->rollbackPaymentFailure($order, 'failed');
                $this->notificationModel->createNotification([
                    'user_id' => $order['user_id'],
                    'title' => 'Đơn hàng #' . $orderCode . ' thanh toán thất bại',
                    'message' => 'Thanh toán đơn hàng thất bại. Vui lòng thử lại hoặc liên hệ hỗ trợ.',
                    'type' => 'order',
                    'related_id' => $order['id']
                ]);
                throw new \Exception("Thanh toán không thành công: $status");
        }

    } catch (\Throwable $e) {
    $this->rollBack();
    $errorMsg = "Payment callback failed: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString();
    error_log($errorMsg);
    error_log("Data: " . json_encode($payOSData, JSON_UNESCAPED_UNICODE));
    return [
        'success' => false,
        'message' => $e->getMessage()
    ];
}
}


public function cancelOrder(string $orderId, string $userId): array
{
    $this->beginTransaction();

    try {
        $order = $this->getOrderById($orderId, $userId);
        if (!$order) {
            throw new \Exception("Không tìm thấy đơn hàng của người này.");
        }

        if ($order['status'] !== 'pending') {
            throw new \Exception("Chỉ có đơn hàng pending mới có thể hủy. Trạng thái hiện tại: {$order['status']}");
        }

        $this->update('orders', $order['id'], [
            'status' => 'cancelled'
                ]);

        $orderItems = $this->getOrderItemsByOrderId($order['id']);
        foreach ($orderItems as $item) {
            $product = $this->getProductById($item['product_id']);
            if ($product) {
                $newQuantity = $product['stock'] + (int)$item['quantity'];
                $this->update('products', $product['id'], ['stock' => $newQuantity]);
            }
        }

        $this->notificationModel->createNotification([
            'user_id' => $userId,
            'title' => 'Đơn hàng #' . $order['order_code'] . ' đã bị hủy',
            'message' => 'Đơn hàng của bạn đã được hủy thành công. Sản phẩm đã được trả về kho.',
            'type' => 'order',
            'related_id' => $order['id']
        ]);

        $this->commit();

        return [
            'success' => true,
            'message' => 'Đơn hàng đã hủy thành công',
            'order_id' => $order['id']
        ];

    } catch (\Throwable $e) {
        $this->rollBack();
        $errorMsg = "Cancel order failed: " . $e->getMessage();
        error_log($errorMsg);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

public function getOrderItemsByOrderId(string $orderId): array
{
    $sql = "
        SELECT 
            oi.id,
            oi.order_id,
            oi.product_id,
            oi.quantity,
            oi.price_at_purchase,
            oi.product_name_at_purchase,
            p.name AS product_name,
            pi.image_url AS main_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE oi.order_id = ?
        GROUP BY oi.id, oi.product_id, oi.quantity, oi.price_at_purchase, oi.product_name_at_purchase, p.name, pi.image_url
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function createKey(array $data): array
{
    try {
        $this->validate($data, [
            'key_value'        => v::stringType()->length(1, 32)->notEmpty(),
            'status'           => v::in(['used', 'unused']),
            'email'            => v::optional(v::email()),
            'buyer'            => v::stringType()->length(1, 500)->notEmpty(),
            'user_info'        => v::optional(v::stringType()->length(1, 500)),
            'active'           => v::intVal(),
            'device_id'        => v::optional(v::stringType()->length(1, 400)),
            'app_name'         => v::optional(v::stringType()->length(1, 255)),
            'usage_count'      => v::intVal()->min(0),
            'note'             => v::optional(v::stringType()),
            'type'             => v::in(['bán lẻ', 'bán sỉ', 'khác', 'rush']),
            'logger'           => v::optional(v::stringType()),
            'reason_for_reset' => v::optional(v::stringType()),
            'number_of_resets' => v::intVal()->min(0),
            'account_history'  => v::optional(v::stringType()),
            'used_at'          => v::optional(v::dateTime('Y-m-d H:i:s')),
            'start_time'       => v::optional(v::dateTime('Y-m-d H:i:s')),
            'expiration_time'  => v::optional(v::dateTime('Y-m-d H:i:s')),
            'order_id'         => v::optional(v::uuid())
        ]);

        $data['id'] = $this->generateUUID();
        $this->create('activation_keys', $data);

        return ['success' => true, 'message' => 'Tạo key kích hoạt thành công', 'id' => $data['id']];

    } catch (\Exception $e) {
        $this->rollBack();
        error_log("Create activation key failed: " . $e->getMessage());
        throw new \Exception("Lỗi tạo key kích hoạt: " . $e->getMessage());
    }
}

private function generateActivationKey(): string
{
    return bin2hex(random_bytes(16)); 
}

private function rollbackPaymentFailure(array $order, string $newStatus): void
{
    $stmt = $this->pdo->prepare("
        SELECT oi.product_id, oi.quantity 
        FROM order_items oi 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $this->pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
            ->execute([$item['quantity'], $item['product_id']]);
    }

    $this->update('orders', $order['id'], [
        'status' => $newStatus
    ]);

    if ($order['voucher_id']) {
        $this->pdo->prepare("DELETE FROM voucher_usage WHERE voucher_id = ? AND user_id = ?")
            ->execute([$order['voucher_id'], $order['user_id']]);
    }

    error_log("Rollback payment for order: {$order['id']} → status: $newStatus - stock restored, voucher usage removed");
}

public function getOrderByCode(string $orderCode): ?array
{
    $sql = "SELECT * FROM orders WHERE order_code = :order_code";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['order_code' => $orderCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

    public function updateOrder($id, $data) {
        $this->validate($data, [
            'status' => v::optional(v::in(['pending','completed','cancelled','failed']))
        ]);
        $this->update('orders', $id, $data);
        return ['success' => 'Cập nhật đơn hàng thành công'];
    }

    public function deleteOrder($id) {
        $this->pdo->prepare("DELETE FROM order_items WHERE order_id = :id")->execute(['id' => $id]);
        $this->delete('orders', $id);
        return ['success' => 'Xóa đơn hàng thành công'];
    }

    public function updateStatus($id, $status)
{
    $this->validate(['status' => $status], [
        'status' => v::optional(v::in(['pending','completed','cancelled','failed']))
    ]);

    $this->update('orders', $id, ['status' => $status]);

    return ['success' => 'Cập nhật trạng thái đơn hàng thành công'];
}


public function getPaymentRequirements(): array
{
    $stmt = $this->pdo->prepare("SELECT setting_json FROM site_settings WHERE is_use = 1 LIMIT 1");
    $stmt->execute();
    $setting = $stmt->fetch(\PDO::FETCH_ASSOC);

    $requiredFields = [];
    $enabledPaymentMethods = [];

    if ($setting && !empty($setting['setting_json'])) {
        $decoded = json_decode($setting['setting_json'], true);
        $paymentConfig = $decoded['payment'] ?? $decoded; 
        
        if (!empty($paymentConfig['requirePaymentInfo']) && !empty($paymentConfig['requireUserFields'])) {
            $requiredFields = $paymentConfig['requireUserFields'];
        }

        if (!empty($paymentConfig['paymentSupport'])) {
            foreach ($paymentConfig['paymentSupport'] as $method => $config) {
                if (!empty($config['enabled'])) {
                    $enabledPaymentMethods[] = $method;
                }
            }
        }
    }

    return [
        'required_fields' => $requiredFields,
        'payment_methods' => $enabledPaymentMethods
    ];
}
    
}
