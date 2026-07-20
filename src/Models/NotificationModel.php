<?php
namespace App\Models;

use Respect\Validation\Validator as v;
use App\Validation\ValidationRules as rule;

class NotificationModel extends BaseModel {
   public function getAllForUser(string $userId, array $params): array
    {
        $extraWhere = " AND user_id = :user_id";
        $params['user_id'] = $userId;

         if (!empty($params['status']) && in_array($params['status'], ['read', 'unread'])) {
        if ($params['status'] === 'read') {
            $extraWhere .= " AND is_read = 1";
        } else {
            $extraWhere .= " AND is_read = 0";
        }
    }

        return $this->getAllWithPaginationAndFilter('notifications', $params, [], $extraWhere);
    }

    public function createNotification($data): string {
          error_log("🔔 Tạo thông báo cho user_id: " . ($data['user_id'] ?? 'null'));
        $this->validate($data, [
           'user_id'     => v::notEmpty(),  
        'title'       => v::stringType()->notEmpty()->length(1, 255),
        'message'     => v::stringType()->notEmpty(),
        'type'        => v::in(['order', 'comment', 'review', 'system', 'promotion', 'key']),
        'related_id'  => v::optional(v::uuid()),
        'is_read'     => v::optional(v::in([0, 1]))
    ], [
        'user_id'     => 'ID người dùng không hợp lệ',
        'title'       => 'Tiêu đề bắt buộc và tối đa 255 ký tự',
        'message'     => 'Nội dung thông báo không được để trống',
        'type'        => 'Loại thông báo không hợp lệ',
        'related_id'  => 'ID liên quan không hợp lệ',
        'is_read'     => 'Trạng thái đọc chỉ được là 0 hoặc 1'
    ]);


        $data['id'] = $this->generateUUID();
        $data['is_read'] = $data['is_read'] ?? 0;
        parent::create('notifications', $data);

        return $data['id'];
    }

    public function markRead($id): string {
        $this->update('notifications', $id, ['is_read' => 1]);
        return 'Đánh dấu đã đọc';
    }

    public function markUnread($id): string {
        $this->update('notifications', $id, ['is_read' => 0]);
        return 'Đánh dấu chưa đọc';
    }

    public function deleteNotification($id): string {
        $this->delete('notifications', $id);
        return 'Xóa thông báo thành công';
    }
}
