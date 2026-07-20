<?php
namespace App\Models;

use PDO;
use Respect\Validation\Validator as v;

class PostModel extends BaseModel
{
public function getAll(array $params): array
{
    $joins = [
        'LEFT JOIN post_images pi ON posts.id = pi.post_id',
        'LEFT JOIN post_tags pt ON posts.id = pt.post_id',
        'LEFT JOIN tags t ON pt.tag_id = t.id',
        'LEFT JOIN post_categories pc ON posts.category_id = pc.id'
    ];

    $extraWhere = '';
    if (!empty($params['tag'])) {
        $tags = is_array($params['tag']) ? $params['tag'] : explode(',', $params['tag']);
        $tags = array_filter(array_map('trim', $tags)); 

        if (!empty($tags)) {
            $placeholders = [];
            foreach ($tags as $i => $tag) {
                $placeholder = ":tag_$i";
                $placeholders[] = $placeholder;
                $params["tag_$i"] = $tag; 
            }
            $extraWhere .= " AND t.id IN (" . implode(', ', $placeholders) . ")";
        }
    }
    if (!empty($params['category'])) {
        $extraWhere .= " AND posts.category_id = :category";
    }

    $result = $this->getAllWithPaginationAndFilter('posts', $params, $joins, $extraWhere, 'posts.id');

    $grouped = [];
    foreach ($result['data'] as $row) {
        $id = $row['id'] ?? null;
        if (!$id) continue;

        if (!isset($grouped[$id])) {
            $grouped[$id] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'category_id' => $row['category_id'],
                'title' => $row['title'],
                'content' => $row['content'],
                'short_description' => $row['short_description'] ?? null,
                'view_count' => (int)$row['view_count'],
                'like_count' => (int)$row['like_count'],
                'seo_title' => $row['seo_title'] ?? null,
                'seo_description' => $row['seo_description'] ?? null,
                'seo_keywords' => $row['seo_keywords'] ?? null,
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'images' => [],
                'tags' => [],
                'category' => !empty($row['category_id']) ? [
                    'id' => $row['category_id'],
                    'name' => $row['pc_name'] ?? null,
                    'slug' => $row['pc_slug'] ?? null
                ] : null
            ];
        }

        if (!empty($row['image_url'])) {
            $grouped[$id]['images'][] = [
                'image_url' => $row['image_url'],
                'alt_text' => $row['alt_text'] ?? null,
                'is_primary' => (bool)$row['is_primary']
            ];
        }

        $stmt = $this->pdo->prepare("
            SELECT t.name 
            FROM post_tags pt
            JOIN tags t ON pt.tag_id = t.id
            WHERE pt.post_id = :post_id
        ");
        $stmt->execute(['post_id' => $id]);
        $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $grouped[$id]['tags'] = $tags;
        }

    $result['data'] = array_values($grouped);
    return $result;
}
    public function getByIdPost(string $id): ?array
    {
        $post = $this->getById('posts', $id);
        if (!$post) return null;

        if ($post['category_id']) {
            $stmt = $this->pdo->prepare("SELECT id, name, slug FROM post_categories WHERE id = ?");
            $stmt->execute([$post['category_id']]);
            $post['category'] = $stmt->fetch();
        } else {
            $post['category'] = null;
        }

        $stmt = $this->pdo->prepare("SELECT image_url, alt_text, is_primary FROM post_images WHERE post_id = ?");
        $stmt->execute([$id]);
        $post['images'] = $stmt->fetchAll();

        $stmt = $this->pdo->prepare("SELECT t.name FROM post_tags pt JOIN tags t ON pt.tag_id = t.id WHERE pt.post_id = ?");
        $stmt->execute([$id]);
        $post['tags'] = array_column($stmt->fetchAll(), 'name');

        return $post;
    }


    public function getByIdPostActive(string $id): ?array
    {
        $post = $this->getById('posts', $id, "published");
        if (!$post) return null;

    if ($post['category_id']) {
        $stmt = $this->pdo->prepare("
            SELECT id, name, slug 
            FROM post_categories 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$post['category_id']]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            return null;
        }

        $post['category'] = $category;
        } else {
            return null; 
        }

        $stmt = $this->pdo->prepare("
            SELECT image_url, alt_text, is_primary 
            FROM post_images 
            WHERE post_id = ?
        ");
        $stmt->execute([$id]);
        $post['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("
            SELECT t.name 
            FROM post_tags pt 
            JOIN tags t ON pt.tag_id = t.id 
            WHERE pt.post_id = ?
        ");
        $stmt->execute([$id]);
        $post['tags'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

        return $post;
    }

    public function createPost(array $data): array
    {
        $this->beginTransaction();

        try {
            $this->validate($data, [
                'user_id' => v::uuid(),
                'title' => v::stringType()->length(1, 255)->notEmpty(),
                'content' => v::stringType()->notEmpty(),
                'category_id' => v::optional(v::stringType()->length(36)),
                'short_description' => v::optional(v::stringType()),
                'seo_title' => v::optional(v::stringType()->length(1, 255)),
                'seo_description' => v::optional(v::stringType()),
                'seo_keywords' => v::optional(v::stringType()),
                'status' => v::in(['draft', 'published', 'archived']),
                'images' => v::optional(v::arrayVal()),
                'tags' => v::optional(v::arrayVal())
            ]);

            $data['id'] = $this->generateUUID();

            $cleanData = $data;

            unset($cleanData['images']);
            unset($cleanData['tags']);


            $this->create('posts', $cleanData);

            if (!empty($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $img) {
                    $this->validate($img, [
                        'image_url' => v::url()->notEmpty(),
                        'alt_text' => v::optional(v::stringType()),
                        'is_primary' => v::boolVal()
                    ]);
                    $img['id'] =  $this->generateUUID();
                    $img['post_id'] = $data['id'];
                    $this->create('post_images', $img);
                }
            }

            if (!empty($data['tags']) && is_array($data['tags'])) {
                foreach ($data['tags'] as $tagName) {
                    $this->validate($tagName, ['tagName' => v::stringType()->notEmpty()]);  // Validate tag name
                    $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tagName]);
                    $tag = $stmt->fetch(PDO::FETCH_ASSOC);

                    $tagId = $tag['id'] ?? $this->generateUUID();
                    if (!$tag) {
                        $this->create('tags', ['id' => $tagId, 'name' => $tagName]);
                    }

                        $tagIdPost = $this->generateUUID();

                    $this->create('post_tags', ['post_id' => $data['id'], 'tag_id' => $tagId, 'id'=> $tagIdPost]);
                }
            }

            $this->commit();

            return ['success' => true, 'message' => 'Tạo bài viết thành công', 'id' => $data['id']];

        } catch (\Exception $e) {
            $this->rollBack();
            error_log("Create post failed: " . $e->getMessage());
            throw new \Exception("Lỗi tạo bài viết: " . $e->getMessage());
        }
    }

    public function updatePost(string $id, array $data): array
    {
        $this->beginTransaction();

        try {
            $existingPost = $this->getByIdPost($id);  
            if (!$existingPost) {
                throw new \Exception("Bài viết không tồn tại: $id");
            }

            $this->validate($data, [
                'title' => v::optional(v::stringType()->length(1, 255)),
                'content' => v::optional(v::stringType()),
                'category_id' => v::optional(v::stringType()->length(36)),
                'short_description' => v::optional(v::stringType()),
                'seo_title' => v::optional(v::stringType()->length(1, 255)),
                'seo_description' => v::optional(v::stringType()),
                'seo_keywords' => v::optional(v::stringType()),
                'status' => v::optional(v::in(['draft', 'published', 'archived'])),
                'images' => v::optional(v::arrayVal()),
                'tags' => v::optional(v::arrayVal())
            ]);

            $cleanData = $data;

            unset($cleanData['images']);
            unset($cleanData['tags']);


            $this->update('posts', $id, $cleanData);

            if (isset($data['images']) && is_array($data['images'])) {
                $this->pdo->prepare("DELETE FROM post_images WHERE post_id = ?")
                    ->execute([$existingPost['id']]);

                foreach ($data['images'] as $img) {
                    $img['post_id'] = $id;
                    $img['id'] = $this->generateUUID();
                    $this->create('post_images', $img);
                }
            }

            if (isset($data['tags']) && is_array($data['tags'])) {
                $this->pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")
                    ->execute([$existingPost['id']]);

                foreach ($data['tags'] as $tagName) {
                    $tagName = trim($tagName);
                    if ($tagName === '') {
                        continue; 
                    }
                    $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tagName]);
                    $tag = $stmt->fetch(PDO::FETCH_ASSOC);

                    $tagId = $tag['id'] ?? $this->generateUUID();
                    if (!$tag) {
                        $this->create('tags', ['id' => $tagId, 'name' => $tagName]);
                    }

                    $tagIdPost = $this->generateUUID();
                    $this->create('post_tags', ['post_id' => $id, 'tag_id' => $tagId, 'id'=> $tagIdPost]);
                }
            }

            $this->commit();

            return ['success' => true, 'message' => 'Cập nhật bài viết thành công'];

        } catch (\Exception $e) {
            $this->rollBack();
            throw new \Exception("Lỗi cập nhật bài viết: " . $e->getMessage());
        }
    }

    public function deletePost(string $id): array
    {
        $this->beginTransaction();

        try {
            $existingPost = $this->getByIdPost($id); 
            if (!$existingPost) {
                throw new \Exception("Bài viết không tồn tại: $id");
            }

            $this->pdo->prepare("DELETE FROM post_images WHERE post_id = ?")
                ->execute([$id]);

            $this->pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")
                ->execute([$id]);

            $this->delete('posts', $id);

            $this->commit();

            return ['success' => true, 'message' => 'Xóa bài viết thành công'];

        } catch (\Exception $e) {
            $this->rollBack();
            error_log("Delete post failed: " . $e->getMessage());
            throw new \Exception("Lỗi xóa bài viết: " . $e->getMessage());
        }
    }
}