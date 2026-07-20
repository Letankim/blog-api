<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ReviewModel;
use Respect\Validation\Exceptions\ValidationException;

class ReviewController
{
    private ReviewModel $model;

    public function __construct()
    {
        $this->model = new ReviewModel();
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $result = $this->model->getAll($params);
            return $this->jsonResponse($response, 200, [
                'data'       => $result['data'],
                'pagination' => $result['pagination'],
                'success'    => 'Lấy danh sách đánh giá thành công'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

     public function getAllPublic(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $params['status'] = "approved";
            $result = $this->model->getAllPublic($params);
            return $this->jsonResponse($response, 200, [
                'data'       => $result['data'],
                'pagination' => $result['pagination'],
                'avg_rating' => $result['avg_rating'],
                'success'    => 'Lấy danh sách đánh giá thành công'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            if (!$user || !isset($user->id)) {
                return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $data['user_id'] = $user->id;
            $data['status'] = 'pending';
            $result = $this->model->createReview($data);

            return $this->jsonResponse($response, 201, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID đánh giá']);
            }

            $data = $request->getParsedBody();
            $status = $data['status'] ?? null;
            if (!$status) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu trường status']);
            }

            $result = $this->model->updateReviewStatus($id, $status);

            return $this->jsonResponse($response, 200, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Trạng thái không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID đánh giá']);
            }

            $result = $this->model->deleteReview($id);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    private function jsonResponse(Response $response, int $status, array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json);
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    public function checkUserReviewed(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $productId = $params['product_id'] ?? null;
            $user = $request->getAttribute('user');

            if (!$user || !isset($user->id)) {
                return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
            }

            if (!$productId) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu product_id']);
            }

            $review = $this->model->getUserReviewForProduct($productId, $user->id);

            return $this->jsonResponse($response, 200, [
                'reviewed' => $review ? true : false,
                'data'     => $review
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function createOrUpdate(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            if (!$user || !isset($user->id)) {
                return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
            }

            $data = $request->getParsedBody();

            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $data['user_id'] = $user->id;

            $result = $this->model->createOrUpdateReview($data);

            return $this->jsonResponse($response, 200, $result);

        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }


}