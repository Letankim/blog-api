<?php
namespace App\Models;

use Respect\Validation\Validator as v;
use PDO;

class ReviewReplyModel extends BaseModel
{
    public function getAll(array $params = []): array
    {
        $joins = ['LEFT JOIN users u ON review_replies.user_id = u.id'];
        $extraWhere = !empty($params['review_id']) ? " AND review_replies.review_id = :review_id" : '';

        $result = $this->getAllWithPaginationAndFilter('review_replies', $params, $joins, $extraWhere);

        foreach ($result['data'] as &$row) {
            $row['user_name'] = $row['username'] ?? null;
            unset($row['username']);
        }

        return $result;
    }

    public function createReply(array $data): array
    {
        $this->validate($data, [
            'review_id' => v::uuid(),
            'content'   => v::stringType()->notEmpty(),
            'status'    => v::in(['visible', 'hidden']),
        ]);

        $data['id'] = $this->generateUUID();
        $this->create('review_replies', $data);

        return [
            'success' => 'Tạo trả lời đánh giá thành công',
            'id'         => $data['id'],
        ];
    }

    public function updateReplyStatus(string $id, string $status): array
    {
        $this->validate(['status' => $status], [
            'status' => v::in(['visible', 'hidden']),
        ]);

        $this->update('review_replies', $id, ['status' => $status]);
        return ['success' => 'Cập nhật trạng thái trả lời thành công'];
    }

    public function deleteReply(string $id): array
    {
        $this->delete('review_replies', $id);
        return ['success' => 'Xóa trả lời đánh giá thành công'];
    }
}
