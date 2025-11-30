<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class PostCategoryModel extends BaseModel
{
    public function getAll(array $params = []): array
    {
        $joins = [
        ];

        $extraWhere = '';

        if (empty($params['include_inactive'])) {
            $extraWhere .= " AND post_categories.status = 'active'";
        }
        if (!empty($params['status'])) {
            $extraWhere .= " AND post_categories.status = :status";
        }

        return $this->getAllWithPaginationAndFilter(
            table: 'post_categories',
            params: $params,
            joins: $joins,
            extraWhere: $extraWhere,
            groupBy: 'post_categories.id' 
        );
    }
    public function getByIdPostCategory($id)
    {
        return $this->getById('post_categories', $id);
    }

    public function createPostCategory($data)
    {
        $this->validate($data, [
            'name' => v::stringType()->length(1, 255)->notEmpty(),
            'slug' => v::slug()->notEmpty(),
            'description' => v::optional(v::stringType()),
            'parent_id' => v::optional(v::stringType()->length(36)),
            'image_url' => v::optional(v::url()),
            'seo_title' => v::optional(v::stringType()->length(1, 255)),
            'seo_description' => v::optional(v::stringType()),
            'seo_keywords' => v::optional(v::stringType()),
            'display_order' => v::optional(v::intVal()->min(0)),
            'status' => v::optional(v::in(['active', 'inactive']))
        ]);

        $data['id'] = $this->generateUUID();
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['status'] = $data['status'] ?? 'active';

        $stmt = $this->pdo->prepare("SELECT id FROM post_categories WHERE slug = ?");
        $stmt->execute([$data['slug']]);
        if ($stmt->fetch()) {
            throw new \Exception('Slug đã tồn tại');
        }

        $this->create('post_categories', $data);
        return ['success' => 'Tạo danh mục bài viết thành công', 'id' => $data['id']];
    }

    public function updatePostCategory($id, $data)
    {
        $this->validate($data, [
            'name' => v::optional(v::stringType()->length(1, 255)),
            'slug' => v::optional(v::slug()),
            'parent_id' => v::optional(v::stringType()->length(36)),
            'status' => v::optional(v::in(['active', 'inactive'])),
            'display_order' => v::optional(v::intVal()->min(0))
        ]);

        if (!empty($data['slug'])) {
            $stmt = $this->pdo->prepare("SELECT id FROM post_categories WHERE slug = ? AND id != ?");
            $stmt->execute([$data['slug'], $id]);
            if ($stmt->fetch()) {
                throw new \Exception('Slug đã tồn tại');
            }
        }

        $this->update('post_categories', $id, $data);
        return ['success' => 'Cập nhật danh mục thành công'];
    }

    public function deletePostCategory($id)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM posts WHERE category_id = ? LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            throw new \Exception('Không thể xóa danh mục đang có bài viết');
        }

        $this->delete('post_categories', $id);
        return ['success' => 'Xóa danh mục thành công'];
    }
}