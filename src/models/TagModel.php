<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class TagModel extends BaseModel {
    public function getAll($params) {
        return $this->getAllWithPaginationAndFilter('tags', $params);
    }

    public function getByIdTag($id) {
        return $this->getById('tags', $id);
    }

    public function createTag($data) {
        $this->validate($data, [
            'name' => v::stringType()->length(1, 100)->notEmpty()
        ]);
        $data['id'] = $this->generateUUID();
        $this->create('tags', $data);
        return ['success' => 'Tạo tag thành công', 'id' => $data['id']];
    }

    public function updateTag($id, $data) {
        $this->validate($data, [
            'name' => v::optional(v::stringType()->length(1, 100))
        ]);
        $this->update('tags', $id, $data);
        return ['success' => 'Cập nhật tag thành công'];
    }

    public function deleteTag($id) {
        $this->delete('tags', $id);
        return ['success' => 'Xóa tag thành công'];
    }
}