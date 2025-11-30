<?php
namespace App\Models;

use Respect\Validation\Validator as v;
use App\Validation\ValidationRules as rule;
use DateTime;
use PDO;
 
class CommentModel extends BaseModel{
public function getAll(array $params): array
{
    $joins = [
        'LEFT JOIN users u ON comments.user_id = u.id'
    ];

    $extraWhere = " AND comments.parent_id IS NULL"; 

    if (!empty($params['post_id'])) {
        $extraWhere .= " AND comments.post_id = :post_id";
    }

    if (empty($params['status'])) {
    } else {
        $extraWhere .= " AND comments.status = :status";
    }

    $result = $this->getAllWithPaginationAndFilter(
        'comments',
        $params,
        $joins,
        $extraWhere
    );

    foreach ($result['data'] as &$comment) {
        $comment['user_name']   = $comment['username'] ?? null;
        $comment['user_avatar'] = $comment['avatar_url'] ?? null;

        $stmt = $this->pdo->prepare("
            SELECT 
                c.*, 
                u.username AS user_name,
                u.avatar_url AS user_avatar
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.parent_id = :parent_id
              AND c.status = 'approved'
            ORDER BY c.created_at ASC
        ");
        $stmt->execute(['parent_id' => $comment['id']]);
        $comment['children'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $result;
}

    public function createComment($data) {
    try {
            // $this->validate($data, [
            // 'post_id'   => rule::uuid(),
            // 'user_id'   => rule::uuid(),
            // 'content'   => rule::stringNotEmpty()->length(1, 65535), 
            // 'parent_id' => rule::optionalUuid(),                  
            // 'status'    => rule::status(['approved', 'pending', 'rejected', 'banned'])
            // ]);
            
            $data['id'] = $this->generateUUID();
            $this->create('comments', $data);
            return ['success' => 'Tạo bình luận thành công', 'id' => $data['id']];
        }catch (\Exception $e) {
            return ['error' => json_decode($e->getMessage(), true)];
        }
    }

    public function updateComment(string $commentId, array $data, bool $isAdmin = false): array
    {
        try {
            $this->validate($data, [
                'content' => v::stringType()->notEmpty()->length(1, 65535),
                'user_id' => rule::uuid(), 
            ]);

            $comment = $this->getById('comments', $commentId);
            if (!$comment) {
                return ['error' => 'Bình luận không tồn tại hoặc đã bị xóa.'];
            }


            if (!$isAdmin && $comment['user_id'] !== $data['user_id']) {
                return ['error' => 'Bạn không có quyền sửa bình luận này.'];
            }

            $hasReplies = $this->pdo->prepare("
                SELECT 1 FROM comments WHERE parent_id = :parent_id LIMIT 1
            ");

            $hasReplies->execute(['parent_id' => $commentId]);

            if ($hasReplies->fetchColumn()) {
                return ['error' => 'Không thể sửa bình luận đã có phản hồi.'];
            }

            $createdAt = new DateTime($comment['created_at']);
            $now       = new \DateTime();
            $interval  = $now->getTimestamp() - $createdAt->getTimestamp();

            if (!$isAdmin && $interval > 900) {
                return ['error' => 'Chỉ được chỉnh sửa bình luận trong vòng 15 phút sau khi đăng.'];
            }

            $updateData = [
                'content'     => trim($data['content']),
                'updated_at'  => date('Y-m-d H:i:s'), 
            ];

            $this->update('comments', $commentId, $updateData);

            return [
                'success' => 'Cập nhật bình luận thành công',
                'comment' => array_merge($comment, $updateData)
            ];

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if (json_decode($errorMsg, true)) {
                return ['error' => json_decode($errorMsg, true)];
            }

            return ['error' => 'Đã có lỗi xảy ra: ' . $e->getMessage()];
        }
    }


    public function updateStatus($id, $status) {
        $this->validate(['status' => $status], [
            'status' => v::in(['approved', 'pending', 'rejected', 'banned'])
        ], [
            'status' => 'Trạng thái không hợp lệ. Chỉ chấp nhận: approved, pending, rejected, banned'
        ]);
        $this->update('comments', $id, ['status' => $status]);
        return ['success' => 'Cập nhật trạng thái bình luận thành công'];
    }

    public function deleteComment($id) {
        $this->delete('comments', $id);
        return ['success' => 'Xóa bình luận thành công'];
    }
}
