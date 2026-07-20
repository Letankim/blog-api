<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class ReviewModel extends BaseModel {
    public function getAll(array $params): array
    {
        $joins = ['LEFT JOIN users u ON product_reviews.user_id = u.id'];
        $extraWhere = !empty($params['product_id']) ? " AND product_reviews.product_id = :product_id" : '';

        $result = $this->getAllWithPaginationAndFilter('product_reviews', $params, $joins, $extraWhere);

        $replyModel = new ReviewReplyModel();

        foreach ($result['data'] as &$row) {
            $row['user_name'] = $row['username'] ?? null;
            unset($row['username']);

            $replies = $replyModel->getAll(['review_id' => $row['id']]);
            $row['replies'] = $replies['data'] ?? [];
        }

        return $result;
    }

    public function getAllPublic(array $params): array
    {
        $joins = ['LEFT JOIN users u ON product_reviews.user_id = u.id AND u.status = "active"'];
        $extraWhere = !empty($params['product_id']) ? " AND product_reviews.product_id = :product_id AND product_reviews.status='approved' " : '';

        $result = $this->getAllWithPaginationAndFilter('product_reviews', $params, $joins, $extraWhere);

        $replyModel = new ReviewReplyModel();

        foreach ($result['data'] as &$row) {
            $row['user_name'] = $row['username'] ?? null;
            unset($row['username']);

            $replies = $replyModel->getAll(['review_id' => $row['id']]);
            $row['replies'] = $replies['data'] ?? [];
        }

       if (!empty($params['product_id'])) {
            $sql = "SELECT AVG(rating) AS avg_rating
                    FROM product_reviews
                    WHERE product_id = :product_id AND status = 'approved'";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':product_id' => $params['product_id']]);
            $avgRatingRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            $avg = isset($avgRatingRow['avg_rating']) ? (float)$avgRatingRow['avg_rating'] : 0.0;
            $result['avg_rating'] = round($avg, 1);
        }
        return $result;
    }

    public function getByIdReview($id) {
        return $this->getById('product_reviews', $id);
    }

    public function createReview($data) {
        $this->validate($data, [
            'product_id' => v::uuid(),
            'user_id' => v::uuid(),
            'rating' => v::intVal()->between(1, 5),
            'content' => v::stringType()->notEmpty(),
            'status' => v::in(['approved', 'pending', 'rejected', 'banned'])
        ]);
        $data['id'] = $this->generateUUID();
        $this->create('product_reviews', $data);
        return ['success' => 'Tạo đánh giá thành công', 'id' => $data['id']];
    }

    public function updateReviewStatus($id, $status) {
        $this->validate(['status' => $status], [
            'status' => v::in(['approved', 'pending', 'rejected', 'banned'])
        ]);
        $this->update('product_reviews', $id, ['status' => $status]);
        return ['success' => 'Cập nhật trạng thái đánh giá thành công'];
    }

    public function deleteReview($id) {
        $this->delete('product_reviews', $id);
        return ['success' => 'Xóa đánh giá thành công'];
    }

    public function getUserReviewForProduct($productId, $userId)
    {
        $sql = "SELECT * 
                FROM product_reviews 
                WHERE product_id = :product_id 
                AND user_id = :user_id 
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId
        ]);

        return $stmt->fetch() ?: null;
    }

    public function createOrUpdateReview($data)
    {
        $this->validate($data, [
            'product_id' => v::uuid(),
            'user_id' => v::uuid(),
            'rating' => v::intVal()->between(1, 5),
            'content' => v::stringType()->notEmpty()
        ]);

        $existingReview = $this->getUserReviewForProduct($data['product_id'], $data['user_id']);


        if ($existingReview && $existingReview['status'] === 'banned') {
            return [
                'success' => false,
                'message' => 'Bạn đã bị khóa quyền đánh giá cho sản phẩm này'
            ];
        }
        if ($existingReview) {
            $updateData = [
                'rating' => $data['rating'],
                'content' => $data['content'],
                'status' => 'pending', 
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->update('product_reviews', $existingReview['id'], $updateData);

            return [
                'success' => true,
                'action' => 'updated',
                'message' => 'Cập nhật đánh giá thành công',
                'id' => $existingReview['id']
            ];
        }

        $newId = $this->generateUUID();
        $data['id'] = $newId;
        $data['status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->create('product_reviews', $data);

        return [
            'success' => true,
            'action' => 'created',
            'message' => 'Tạo đánh giá thành công',
            'id' => $newId
        ];
    }
}
