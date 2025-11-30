<?php

namespace App\Controllers;

use App\Models\CommentModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Exceptions\ValidationException;

class CommentController
{
    private CommentModel $model;

    public function __construct()
    {
        $this->model = new CommentModel();
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $comments = $this->model->getAll($params);

            return $this->jsonResponse($response, 200, [
            'data'       => $comments['data'],
            'pagination' => $comments['pagination'],
            'success'    => 'Lấy danh sách bình luận thành công'
        ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => $e->getMessage()
            ]);
        }
    }


     public function getAllCommentActiveByPost(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();

            if (empty($params['post_id'])) {
                throw new \Exception('Thiếu tham số post_id');
            }

            $params['status'] ='approved';

            $comments = $this->model->getAll($params);

            return $this->jsonResponse($response, 200, [
                'data'       => $comments['data'] ?? [],
                'pagination' => $comments['pagination'] ?? null,
                'success'    => 'Lấy danh sách bình luận thành công'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => $e->getMessage()
            ]);
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
            $data['status'] = 'approved'; 

            $result = $this->model->createComment($data);
            
            return $this->jsonResponse($response, 201, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage() 
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => $e->getMessage()."abc"
            ]);
        }
    }

     public function updateByUser(Request $request, Response $response): Response
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
            $data['status'] = 'approved'; 

            $result = $this->model->updateComment($data["commentId"], $data, false );
            
            return $this->jsonResponse($response, 201, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Dữ liệu không hợp lệ',
                'details' => $e->getMessage() 
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => $e->getMessage()."abc"
            ]);
        }
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID bình luận']);
            }

            $data = $request->getParsedBody();
            $status = $data['status'] ?? null;

            if (!$status) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu trường status']);
            }

            $result = $this->model->updateStatus($id, $status);

            return $this->jsonResponse($response, 200, $result);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, 422, [
                'error' => 'Trạng thái không hợp lệ',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID bình luận']);
            }

            $result = $this->model->deleteComment($id);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, [
                'error' => $e->getMessage()
            ]);
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