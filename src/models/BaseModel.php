<?php
namespace App\Models;

use PDO;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use App\Config\Database;

abstract class BaseModel
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

protected function validate(array $data, array $rules): void
{
    $errors = [];

    foreach ($rules as $field => $validator) {
        $value = $data[$field] ?? null;

        try {
            $validator->assert($value);
        } catch (\Exception $e) {
            $errors[$field] = "Trường {$field} không hợp lệ";
        }
    }

    if (!empty($errors)) {
        throw new \Exception(json_encode($errors, JSON_UNESCAPED_UNICODE));
    }
}

    private function columnExists(string $table, string $column): bool
    {
        try {
            $dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
            $sql = "SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = :db 
                    AND TABLE_NAME = :table 
                    AND COLUMN_NAME = :column";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'db' => $dbName,
                'table' => $table,
                'column' => $column
            ]);
            
            $exists = $stmt->rowCount() > 0;
            return $exists;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function buildSearchCondition(string $table, array $params, string $prefix): array
    {
        $conditions = [];
        $binds = [];

        if (empty($params['search'])) {
            return [$conditions, $binds];
        }

        $rawSearch = $params['search'];
        $value = "%" . $rawSearch . "%";
        $columns = ['title', 'name', 'content', 'short_description'];

        foreach ($columns as $i => $col) {
            if ($this->columnExists($table, $col)) {
                $placeholder = ":{$prefix}_{$i}";
                $conditions[] = "$table.$col COLLATE utf8mb4_unicode_ci LIKE $placeholder";
                $binds[$placeholder] = $value;
            } 
        }

        return [$conditions, $binds];
    }

    private function extractBindsFromWhere(string $where, array $params): array
    {
        $binds = [];
        if (preg_match_all('/:\w+/', $where, $matches)) {
            foreach ($matches[0] as $placeholder) {
                $key = ltrim($placeholder, ':');
                if (isset($params[$key])) {
                    $binds[$key] = $params[$key];
                }
            }
        }
        return $binds;
    }

protected function getAllWithPaginationAndFilter(
    string $table,
    array $params = [],
    array $joins = [],
    string $extraWhere = '',
    string $groupBy = '',
    string $defaultOrderBy = 'DESC',
    bool $enablePagination = true,
    array $searchColumns = [],
    array $sortableColumns = []
): array {
    $usePagination = $enablePagination && (!isset($params['pagination']) || $params['pagination'] !== '0');

    if (empty($searchColumns)) {
        $defaultColumns = [
            'posts'             => ['title', 'content', 'short_description'],
            'post_categories'   => ['name', 'slug'],
            'products'          => ['name', 'short_description', 'description'],
            'product_categories'=> ['name', 'slug'],
            'users'             => ['username', 'email', 'phone_number'],
            'comments'          => ['content'],
            'notifications'     => ['title', 'message'],
            'tags'              => ['name'],
            'vouchers'          => ['code'],
        ];
        $searchColumns = $defaultColumns[$table] ?? ['name', 'title'];
    }

    if (empty($sortableColumns)) {
        $defaultSortable = [
            'posts'             => ['title', 'created_at'],
            'products'          => ['name', 'price', 'created_at', 'rating_avg'],
            'post_categories'   => ['name', 'created_at'],
            'product_categories'=> ['name', 'created_at'],
            'users'             => ['username', 'email', 'created_at'],
        ];
        $sortableColumns = $defaultSortable[$table] ?? ['created_at'];
    }

    $sortBy    = $params['sort_by'] ?? null;
    $sortOrder = strtoupper($params['sort_order'] ?? $defaultOrderBy) === 'ASC' ? 'ASC' : 'DESC';

    $orderByClause      = "$table.created_at $sortOrder";
    $extraJoinsForSort  = [];
    $selectExtraForSort = '';
    $finalGroupBy       = $groupBy;

    $hasProductReviews = false;
    foreach ($joins as $join) {
        if (preg_match('/\bproduct_reviews\s+pr\b/i', $join)) {
            $hasProductReviews = true;
            break;
        }
    }

    if ($table === 'products' && $sortBy === 'rating_avg') {
        if (!$hasProductReviews) {
            $extraJoinsForSort[] = "LEFT JOIN product_reviews pr ON pr.product_id = $table.id AND pr.status = 'approved'";
        }
        $selectExtraForSort = ', COALESCE(AVG(pr.rating), 0) AS rating_avg';
        $orderByClause      = "rating_avg $sortOrder";
        $finalGroupBy       = "$table.id";
    } elseif ($sortBy && in_array($sortBy, $sortableColumns, true)) {
        if ($this->columnExists($table, $sortBy)) {
            $orderByClause = "$table.$sortBy $sortOrder";
        }
    }

    // 4. Search
    $searchConditions = [];
    $searchBinds      = [];
    if (!empty($params['search'])) {
        $value = "%" . trim($params['search']) . "%";
        foreach ($searchColumns as $i => $col) {
            if ($this->columnExists($table, $col)) {
                $placeholder = ":search_{$i}";
                $searchConditions[] = "$table.$col COLLATE utf8mb4_unicode_ci LIKE $placeholder";
                $searchBinds[$placeholder] = $value;
            }
        }
    }

    // 5. No pagination
    if (!$usePagination) {
        $joins = [];
        $selectColumns = "$table.id, " . ($searchColumns[0] ?? 'name');
        $sql = "SELECT $selectColumns FROM $table WHERE 1=1 $extraWhere";
        if (!empty($searchConditions)) {
            $sql .= " AND (" . implode(' OR ', $searchConditions) . ")";
        }
        $defaultSortCol = $searchColumns[0] ?? 'name';
        $sql .= " ORDER BY $table.$defaultSortCol ASC LIMIT 50";

        $binds = array_merge($searchBinds, $this->extractBindsFromWhere($extraWhere, $params));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($binds);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['data' => $data, 'pagination' => null];
    }

    if (!empty($params['exclude_id'])) {
        $extraWhere .= " AND $table.id != :exclude_id";
    }

    // 6. SELECT
    $selectColumns = "$table.*" . $selectExtraForSort;
    $joinSelects   = [];
    foreach ($joins as $join) {
        if (preg_match('/JOIN\s+post_images\s+pi/i', $join)) $joinSelects[] = 'pi.image_url, pi.alt_text, pi.is_primary';
        if (preg_match('/JOIN\s+material_images\s+mi/i', $join)) $joinSelects[] = 'mi.image_url, mi.alt_text, mi.is_primary';
        if (preg_match('/JOIN\s+product_reviews\s+pr/i', $join)) $joinSelects[] = 'pr.rating';
        if (preg_match('/JOIN\s+tags\s+t/i', $join)) $joinSelects[] = 't.name AS tag_name';
        if (preg_match('/JOIN\s+post_categories\s+pc/i', $join)) $joinSelects[] = 'pc.name AS pc_name, pc.slug AS pc_slug';
        if (preg_match('/JOIN\s+product_categories\s+pc/i', $join)) $joinSelects[] = 'pc.name AS pc_name, pc.slug AS pc_slug';
        if (preg_match('/JOIN\s+users\s+u/i', $join)) $joinSelects[] = 'u.username, u.avatar_url';
    }
    if (!empty($joinSelects)) {
        $selectColumns .= ', ' . implode(', ', $joinSelects);
    }

    $countSql = "SELECT COUNT(*) FROM ( 
        SELECT DISTINCT $table.id 
        FROM $table";
    foreach ($joins as $join) $countSql .= " $join";
    foreach ($extraJoinsForSort as $join) $countSql .= " $join";
    $countSql .= " WHERE 1=1 $extraWhere";
    if (!empty($searchConditions)) $countSql .= " AND (" . implode(' OR ', $searchConditions) . ")";
    if ($finalGroupBy) $countSql .= " GROUP BY $finalGroupBy";
    $countSql .= " ) AS counted";

    $countStmt = $this->pdo->prepare($countSql);
    $countStmt->execute(array_merge($searchBinds, $this->extractBindsFromWhere($extraWhere, $params)));
    $totalRecords = (int) $countStmt->fetchColumn();

    // 8. Main query
    $sql = "SELECT $selectColumns FROM $table";
    foreach ($joins as $join) $sql .= " $join";
    foreach ($extraJoinsForSort as $join) $sql .= " $join";
    $sql .= " WHERE 1=1 $extraWhere";
    if (!empty($searchConditions)) $sql .= " AND (" . implode(' OR ', $searchConditions) . ")";
    if ($finalGroupBy) $sql .= " GROUP BY $finalGroupBy";
    $sql .= " ORDER BY $orderByClause";

    // 9. Pagination
    $page    = max(1, (int)($params['page'] ?? 1));
    $perPage = min(100, max(1, (int)($params['limit'] ?? 10)));
    $offset  = ($page - 1) * $perPage;
    $sql .= " LIMIT :limit OFFSET :offset";

    $binds = array_merge($searchBinds, $this->extractBindsFromWhere($extraWhere, $params));
    $binds[':limit'] = $perPage;
    $binds[':offset'] = $offset;

    if (!empty($params['exclude_id'])) {
        $binds[':exclude_id'] = $params['exclude_id'];
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($binds);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = max(1, (int) ceil($totalRecords / $perPage));

    return [
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $totalRecords,
            'last_page'    => $totalPages,
            'from'         => $totalRecords > 0 ? ($page - 1) * $perPage + 1 : 0,
            'to'           => min($page * $perPage, $totalRecords)
        ]
    ];
}

    protected function getById(string $table, string $id, ?string $status = null): ?array
    {
        $sql = "SELECT * FROM $table WHERE id = :id";
        $params = ['id' => $id];

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }


    protected function create(string $table, array $data): string
    {
        $keys = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($keys) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    protected function update(string $table, string $id, array $data): void
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = :$key";
        }
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    protected function delete(string $table, string $id): void
    {
        $sql = "DELETE FROM $table WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    protected function getOne(string $table, array $conditions): ?array
{
    $where = [];
    $values = [];

    foreach ($conditions as $key => $value) {
        $where[] = "$key = ?";
        $values[] = $value;
    }

    $whereClause = implode(' AND ', $where);

    $sql = "SELECT * FROM {$table} WHERE {$whereClause} LIMIT 1";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($values);

    $result = $stmt->fetch(\PDO::FETCH_ASSOC);

    return $result ?: null;
}


    protected function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    

    protected function beginTransaction(): void { $this->pdo->beginTransaction(); }
    protected function commit(): void { $this->pdo->commit(); }
    protected function rollBack(): void { $this->pdo->rollBack(); }
}