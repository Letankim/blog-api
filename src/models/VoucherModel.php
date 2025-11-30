<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class VoucherModel extends BaseModel
{
    public function getAll(array $params = []): array
    {
        return $this->getAllWithPaginationAndFilter('vouchers', $params);
    }

    public function getByIdVoucher(string $id)
    {
        return $this->getById('vouchers', $id);
    }

    public function createVoucher(array $data): array
    {
        $this->validate($data, [
            'code'        => v::stringType()->length(1, 50)->notEmpty(),
            'discount'    => v::numeric()->between(0, 100),
            'expires_at'  => v::optional(v::date('Y-m-d H:i:s')),
            'max_uses'    => v::intVal()->min(1),
            'uses_count'  => v::optional(v::intVal()->min(0)),
            'is_public'   => v::boolVal(),
        ]);

        $data['id'] = $this->generateUUID();
        $this->create('vouchers', $data);

        return [
            'thành công' => 'Tạo voucher thành công',
            'id'         => $data['id'],
        ];
    }

    public function updateVoucher(string $id, array $data): array
    {
        $this->validate($data, [
            'discount'    => v::optional(v::numeric()->between(0, 100)),
            'expires_at'  => v::optional(v::date('Y-m-d H:i:s')),
            'max_uses'    => v::optional(v::intVal()->min(1)),
            'uses_count'  => v::optional(v::intVal()->min(0)),
            'is_public'   => v::optional(v::boolVal()),
        ]);

        $this->update('vouchers', $id, $data);
        return ['thành công' => 'Cập nhật voucher thành công'];
    }

    public function deleteVoucher(string $id): array
    {
        $this->delete('vouchers', $id);
        return ['thành công' => 'Xóa voucher thành công'];
    }

    public function checkVoucher(string $code, string $userId, float $amount): array
{
    $voucher = $this->getOne('vouchers', ['code' => $code]);

    if (!$voucher) {
        return ['error' => 'Mã voucher không tồn tại'];
    }

    if (!empty($voucher['expires_at']) && strtotime($voucher['expires_at']) < time()) {
        return ['error' => 'Voucher đã hết hạn'];
    }

    if (isset($voucher['is_public']) && !$voucher['is_public']) {
        return ['error' => 'Voucher này không được phép sử dụng'];
    }

    if (isset($voucher['uses_count'], $voucher['max_uses']) && $voucher['uses_count'] >= $voucher['max_uses']) {
        return ['error' => 'Voucher đã được sử dụng hết'];
    }

    if (isset($voucher['min_amount']) && $amount < $voucher['min_amount']) {
        return ['error' => "Đơn hàng phải có giá trị tối thiểu {$voucher['min_amount']} để áp dụng voucher này"];
    }

    $usage = $this->getOne('voucher_usage', [
        'voucher_id' => $voucher['id'],
        'user_id'    => $userId
    ]);

    if ($usage) {
        return ['error' => 'Bạn đã sử dụng voucher này rồi'];
    }

        $discountAmount = 0;

        if (isset($voucher['discount_type']) && $voucher['discount_type'] === 'percent') {
            $discountAmount = $amount * ($voucher['discount'] / 100);
        } else {
            $discountAmount = $voucher['discount']; 
        }

    $finalAmount = max($amount - $discountAmount, 0);

        return [
            'success'         => 'Voucher hợp lệ',
            'voucher_id'      => $voucher['id'],
            'discount_amount' => round($discountAmount, 2),
            'final_amount'    => round($finalAmount, 2),
            'voucher'         => $voucher
        ];
    }

}
