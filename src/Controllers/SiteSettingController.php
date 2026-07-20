<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\SiteSettingModel;
use Respect\Validation\Exceptions\ValidationException;

class SiteSettingController
{
    private SiteSettingModel $model;

    public function __construct()
    {
        $this->model = new SiteSettingModel();
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $settings = $this->model->getAll($params);
            return $this->jsonResponse($response, 200, [
                'data'  => $settings['data'],
            'pagination' => $settings['pagination'],
                'success' => 'Lấy cài đặt site thành công'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function getById(Request $request, Response $response, array $args): Response
{
    try {
        $id = $args['id'] ?? null;
        if (!$id) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID cài đặt']);
        }

        $setting = $this->model->getByIdSetting($id);
        if (!$setting) {
            return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy cài đặt']);
        }

        return $this->jsonResponse($response, 200, [
            'data' => $setting,
            'success' => 'Lấy chi tiết cài đặt site thành công'
        ]);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}


    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->createSetting($data);

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

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID cài đặt']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->updateSetting($id, $data);

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

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID cài đặt']);
            }

            $result = $this->model->deleteSetting($id);

            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function setUse(Request $request, Response $response, array $args): Response
{
    try {
        $id = $args['id'] ?? null;
        if (!$id) {
            return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID cài đặt']);
        }

        $result = $this->model->setUse($id);
        return $this->jsonResponse($response, 200, $result);

    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}

public function getActive(Request $request, Response $response): Response
{
    try {
        $setting = $this->model->getActiveSetting();

        if (!$setting) {
            return $this->jsonResponse($response, 404, ['error' => 'Chưa có cấu hình đang sử dụng']);
        }

        return $this->jsonResponse($response, 200, [
            'data' => $setting,
            'success' => 'Lấy cấu hình site đang sử dụng thành công'
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