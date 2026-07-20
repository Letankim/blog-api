<?php
namespace App\Models;

use Respect\Validation\Validator as v;
use App\Validation\ValidationRules as rule;

class MaterialModel extends BaseModel {
   public function getAll(array $params): array
{
    $joins = ['LEFT JOIN material_images mi ON materials.id = mi.material_id'];
    $groupBy = 'materials.id';

    $result = $this->getAllWithPaginationAndFilter('materials', $params, $joins, '', $groupBy);

    $grouped = [];
    foreach ($result['data'] as $row) {
        $id = $row['id'] ?? null;
        if (!$id) continue;

        if (!isset($grouped[$id])) {
            $grouped[$id] = $row;
            $grouped[$id]['images'] = [];
        }

        if (!empty($row['image_url'])) {
            $grouped[$id]['images'][] = [
                'image_url' => $row['image_url'],
                'alt_text'  => $row['alt_text'] ?? null,
               'is_primary' => (bool)$row['is_primary']

            ];
        }
    }

    $result['data'] = array_values($grouped);
    return $result; 
}

public function getMaterialById(string $id): ?array
{
    $sql = "
        SELECT m.*, mi.image_url, mi.alt_text, mi.is_primary
        FROM materials m
        LEFT JOIN material_images mi ON m.id = mi.material_id
        WHERE m.id = :id
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (!$rows) {
        return null;
    }

    $material = $rows[0];
    $material['images'] = [];

    foreach ($rows as $row) {
        if (!empty($row['image_url'])) {
            $material['images'][] = [
                'image_url' => $row['image_url'],
                'alt_text'  => $row['alt_text'] ?? null,
                'is_primary' => (bool)$row['is_primary']
            ];
        }
    }

    return $material;
}


    public function createMaterial($data): string {
        $this->validate($data, [
            'title'         => rule::stringNotEmpty()->length(1, 255),
            'description'   => rule::optional(rule::stringNotEmpty()),
            'file_link'     => rule::optional(v::url()),
            'user_id'       => rule::uuid(),
            'seo_keywords'  => rule::optional(rule::stringNotEmpty()->length(1, 255)),
            'status'        => rule::status(['published', 'private'])
        ], [
            'title'         => 'Tiêu đề bắt buộc và tối đa 255 ký tự',
            'description'   => 'Mô tả không hợp lệ',
            'file_link'     => 'Link file phải là URL hợp lệ',
            'user_id'       => 'ID người dùng không hợp lệ',
            'seo_keywords'  => 'Từ khóa SEO tối đa 255 ký tự',
            'status'        => 'Trạng thái chỉ được là: published, private'
        ]);

        $data['id'] = $this->generateUUID();
        $images = $data['images'] ?? [];
        unset($data['images']);
        parent::create('materials', $data);

        if (!empty($images)) {
        foreach ($images as $img) {
            $this->validate($img, [
                'image_url' => v::url()->notEmpty(),
                'alt_text'  => rule::optional(rule::stringNotEmpty()->length(1, 255))
            ], [
                'image_url' => 'URL hình ảnh không hợp lệ hoặc bị trống',
                'alt_text'  => 'Văn bản thay thế tối đa 255 ký tự'
            ]);
            $img['id'] = $this->generateUUID();
            $img['material_id'] = $data['id'];
            parent::create('material_images', $img);
        }
    }

        return $data['id'];
    }

    public function updateMaterial($id, $data): string {
        $this->validate($data, [
            'title'        => rule::optional(rule::stringNotEmpty()->length(1, 255)),
            'description'  => rule::optional(rule::stringNotEmpty()),
            'file_link'    => rule::optional(v::url())
        ], [
            'title'        => 'Tiêu đề tối đa 255 ký tự',
            'description'  => 'Mô tả không hợp lệ',
            'file_link'    => 'Link file phải là URL hợp lệ'
        ]);

        $this->update('materials', $id, $data);
        return 'Cập nhật tài liệu thành công';
    }

      public function updateStatus($id, $status) {
        $this->validate(['status' => $status], [
            'status' => rule::status(['published', 'private'])
        ], [
            'status' => 'Trạng thái chỉ được là: published, private'
        ]);
        $this->update('materials', $id, ['status' => $status]);
        return ['success' => 'Cập nhật trạng thái tài liệu thành công'];
    }

    public function deleteMaterial($id): string {
        $this->pdo->prepare("DELETE FROM material_images WHERE material_id = :id")->execute(['id' => $id]);
        $this->delete('materials', $id);
        return 'Xóa tài liệu thành công';
    }
}
