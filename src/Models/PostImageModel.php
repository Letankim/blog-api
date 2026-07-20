<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class PostImageModel extends BaseModel {
    public function addImageToPost($data) {
        $this->validate($data, [
            'post_id' => v::uuid(),
            'image_url' => v::url()->notEmpty(),
            'alt_text' => v::optional(v::stringType()->length(1, 255)),
            'is_primary' => v::boolVal()
        ]);
        $fields = array_filter($data);
        $id = $this->create('post_images', $data, $fields);
        return ['success' => 'Thêm hình ảnh vào bài viết thành công'];
    }
}