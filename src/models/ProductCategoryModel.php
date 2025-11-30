<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class ProductCategoryModel extends BaseModel
{
    public function getAllProductCategories(array $params = []): array
    {
         $joins = [
        ];

        $extraWhere = '';

        if (empty($params['include_inactive'])) {
            $extraWhere .= " AND product_categories.status = 'active'";
        }
        if (!empty($params['status'])) {
            $extraWhere .= " AND product_categories.status = :status";
        }

         return $this->getAllWithPaginationAndFilter(
            table: 'product_categories',
            params: $params,
            joins: $joins,
            extraWhere: $extraWhere,
            groupBy: 'product_categories.id' 
        );
    
    }

    public function getProductCategoryById(string $id): ?array
    {
        return $this->getById('product_categories', $id);
    }

    public function createProductCategory(array $data): array
    {
        $this->validate($data, [
            'name' => v::stringType()->length(1, 255)->notEmpty(),
            'slug' => v::slug()->notEmpty(),
            'description' => v::optional(v::stringType()),
            'parent_id' => v::optional(v::stringType()->length(36)),
            'image_url' => v::optional(v::url()),
            'seo_title' => v::optional(v::stringType()),
            'display_order' => v::optional(v::intVal()->min(0)),
            'status' => v::optional(v::in(['active', 'inactive']))
        ]);

        $data['id'] = $this->generateUUID();
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['status'] = $data['status'] ?? 'active';

        $stmt = $this->pdo->prepare("SELECT id FROM product_categories WHERE slug = ?");
        $stmt->execute([$data['slug']]);
        if ($stmt->fetch()) {
            throw new \Exception('Slug đã tồn tại trong danh mục sản phẩm');
        }

        $this->create('product_categories', $data);
        return [
            'success' => 'Tạo danh mục sản phẩm thành công',
            'id' => $data['id']
        ];
    }

    public function updateProductCategory(string $id, array $data): array
    {
        $this->validate($data, [
            'name' => v::optional(v::stringType()->length(1, 255)),
            'slug' => v::optional(v::slug()),
            'status' => v::optional(v::in(['active', 'inactive'])),
            'display_order' => v::optional(v::intVal()->min(0))
        ]);

        if (!empty($data['slug'])) {
            $stmt = $this->pdo->prepare("SELECT id FROM product_categories WHERE slug = ? AND id != ?");
            $stmt->execute([$data['slug'], $id]);
            if ($stmt->fetch()) {
                throw new \Exception('Slug đã tồn tại');
            }
        }

        $this->update('product_categories', $id, $data);
        return ['success' => 'Cập nhật danh mục sản phẩm thành công'];
    }

    public function deleteProductCategory(string $id): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM products WHERE category_id = ? LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            throw new \Exception('Không thể xóa danh mục vì có sản phẩm đang sử dụng');
        }

        $this->delete('product_categories', $id);
        return ['success' => 'Xóa danh mục sản phẩm thành công'];
    }
}