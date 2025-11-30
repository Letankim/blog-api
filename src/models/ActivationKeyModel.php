<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class ActivationKeyModel extends BaseModel {
   public function getAll(array $params): array
    {
        $result = $this->getAllWithPaginationAndFilter('activation_keys', $params);
        foreach ($result['data'] as &$row) {
            if (!empty($row['user_info'])) {
                $decoded = json_decode($row['user_info'], true);
                $row['user_info'] = $decoded ?: (object) [];
            } else {
                $row['user_info'] = null;
            }

            if (!empty($row['logger'])) {
                $decoded = json_decode($row['logger'], true);
                $row['logger'] = $decoded ?: (object) [];
            } else {
                $row['logger'] = null;
            }
        }

        return $result;
    }


public function getKeysByUserId(string $userId, array $params = []): array {
    try {
        $page     = max(1, (int)($params['page'] ?? 1));
        $pageSize = max(1, (int)($params['pageSize'] ?? 10));
        $offset   = ($page - 1) * $pageSize;

        $search   = $params['search'] ?? null;
        $status   = $params['status'] ?? null;

        $sortBy = in_array($params['sortBy'] ?? '', ['ak.key_value','ak.created_at','ak.status','ak.app_name'])
            ? $params['sortBy']
            : 'ak.created_at';

        $sortDir = strtoupper($params['sortDir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $where = " WHERE o.user_id = :user_id ";
        $binds = ['user_id' => $userId];

        if (!empty($search)) {
            $where .= " AND (ak.key_value LIKE :search OR ak.app_name LIKE :search2)";
            $binds['search'] = "%$search%";
               $binds['search2'] = "%$search%";
        }

        if ($status !== null && $status !== '') {
            $where .= " AND ak.status = :status";
            $binds['status'] = $status;
        }

        $sql = "SELECT ak.id, ak.key_value, ak.status, ak.buyer, ak.app_name,
                       ak.created_at, ak.used_at, ak.expiration_time, ak.type, ak.usage_count, ak.active,
                       o.id AS order_id,
                       u.username, u.email
                FROM activation_keys ak
                JOIN orders o ON ak.order_id = o.id
                JOIN users u ON o.user_id = u.id
                $where
                ORDER BY $sortBy $sortDir
                LIMIT $pageSize OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($binds as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $countSql = "SELECT COUNT(*)
                     FROM activation_keys ak
                     JOIN orders o ON ak.order_id = o.id
                     $where";

        $countStmt = $this->pdo->prepare($countSql);

        foreach ($binds as $k => $v) {
            $countStmt->bindValue(':' . $k, $v);
        }

        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize),
            'success' => 'Lấy danh sách key thành công'
        ];

    } catch (\PDOException $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    } catch (\Exception $e) {
        return ['error' => 'Unexpected error: ' . $e->getMessage()];
    }
}

public function resetKeyByUser(string $userId, string $keyId, string $reason = 'Reset để sử dụng'): array
{
    try {

        $stmt = $this->pdo->prepare("
            SELECT ak.id
            FROM activation_keys ak
            JOIN orders o ON ak.order_id = o.id
            WHERE ak.id = :key_id
            AND o.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([
            'key_id' => $keyId,
            'user_id' => $userId
        ]);

        $key = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$key) {
            return ['error' => 'Key này không tồn tại hoặc không thuộc user được chỉ định'];
        }

        $update = $this->pdo->prepare("
            UPDATE activation_keys
            SET 
                status = 'unused',
                used_at = NULL,
                number_of_resets = number_of_resets + 1,
                reason_for_reset = :reason
            WHERE id = :id
        ");

        $update->execute([
            'reason' => $reason,
            'id'     => $keyId
        ]);

        return [
            'success' => 'Reset key thành công',
            'key_id' => $keyId,
            'user_id' => $userId
        ];

    } catch (\PDOException $e) {
        return [
            'error' => 'Database error: ' . $e->getMessage()
        ];
    } catch (\Exception $e) {
        return [
            'error' => 'Unexpected error: ' . $e->getMessage()
        ];
    }
}


    public function getByIdActivationKey($id) {
       $key = parent::getById('activation_keys', $id);
        if (!$key) return null;

        $key['user_info'] = !empty($key['user_info']) ? json_decode($key['user_info'], true) : null;
        $key['logger'] = !empty($key['logger']) ? json_decode($key['logger'], true) : null;

        return $key;
    }


    public function getKeysByOrderId(string $orderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ak.id,
                ak.key_value,
                ak.status,
                ak.buyer,
                ak.app_name,
                ak.created_at,
                ak.used_at,
                ak.expiration_time,
                ak.type,
                ak.usage_count,
                ak.active
            FROM activation_keys ak
            WHERE ak.order_id = :order_id
            ORDER BY ak.created_at DESC
        ");
        $stmt->execute(['order_id' => $orderId]);
        $keys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'data' => $keys,
            'count' => count($keys),
            'success' => 'Lấy danh sách key theo đơn hàng thành công'
        ];
    }

    public function getKeysByOrderAndUser(string $orderId, int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ak.id,
                ak.key_value,
                ak.status,
                ak.buyer,
                ak.app_name,
                ak.created_at,
                ak.used_at,
                ak.expiration_time,
                ak.type,
                ak.usage_count,
                ak.active,
                o.id AS order_id,
                o.total_money,
                o.payment_status,
                u.id AS user_id,
                u.username,
                u.email
            FROM activation_keys ak
            JOIN orders o ON ak.order_id = o.id
            JOIN users u ON o.user_id = u.id
            WHERE ak.order_id = :order_id
            AND u.id = :user_id
            ORDER BY ak.created_at DESC
        ");

        $stmt->execute([
            'order_id' => $orderId,
            'user_id'  => $userId
        ]);

        $keys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'data' => $keys,
            'count' => count($keys),
            'success' => 'Lấy danh sách key theo đơn hàng & user thành công'
        ];
    }



    public function createKey($data) {
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
            'used_at'          => v::optional(v::date('Y-m-d H:i:s')),
            'start_time'       => v::optional(v::date('Y-m-d H:i:s')),
            'expiration_time'  => v::optional(v::date('Y-m-d H:i:s')),
            'order_id'         => v::optional(v::uuid())
        ]);

        $data['id'] = $this->generateUUID();
        $this->create('activation_keys', $data);
        return ['success' => 'Tạo key kích hoạt thành công', 'id' => $data['id']];
    }

    public function createKeysForOrder(array $data): array
{
    $this->validate($data, [
        'order_id'   => v::uuid()->notEmpty(),
        'buyer'      => v::stringType()->length(1, 255)->notEmpty(),
        'quantities' => v::arrayType()->notEmpty()
    ]);

    $createdKeys = [];

    foreach ($data['quantities'] as $item) {
        $appName = $item['app_name'] ?? 'unknown';
        $quantity = (int)($item['quantity'] ?? 1);

        for ($i = 0; $i < $quantity; $i++) {
           $keyValue = strtoupper(bin2hex(random_bytes(16)));

            $keyData = [
                'id'              => $this->generateUUID(),
                'key_value'       => $keyValue,
                'status'          => 'unused',
                'buyer'           => $data['buyer'],
                'app_name'        => $appName,
                'order_id'        => $data['order_id'],
                'active'          => 1,
                'usage_count'     => 0,
                'type'            => 'bán lẻ',
                'number_of_resets'=> 0,
                'created_at'      => date('Y-m-d H:i:s'),
            ];

            $this->create('activation_keys', $keyData);
            $createdKeys[] = $keyData;
        }
    }

    return [
        'success' => 'Tạo mã kích hoạt cho đơn hàng thành công',
        'count' => count($createdKeys),
        'keys' => $createdKeys
    ];
}


    public function createMultipleKeysByAdmin(array $data): array
    {
        $this->validate($data, [
            'buyer' => v::stringType()->length(1, 255)->notEmpty(),
            'keys'  => v::arrayType()->notEmpty() 
        ]);

        $createdKeys = [];

        foreach ($data['keys'] as $item) {
            $keyValue = trim($item['key_value'] ?? '');
            $appName  = trim($item['app_name'] ?? '');

            if ($keyValue === '' || $appName === '') {
                continue; 
            }

            $keyData = [
                'id'              => $this->generateUUID(),
                'key_value'       => strtoupper($keyValue),
                'status'          => 'unused',
                'buyer'           => $data['buyer'],
                'app_name'        => $appName,
                'order_id'        => null,
                'active'          => 1,
                'usage_count'     => 0,
                'type'            => 'admin',
                'number_of_resets'=> 0,
                'created_at'      => date('Y-m-d H:i:s'),
            ];

            $this->create('activation_keys', $keyData);
            $createdKeys[] = $keyData;
        }

        if (empty($createdKeys)) {
            return ['error' => 'Không có key hợp lệ nào được tạo'];
        }

        return [
            'success' => 'Tạo thành công ' . count($createdKeys) . ' key cho nhiều ứng dụng',
            'count' => count($createdKeys),
            'keys' => $createdKeys
        ];
    }

    public function updateKey($id, $data) {
        $this->validate($data, [
            'status'      => v::optional(v::in(['used', 'unused'])),
            'usage_count' => v::optional(v::intVal()->min(0)),
            'used_at'     => v::optional(v::date('Y-m-d H:i:s'))
        ]);
        $this->update('activation_keys', $id, $data);
        return ['success' => 'Cập nhật key thành công'];
    }

    public function updateKeyActiveStatus(string $id, int $active): array
{
    try {
        if (!in_array($active, [0, 1])) {
            throw new \InvalidArgumentException('Giá trị active chỉ có thể là 0 hoặc 1');
        }

        $stmt = $this->pdo->prepare("UPDATE activation_keys SET active = :active WHERE id = :id");
        $stmt->execute([
            'active' => $active,
            'id' => $id
        ]);
        if ($stmt->rowCount() === 0) {
            return ['error' => 'Không tìm thấy key cần cập nhật'];
        }

        return ['success' => 'Cập nhật trạng thái key thành công', 'id' => $id, 'active' => $active];
    } catch (\Exception $e) {
        error_log("updateKeyActiveStatus() ERROR: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}


    public function deleteKey($id) {
        $this->delete('activation_keys', $id);
        return ['success' => 'Xóa key thành công'];
    }
}
