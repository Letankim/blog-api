<?php
namespace App\Models;

use PDO;
use Respect\Validation\Validator as v;

class PostLikeModel extends BaseModel {
    public function like($data) {
        $this->validate($data, [
            'post_id' => v::uuid(),
            'user_id' => v::uuid()
        ]);

        $check = $this->pdo->prepare("SELECT id FROM post_likes WHERE post_id = :post_id AND user_id = :user_id");
        $check->execute($data);
        if ($check->fetch()) {
            throw new \Exception('Lỗi: Bạn đã like bài viết này rồi');
        }

        $this->create('post_likes', $data);
        $this->pdo->prepare("UPDATE posts SET like_count = like_count + 1 WHERE id = :post_id")->execute(['post_id' => $data['post_id']]);
        return ['success' => 'Like bài viết thành công'];
    }

    public function unlike($postId, $userId) {
        $sql = "DELETE FROM post_likes WHERE post_id = :post_id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['post_id' => $postId, 'user_id' => $userId]);
        $this->pdo->prepare("UPDATE posts SET like_count = like_count - 1 WHERE id = :post_id")->execute(['post_id' => $postId]);
        return ['success' => 'Unlike bài viết thành công'];
    }

    public function getLikesByPost(string $postId): array
{
    $stmt = $this->pdo->prepare("
        SELECT 
            u.id AS user_id,
            u.username,
            u.avatar_url as avatar,
            pl.created_at AS liked_at
        FROM post_likes pl
        JOIN users u ON pl.user_id = u.id
        WHERE pl.post_id = :post_id
        ORDER BY pl.created_at DESC
    ");
    $stmt->execute(['post_id' => $postId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $likeCount = count($users);

    return [
        'count' => $likeCount,
        'users' => $users,
    ];
}

}