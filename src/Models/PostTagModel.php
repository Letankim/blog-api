<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class PostTagModel extends BaseModel {
    public function addTagToPost($data) {
        $this->validate($data, [
            'post_id' => v::uuid(),
            'tag_id' => v::uuid()
        ]);
        $fields = array_filter($data);
        $id = $this->create('post_tags', $data, $fields);
        return ['success' => 'Thêm tag vào bài viết thành công'];
    }

    public function removeTagFromPost($postId, $tagId) {
        $this->validate(['post_id' => $postId, 'tag_id' => $tagId], [
            'post_id' => v::uuid(),
            'tag_id' => v::uuid()
        ]);
        $stmt = $this->pdo->prepare("DELETE FROM post_tags WHERE post_id = ? AND tag_id = ?");
        $stmt->execute([$postId, $tagId]);
        return ['success' => 'Xóa tag khỏi bài viết thành công'];
    }
}