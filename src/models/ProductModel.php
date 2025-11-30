<?php
namespace App\Models;

use PDO;
use Respect\Validation\Validator as v;

class ProductModel extends BaseModel
{
    public function getAll(array $params): array
    {
        $joins = [
            'LEFT JOIN product_images pi ON products.id = pi.product_id',
            'LEFT JOIN product_categories pc ON products.category_id = pc.id',
            'LEFT JOIN product_reviews pr ON products.id = pr.product_id AND pr.status = "approved"'
        ];

        $extraWhere = '';
        $binds = [];

        if (!empty($params['type'])) {
            $extraWhere .= " AND products.type = :type";
            $binds['type'] = $params['type'];
        }
        if (!empty($params['category'])) {
            $extraWhere .= " AND products.category_id = :category";
            $binds['category'] = $params['category'];
        }
        if (!empty($params['status'])) {
            $extraWhere .= " AND products.status = :status";
            $binds['status'] = $params['status'];
        }
        if (isset($params['min_price'])) {
            $extraWhere .= " AND products.price >= :min_price";
            $binds['min_price'] = $params['min_price'];
        }
        
        if (isset($params['max_price'])) {
            $extraWhere .= " AND products.price <= :max_price";
            $binds['max_price'] = $params['max_price'];
        }

        $result = $this->getAllWithPaginationAndFilter(
            'products',
            $params,
            $joins,
            $extraWhere,
            'products.id',
            $params['orderBy'] ?? 'DESC'
        );

        $grouped = [];
        foreach ($result['data'] as $row) {
            $id = $row['id'] ?? null;
            if (!$id) continue;

            if (!isset($grouped[$id])) {
                $grouped[$id] = $row;
                $grouped[$id]['images'] = [];
                $grouped[$id]['features'] = isset($row['features']) ? json_decode($row['features'], true) : [];
                $grouped[$id]['category'] = !empty($row['category_id']) ? [
                    'id' => $row['category_id'],
                    'name' => $row['pc_name'] ?? null,
                    'slug' => $row['pc_slug'] ?? null
                ] : null;

                $grouped[$id]['reviews_count'] = 0;
                $grouped[$id]['average_rating'] = 0.0;
            }

            if (!empty($row['image_url'])) {
                $grouped[$id]['images'][] = [
                    'image_url' => $row['image_url'],
                    'alt_text' => $row['alt_text'] ?? null,
                    'is_primary' => $row['is_primary'] ?? 0
                ];
            }

            if (!empty($row['rating'])) {
                $grouped[$id]['reviews_count']++;
                $grouped[$id]['average_rating'] += (float)$row['rating'];
            }
        }

        foreach ($grouped as &$product) {
            if ($product['reviews_count'] > 0) {
                $product['average_rating'] = round($product['average_rating'] / $product['reviews_count'], 1);
            } else {
                $product['average_rating'] = 0.0;
            }

            $images = $product['images'] ?? [];
            $primaryImage = null;
            foreach ($images as $img) {
                if (!empty($img['is_primary'])) {
                    $primaryImage = $img['image_url'];
                    break;
                }
            }
            if (!$primaryImage && !empty($images)) {
                $primaryImage = $images[0]['image_url'];
            }
            $product['image'] = $primaryImage;
        }

        if (isset($params['min_rating'])) {
            $minRating = floatval($params['min_rating']);
            $grouped = array_filter($grouped, function ($product) use ($minRating) {
                return $product['average_rating'] >= $minRating;
            });
        }

        $result['data'] = array_values($grouped);
        return $result;
    }

    public function getByIdProduct(string $id): ?array
    {
        $product = $this->getById('products', $id);
        if (!$product) return null;

        $product['features'] = isset($product['features']) ? json_decode($product['features'], true) : [];

        if (!empty($product['category_id'])) {
            $stmt = $this->pdo->prepare("SELECT id, name, slug FROM product_categories WHERE id = ?");
            $stmt->execute([$product['category_id']]);
            $product['category'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            $product['category'] = null;
        }

        $stmt = $this->pdo->prepare("SELECT image_url, alt_text, is_primary FROM product_images WHERE product_id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $product['images'] = $images;

         $primaryImage = null;
        foreach ($images as $img) {
            if (!empty($img['is_primary'])) {
                $primaryImage = $img['image_url'];
                break;
            }
        }
        if (!$primaryImage && !empty($images)) {
            $primaryImage = $images[0]['image_url'];
        }

        $product['image'] = $primaryImage;

        return $product;
    }

   public function createProduct(array $data): array
{
    $this->beginTransaction();

    try {
        $this->validate($data, [
            'name' => v::stringType()->length(1, 255)->notEmpty(),
            'app_name' => v::optional(v::stringType()->notEmpty()),
            'description' => v::stringType()->notEmpty(),
            'short_description' => v::optional(v::stringType()),
            'price' => v::number()->positive(),
            'sale_price' => v::optional(v::number()->positive()),
            'type' => v::in(['activation_key', 'other']),
            'category_id' => v::optional(v::stringType()->length(6)),
            'stock' => v::intVal()->min(0),
            'status' => v::in(['active', 'inactive']),
            'features' => v::optional(v::arrayVal()),
            'images' => v::optional(v::arrayVal())
        ]);

        $data['id'] = $this->generateUUID();
        $data['features'] = json_encode($data['features'] ?? []);

        $productData = $data;
        unset($productData['images']);

        $this->create('products', $productData);

        if (!empty($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $img) {
                $this->validate($img, [
                    'image_url' => v::url()->notEmpty(),
                    'alt_text' => v::optional(v::stringType()),
                    'is_primary' => v::boolVal()
                ]);
                $img['id'] = $this->generateUUID();
                $img['product_id'] = $data['id'];
                $this->create('product_images', $img);
            }
        }

        $this->commit();

        return ['success' => true, 'message' => 'Tạo sản phẩm thành công', 'id' => $data['id']];

    } catch (\Exception $e) {
        $this->rollBack();
        error_log("Create product failed: " . $e->getMessage());
        throw new \Exception("Lỗi tạo sản phẩm: " . $e->getMessage());
    }
}

public function updateProduct(string $id, array $data): array
{
    $this->beginTransaction();

    try {
        $existingProduct = $this->getByIdProduct($id);
        if (!$existingProduct) {
            throw new \Exception("Sản phẩm không tồn tại: $id");
        }

        $this->validate($data, [
            'name' => v::optional(v::stringType()->length(1, 255)),
            'description' => v::optional(v::stringType()->notEmpty()),
            'app_name' => v::optional(v::stringType()->notEmpty()),
            'short_description' => v::optional(v::stringType()),
            'price' => v::number()->positive(),
            'sale_price' => v::optional(v::number()->positive()),
            'type' => v::optional(v::in(['activation_key', 'other'])),
            'category_id' => v::optional(v::stringType()->length(36)),
            'stock' => v::optional(v::intVal()->min(0)),
            'status' => v::optional(v::in(['active', 'inactive'])),
            'features' => v::optional(v::arrayVal()),
            'images' => v::optional(v::arrayVal())
        ]);

        if (isset($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }

        $productData = $data;
        unset($productData['images']);

        $this->update('products', $id, $productData);

        if (isset($data['images']) && is_array($data['images'])) {
            $this->pdo->prepare("DELETE FROM product_images WHERE product_id = ?")
                ->execute([$id]);

            foreach ($data['images'] as $img) {
                $this->validate($img, [
                    'image_url' => v::url()->notEmpty(),
                    'alt_text' => v::optional(v::stringType()),
                    'is_primary' => v::boolVal()
                ]);
                $img['id'] = $this->generateUUID();
                $img['product_id'] = $id;
                $this->create('product_images', $img);
            }
        }

        $this->commit();

        return ['success' => true, 'message' => 'Cập nhật sản phẩm thành công'];

    } catch (\Exception $e) {
        $this->rollBack();
        error_log("Update product failed: " . $e->getMessage());
        throw new \Exception("Lỗi cập nhật sản phẩm: " . $e->getMessage());
    }
}

    public function deleteProduct(string $id): array
    {
        $this->beginTransaction();

        try {
            $existingProduct = $this->getByIdProduct($id); 
            if (!$existingProduct) {
                throw new \Exception("Sản phẩm không tồn tại: $id");
            }

            $this->pdo->prepare("DELETE FROM product_images WHERE product_id = ?")
                ->execute([$id]);

            $this->delete('products', $id);

            $this->commit();

            return ['success' => true, 'message' => 'Xóa sản phẩm thành công'];

        } catch (\Exception $e) {
            $this->rollBack();
            error_log("Delete product failed: " . $e->getMessage());
            throw new \Exception("Lỗi xóa sản phẩm: " . $e->getMessage());
        }
    }
}