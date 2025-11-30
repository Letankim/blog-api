<?php
namespace App\Services;

use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Models\ChatSessionModel;
use App\Models\VoucherModel;
use App\config\settings;
use GuzzleHttp\Client;

class AIService
{
    private $client;
    private $apiKey;
    private $productModel;
    private $orderModel;
    private $userModel;
    private $voucherModel;
    private $chatSessionModel;

    public function __construct(
        ProductModel $productModel, 
        OrderModel $orderModel, 
        UserModel $userModel,
        VoucherModel $voucherModel,
        ChatSessionModel $chatSessionModel
    ) {
        $this->productModel = $productModel;
        $this->orderModel = $orderModel;
        $this->userModel = $userModel;
        $this->voucherModel = $voucherModel;
        $this->chatSessionModel = $chatSessionModel;
        $this->apiKey = settings::get('GEMINI_API_KEY');
        $this->client = new Client(['base_uri' => 'https://generativelanguage.googleapis.com/v1beta/']);
    }

    private function getToolsDefinition(): array
    {
        $config = $this->orderModel->getPaymentRequirements();
        $dbRequiredFields = $config['required_fields']; 
        $paymentMethods = implode(', ', $config['payment_methods']);

        $createOrderProps = [
            'product_name' => ['type' => 'STRING', 'description' => 'Tên sản phẩm'],
            'quantity' => ['type' => 'INTEGER', 'description' => 'Số lượng'],
            'payment_method' => ['type' => 'STRING', 'description' => "Phương thức thanh toán: $paymentMethods"],
            'voucher_code' => ['type' => 'STRING', 'description' => 'Mã giảm giá (nếu có, nếu không thì để null)']
        ];
        $createOrderReq = ['product_name', 'quantity', 'payment_method'];

        foreach ($dbRequiredFields as $field) {
            $desc = "Thông tin $field của khách hàng";
            if ($field === 'email') $desc = "Email (để liên hệ và kiểm tra tài khoản)";
            if ($field === 'name') $desc = "Tên đầy đủ của khách hàng";
            
            $createOrderProps[$field] = ['type' => 'STRING', 'description' => $desc];
            if (!in_array($field, $createOrderReq)) {
                $createOrderReq[] = $field;
            }
        }

        return [
            'function_declarations' => [
                [
                    'name' => 'check_account_tool',
                    'description' => 'Kiểm tra trạng thái tài khoản. Nếu chưa có -> Tự động đăng ký & gửi mail. Nếu Pending -> Báo khách kích hoạt.',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'email' => ['type' => 'STRING', 'description' => 'Email khách hàng'],
                            'name' => ['type' => 'STRING', 'description' => 'Tên khách hàng']
                        ],
                        'required' => ['email', 'name']
                    ]
                ],
                [
                    'name' => 'resend_activation_tool',
                    'description' => 'Gửi lại email kích hoạt KHI KHÁCH YÊU CẦU.',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => ['email' => ['type' => 'STRING']],
                        'required' => ['email']
                    ]
                ],
                [
                    'name' => 'check_voucher_tool',
                    'description' => 'Kiểm tra mã giảm giá xem có hợp lệ không và tính toán số tiền được giảm.',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'voucher_code' => ['type' => 'STRING', 'description' => 'Mã code khách nhập'],
                            'email' => ['type' => 'STRING', 'description' => 'Email khách hàng (để lấy User ID)'],
                            'total_amount' => ['type' => 'NUMBER', 'description' => 'Tổng tiền tạm tính của đơn hàng (Giá x Số lượng)']
                        ],
                        'required' => ['voucher_code', 'email', 'total_amount']
                    ]
                ],
                [
                    'name' => 'create_order_tool',
                    'description' => 'Tạo đơn hàng. CHỈ GỌI KHI ĐỦ THÔNG TIN và TÀI KHOẢN ĐÃ ACTIVE.',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => $createOrderProps,
                        'required' => $createOrderReq
                    ]
                ],
                [
                    'name' => 'lookup_order_tool',
                    'description' => 'Tra cứu đơn hàng bằng mã code 6 số.',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => ['order_code' => ['type' => 'STRING']],
                        'required' => ['order_code']
                    ]
                ]
            ]
        ];
    }

    private function executeFunction(array $functionCall): array
    {
        $name = $functionCall['name'];
        $args = $functionCall['args'];

        if ($name === 'check_account_tool') {
            $email = $args['email'];
            $nameArg = $args['name'];
            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                try {
                    $username = explode('@', $email)[0] . rand(100, 999);
                    $pass = bin2hex(random_bytes(6));
                    $this->userModel->register([
                        'username' => $username, 'email' => $email, 'password' => $pass, 'role' => 'user'
                    ]);
                    return [
                        'status' => 'PENDING',
                        'message' => "Đã tạo tài khoản và gửi mail kích hoạt đến $email. Vui lòng nhắc khách kiểm tra (cả mục Spam)."
                    ];
                } catch (\Exception $e) {
                    return ['status' => 'ERROR', 'message' => $e->getMessage()];
                }
            }

            if ($user['status'] === 'pending' || $user['is_verified'] == 0) {
                return [
                    'status' => 'PENDING',
                    'message' => "Tài khoản $email chưa kích hoạt. Vui lòng nhắc khách kích hoạt. Nếu khách cần, hỏi xem có muốn gửi lại link không."
                ];
            }

            return ['status' => 'ACTIVE', 'message' => "Tài khoản $email hợp lệ."];
        }

        if ($name === 'resend_activation_tool') {
            try {
                $this->userModel->resendActivation($args['email']);
                return ['result' => "Đã gửi lại email kích hoạt thành công."];
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        if ($name === 'check_voucher_tool') {
            $user = $this->userModel->findByEmail($args['email']);
            if (!$user) return ['error' => 'Email chưa đăng ký tài khoản trong hệ thống.'];
            
            $result = $this->voucherModel->checkVoucher($args['voucher_code'], $user['id'], (float)$args['total_amount']);
            
            if (isset($result['error'])) {
                return ['valid' => false, 'message' => $result['error']];
            }
            
            return [
                'valid' => true,
                'discount_amount' => $result['discount_amount'],
                'final_amount' => $result['final_amount'],
                'message' => "Áp dụng mã thành công! Giảm: " . number_format($result['discount_amount']) . "đ. Còn lại: " . number_format($result['final_amount']) . "đ"
            ];
        }

        if ($name === 'create_order_tool') {
            $user = $this->userModel->findByEmail($args['email']);
            if (!$user || $user['status'] !== 'active') {
                return ['error' => "Tài khoản chưa kích hoạt."];
            }

            try {
                $allProducts = $this->productModel->getAll(['limit' => 1000])['data'];
                $targetProduct = null;
                foreach ($allProducts as $p) {
                    if (stripos($p['name'], $args['product_name']) !== false) {
                        $targetProduct = $p;
                        break;
                    }
                }
                if (!$targetProduct) return ['error' => "Không tìm thấy SP '{$args['product_name']}'."];

                $customerInfo = [];
                $exclude = ['product_name', 'quantity', 'payment_method', 'voucher_code'];
                foreach ($args as $k => $v) {
                    if (!in_array($k, $exclude)) $customerInfo[$k] = $v;
                }

                $voucherId = null;
                if (!empty($args['voucher_code'])) {
                    $totalRaw = $targetProduct['price'] * $args['quantity'];
                    $vCheck = $this->voucherModel->checkVoucher($args['voucher_code'], $user['id'], $totalRaw);
                    if (isset($vCheck['voucher_id'])) {
                        $voucherId = $vCheck['voucher_id'];
                    }
                }

                $orderData = [
                    'user_id' => $user['id'],
                    'customer_info' => $customerInfo, 
                    'items' => [[
                        'product_id' => $targetProduct['id'],
                        'quantity' => (int)$args['quantity'],
                        'price_at_purchase' => (float)$targetProduct['price'], 
                        'product_name_at_purchase' => $targetProduct['name']
                    ]],
                    'voucher_id' => $voucherId
                ];

                $result = $this->orderModel->createOrderWithPayment($orderData, $args['payment_method']);
                
                $msg = "✅ **ĐƠN HÀNG #{$result['order_code']} THÀNH CÔNG!**\n";
                if (isset($result['checkout_url'])) {
                    $msg .= "- Link thanh toán: [Thanh toán ngay]({$result['checkout_url']})";
                } else {
                    $msg .= "- Chúng tôi sẽ liên hệ sớm.";
                }
                return ['result' => $msg];

            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        if ($name === 'lookup_order_tool') {
            $order = $this->orderModel->getOrderByCode($args['order_code']);
            if (!$order) return ['error' => "Không tìm thấy đơn #{$args['order_code']}"];
            return ['result' => "📦 Đơn #{$order['order_code']}: **{$order['status']}** - " . number_format($order['total_price']) . "đ"];
        }

        return ['error' => 'Function not found'];
    }

    public function chatWithAI(?string $sessionId, string $userMessage): array
    {
        if (!$sessionId) $sessionId = uniqid('sess_', true);
        $session = $this->chatSessionModel->getSession($sessionId);
        $history = $session['history'];
        $history[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $products = $this->productModel->getAll(['status' => 'active', 'limit' => 50]);
        $prodText = "";
        foreach ($products['data'] as $p) {
            $prodText .= "- {$p['name']} (" . number_format($p['price']) . "đ)\n";
        }

        $config = $this->orderModel->getPaymentRequirements();
        $requiredFields = $config['required_fields'];
        $paymentMethods = implode(', ', $config['payment_methods']);

        $stepsPrompt = "BƯỚC 1: Hỏi Sản phẩm & Số lượng.\n";
        $stepIndex = 2;

        if (in_array('email', $requiredFields)) {
            $stepsPrompt .= "BƯỚC $stepIndex: Hỏi Email & Tên -> GỌI `check_account_tool`. \n";
            $stepsPrompt .= "   - Nếu PENDING: Dừng lại, báo khách kích hoạt. Nếu khách yêu cầu gửi lại link -> GỌI `resend_activation_tool`.\n";
            $stepsPrompt .= "   - Nếu ACTIVE: Đi tiếp.\n";
            $stepIndex++;
        }

        foreach ($requiredFields as $field) {
            if ($field === 'email') continue;
            $stepsPrompt .= "BƯỚC $stepIndex: Hỏi thông tin: $field.\n";
            $stepIndex++;
        }

        $stepsPrompt .= "BƯỚC $stepIndex: Hỏi khách có Mã Giảm Giá không?\n";
        $stepsPrompt .= "   - Nếu có -> GỌI `check_voucher_tool` với tổng tiền tạm tính.\n";
        $stepsPrompt .= "   - Nếu hợp lệ -> Thông báo số tiền giảm.\n";
        $stepIndex++;

        $stepsPrompt .= "BƯỚC $stepIndex: Hỏi Phương thức thanh toán ($paymentMethods).\n";
        $stepIndex++;
        $stepsPrompt .= "BƯỚC $stepIndex: Tóm tắt & Xác nhận -> GỌI `create_order_tool`.\n";

        $systemPrompt = "
            Bạn là trợ lý bán hàng chuyên nghiệp của **3Do Shop**.
            
            THÔNG TIN SHOP:
            - Chuyên cung cấp: Key bản quyền (Windows, Office), Extension Premium, Source Code Website, Dịch vụ thiết kế Web & Automation.
            - Hotline: 0865 341 745 (Tấn Kim)
            - Email: letankim2003@gmail.com
            
            CHÍNH SÁCH:
            - Bảo hành: 1 đổi 1 nếu key lỗi trong thời hạn cam kết.
            - Hỗ trợ: Ultraviewer/Teamviewer cài đặt miễn phí.
            - Thanh toán: Chuyển khoản (có mã QR) hoặc COD (tùy sản phẩm).

            DANH SÁCH SẢN PHẨM:
            $prodText

            QUY TRÌNH BÁN HÀNG NGHIÊM NGẶT (TUÂN THỦ THỨ TỰ):
            $stepsPrompt

            QUY TẮC CỐT LÕI:
            1. **Hỏi từng câu một**: Không bao giờ hỏi dồn 2 thông tin cùng lúc (trừ Tên & Email ở bước đầu).
            2. **Kiên nhẫn**: Chờ khách trả lời xong mới qua bước kế.
            3. **Bắt buộc**: Phải có Email Active mới được tạo đơn.
            4. **Voucher**: Luôn kiểm tra voucher nếu khách cung cấp trước khi chốt đơn.
        ";

        $payload = [
            'contents' => $history,
            'tools' => [$this->getToolsDefinition()], 
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]]
        ];

        try {
            $response = $this->client->post("models/gemini-2.5-flash:generateContent?key={$this->apiKey}", ['json' => $payload]);
            $result = json_decode($response->getBody(), true);
            $candidate = $result['candidates'][0]['content'];
            
            $history[] = $candidate;

            if (isset($candidate['parts'][0]['functionCall'])) {
                $fc = $candidate['parts'][0]['functionCall'];
                $funcResult = $this->executeFunction($fc);
                
                $history[] = [
                    'role' => 'function',
                    'parts' => [['functionResponse' => ['name' => $fc['name'], 'response' => ['content' => $funcResult]]]]
                ];

                $payload['contents'] = $history;
                $response2 = $this->client->post("models/gemini-2.5-flash:generateContent?key={$this->apiKey}", ['json' => $payload]);
                $result2 = json_decode($response2->getBody(), true);
                $finalText = $result2['candidates'][0]['content'];
                
                $history[] = $finalText;
                $responseText = $finalText['parts'][0]['text'];
            } else {
                $responseText = $candidate['parts'][0]['text'];
            }

            $this->chatSessionModel->saveSession($sessionId, $history);

            return ['session_id' => $sessionId, 'message' => $responseText];

        } catch (\Exception $e) {
            return ['session_id' => $sessionId, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    public function getHistoryBySessionId(string $sessionId): array 
    {
        $session = $this->chatSessionModel->getSession($sessionId);
        return $session['history'] ?? [];
    }
}