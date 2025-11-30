<?php
namespace App\Services;

use PayOS\PayOS;
use Exception;
use Psr\Log\LoggerInterface;

class PayOSService
{
    private PayOS $payOS;
private ?LoggerInterface $logger;  
    public function __construct(string $clientId, string $apiKey, string $checksumKey, ?LoggerInterface $logger = null)
    {
        $this->payOS = new PayOS($clientId, $apiKey, $checksumKey);
        $this->logger = $logger;
    }

    public function createPaymentLink(
        int $amount,
        array $items,
        int $orderCode,
        string $description,
        string $returnUrl,
        string $cancelUrl
    ): string {
        $this->log("Tạo link thanh toán cho orderCode: $orderCode");

        $items = array_map(function ($item) {
            return [
                'name'     => $item['name'] ?? 'Sản phẩm',
                'quantity' => (int)($item['quantity'] ?? 1),
                'price'    => (int)($item['price'] ?? 0)
            ];
        }, $items);

        $shortDesc = mb_strtoupper(mb_substr($description, 0, 23, 'UTF-8'), 'UTF-8');

        $data = [
            'orderCode'    => $orderCode,
            'amount'       => $amount,
            'description'  => $shortDesc,
            'items'        => $items,
            'returnUrl'    => $returnUrl,
            'cancelUrl'    => $cancelUrl
        ];

        try {
            $response = $this->payOS->createPaymentLink($data);
            $url = $response['checkoutUrl'] ?? null;

            if ($url) {
                $this->log("Tạo link thành công: $url");
                return $url;
            }

            $this->log("PayOS trả về không có checkoutUrl", $response);
            return "ERROR_NO_CHECKOUT_URL";
        } catch (Exception $e) {
            $this->log("Lỗi tạo link thanh toán: " . $e->getMessage());
            return "ERROR_PAYMENT_LINK: " . $e->getMessage();
        }
    }

    public function verifyPayment(int $orderCode): ?array
    {
        $this->log("Kiểm tra thanh toán cho orderCode: $orderCode");

        try {
            $response = $this->payOS->getPaymentLinkInformation($orderCode);

            if (!isset($response['status'])) {
                $this->log("PayOS không trả status", $response);
                return null;
            }

            $this->log("Trạng thái PayOS: " . $response['status']);
            return $response;
        } catch (Exception $e) {
            $this->log("Lỗi kiểm tra thanh toán: " . $e->getMessage());
            return null;
        }
    }


    public function cancelPayment(int $orderCode, string $reason = "Hủy bởi người dùng"): bool
    {
        $this->log("Hủy thanh toán orderCode: $orderCode, lý do: $reason");

        try {
            $this->payOS->cancelPaymentLink($orderCode, $reason);
            $this->log("Hủy thành công");
            return true;
        } catch (Exception $e) {
            $this->log("Lỗi hủy: " . $e->getMessage());
            return false;
        }
    }
    private function log(string $message, ?array $context = null): void
    {
        if ($this->logger) {
            $this->logger->info($message, $context ?? []);
        }
    }
}