<?php
namespace App\Models;

use PDO;
use Respect\Validation\Validator as v;

class BagrModel extends BaseModel
{
    public function getAll(array $params): array
    {
        $joins = [
            'LEFT JOIN users u ON bagr.user_id = u.id'
        ];

        $extraWhere = '';

        $result = $this->getAllWithPaginationAndFilter('bagr', $params, $joins, $extraWhere, 'bagr.id');

        $grouped = [];
        foreach ($result['data'] as $row) {
            $id = $row['id'] ?? null;
            if (!$id) continue;

            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'user_name' => $row['u_name'] ?? null, 
                    'title' => $row['title'],
                    'full_name' => $row['full_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'] ?? null,
                    'message' => $row['message'],
                    'created_at' => $row['created_at']
                ];
            }
        }

        $result['data'] = array_values($grouped);
        return $result;
    }

    public function getByIdBagr(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT bagr.*, u.name as user_name 
            FROM bagr 
            LEFT JOIN users u ON bagr.user_id = u.id 
            WHERE bagr.id = ?
        ");
        $stmt->execute([$id]);
        $bagr = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bagr) return null;

        return $bagr;
    }

    public function createBagr(array $data): array
    {
        $this->beginTransaction();

        try {
            $this->validate($data, [
                'user_id' => v::optional(v::stringType()->length(36)), 
                'title' => v::stringType()->length(1, 200)->notEmpty(),
                'full_name' => v::stringType()->length(1, 100)->notEmpty(),
                'email' => v::email()->notEmpty(),
                'phone' => v::optional(v::stringType()->length(1, 20)),
                'message' => v::stringType()->notEmpty()
            ]);

            $cleanData = $data;
            unset($cleanData['user_id']); 

            $this->create('bagr', $cleanData);

            $lastId = $this->pdo->lastInsertId();
            
            if (!empty($data['user_id'])) {
                $stmt = $this->pdo->prepare("UPDATE bagr SET user_id = ? WHERE id = ?");
                $stmt->execute([$data['user_id'], $lastId]);
            }

            $this->commit();

            register_shutdown_function(function() use ($lastId, $data) {
            try {
                $notify = new \App\Services\NotificationService();
                $notify->sendToAdminChannel(
                    \App\Enums\NotificationType::ADMIN_NEW_CONTACT->value,
                    [
                        'title' => 'Liên hệ mới!',
                        'body'  => "Có liên hệ mới từ {$data['full_name']} ({$data['email']})",
                        'data'  => [
                            'bagrId' => $lastId,
                            'full_name' => $data['full_name'],
                            'email' => $data['email'],
                            'phone' => $data['phone'] ?? null,
                            'message' => $data['message']
                        ]
                    ]
                );
            } catch (\Exception $e) {
                error_log("Notify admin failed: " . $e->getMessage());
            }
        });

            return ['success' => true, 'message' => 'Tạo liên hệ thành công', 'id' => (int)$lastId];

        } catch (\Exception $e) {
            $this->rollBack();
            error_log("Create bagr failed: " . $e->getMessage());
            throw new \Exception("Lỗi tạo liên hệ: " . $e->getMessage());
        }
    }

    public function deleteBagr(int $id): array
    {
        $this->beginTransaction();

        try {
            $existingBagr = $this->getByIdBagr($id);
            if (!$existingBagr) {
                throw new \Exception("Liên hệ không tồn tại: $id");
            }

            $this->delete('bagr', $id);

            $this->commit();

            return ['success' => true, 'message' => 'Xóa liên hệ thành công'];

        } catch (\Exception $e) {
            $this->rollBack();
            error_log("Delete bagr failed: " . $e->getMessage());
            throw new \Exception("Lỗi xóa liên hệ: " . $e->getMessage());
        }
    }
}