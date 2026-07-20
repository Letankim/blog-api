<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\PostLikeModel;
use Respect\Validation\Exceptions\ValidationException;

class PostLikeController
{
    private PostLikeModel $model;

    public function __construct()
    {
        $this->model = new PostLikeModel();
    }

    public function like(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            if (!$user || !isset($user->id)) {
                return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data) || empty($data['post_id'])) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu post_id']);
            }

            $data['user_id'] = $user->id;
            $result = $this->model->like($data);

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

    public function unlike(Request $request, Response $response, array $args): Response
    {
        try {
            $user = $request->getAttribute('user');
            if (!$user || !isset($user->id)) {
                return $this->jsonResponse($response, 401, ['error' => 'Yêu cầu xác thực']);
            }

            $postId = $args['post_id'] ?? null;
            if (!$postId) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu post_id']);
            }

            $result = $this->model->unlike($postId, $user->id);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

   public function getLikesByPost(Request $request, Response $response, array $args): Response
    {
        try {
            $postId = $args['post_id'] ?? null;
            if (!$postId) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu post_id']);
            }

            $result = $this->model->getLikesByPost($postId);

            return $this->jsonResponse($response, 200, [
                'success' => true,
                'count'   => $result['count'],
                'data'   => $result['users'],
            ]);
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
}