<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\DashboardModel;
use Respect\Validation\Validator as v;

class DashboardController
{
    private DashboardModel $model;

    public function __construct()
    {
        $this->model = new DashboardModel();
    }

    public function overview(Request $request, Response $response): Response
    {
        $data = $this->model->getOverview();
        return $this->json($response, 200, ['data' => $data, 'success' => 'Tổng quan dashboard']);
    }

    public function revenueChart(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();
        $start = $query['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $end = $query['end'] ?? date('Y-m-d');

        $data = $this->model->getRevenueByDateRange($start, $end);
        return $this->json($response, 200, ['data' => $data, 'success' => 'Doanh thu theo ngày']);
    }

    public function topBuyers(Request $request, Response $response): Response
    {
        $limit = (int)($request->getQueryParams()['limit'] ?? 5);
        $data = $this->model->getTopBuyers($limit);
        return $this->json($response, 200, ['data' => $data, 'success' => 'Top khách mua nhiều']);
    }

    public function topCancellers(Request $request, Response $response): Response
    {
        $limit = (int)($request->getQueryParams()['limit'] ?? 5);
        $data = $this->model->getTopCancellers($limit);
        return $this->json($response, 200, ['data' => $data, 'success' => 'Top khách hủy đơn']);
    }

    public function recentOrders(Request $request, Response $response): Response
    {
        $limit = (int)($request->getQueryParams()['limit'] ?? 10);
        $data = $this->model->getRecentOrders($limit);
        return $this->json($response, 200, ['data' => $data, 'success' => 'Đơn hàng mới nhất']);
    }

    public function recentComments(Request $request, Response $response): Response
    {
        $limit = (int)($request->getQueryParams()['limit'] ?? 10);
        $data = $this->model->getRecentComments($limit);
        return $this->json($response, 200, ['data' => $data, 'success' => 'Bình luận mới nhất']);
    }

    public function topProducts(Request $request, Response $response): Response
    {
        $limit = (int)($request->getQueryParams()['limit'] ?? 5);
        $data = $this->model->getTopSellingProducts($limit);
        return $this->json($response, 200, ['data' => $data, 'success' => 'Sản phẩm bán chạy']);
    }

    public function quickStats(Request $request, Response $response): Response
{
    $query = $request->getQueryParams();

    $periods = [];
    if (!empty($query['periods'])) {
        $periods = array_filter(explode(',', $query['periods']));
    }

    $custom = null;
    if (!empty($query['start']) && !empty($query['end'])) {
        $custom = ['start' => $query['start'], 'end' => $query['end']];
    }

    $data = $this->model->getQuickStats($periods, $custom);

    return $this->json($response, 200, [
        'data' => $data,
        'success' => 'Thống kê nhanh'
    ]);
}

    public function keyStats(Request $request, Response $response): Response
    {
        $data = $this->model->getActivationKeyStats();
        return $this->json($response, 200, ['data' => $data, 'success' => 'Thống kê key']);
    }

    public function newUsersChart(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();
        $start = $query['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $end = $query['end'] ?? date('Y-m-d');

        $data = $this->model->getNewUsersByDateRange($start, $end);
        return $this->json($response, 200, ['data' => $data, 'success' => 'Người dùng mới']);
    }

    private function json(Response $response, int $status, array $data): Response
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}