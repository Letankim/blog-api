<?php
namespace App\Models;

use Respect\Validation\Validator as v;

class SiteSettingModel extends BaseModel
{
    public function getAll(array $params = []): array
    {
        $result = $this->getAllWithPaginationAndFilter('site_settings', $params);

        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as &$item) {
                if (!empty($item['social_links']) && is_string($item['social_links'])) {
                    $decoded = json_decode($item['social_links'], true);
                    $item['social_links'] = $decoded ?: [];
                }

                $item['require_email_verification'] = (int)($item['require_email_verification'] ?? 1);
                $item['maintenance_mode'] = (int)($item['maintenance_mode'] ?? 0);
            }
        }

        return $result;
    }

    public function getByIdSetting(string $id)
    {
        $result = $this->getById('site_settings', $id);

        if (!empty($result['social_links']) && is_string($result['social_links'])) {
            $decoded = json_decode($result['social_links'], true);
            $result['social_links'] = $decoded ?: [];
        }

        if (!empty($result['setting_json'])) {
                $decoded = json_decode($result['setting_json'], true);
                $result['setting_json'] = $decoded ?: [];
            }

        $result['require_email_verification'] = (int)($result['require_email_verification'] ?? 1);
        $result['maintenance_mode'] = (int)($result['maintenance_mode'] ?? 0);

        return $result;
    }

    public function createSetting(array $data): array
    {
        $this->validate($data, [
            'logo_url'               => v::optional(v::url()),
            'social_links'           => v::optional(v::arrayType()),
            'seo_global_title'       => v::optional(v::stringType()->length(1, 255)),
            'seo_global_description' => v::optional(v::stringType()),
            'require_email_verification' => v::optional(v::intType()->between(0, 1)),
            'maintenance_mode'           => v::optional(v::intType()->between(0, 1)),
        ]);

        $data['id'] = $this->generateUUID();
        $data['is_use'] = $data['is_use'] ?? 0;
        $data['require_email_verification'] = $data['require_email_verification'] ?? 1;
        $data['maintenance_mode'] = $data['maintenance_mode'] ?? 0;

        if (isset($data['social_links']) && is_array($data['social_links'])) {
            $data['social_links'] = json_encode($data['social_links'], JSON_UNESCAPED_UNICODE);
        }

        $this->create('site_settings', $data);

        return [
            'success' => 'Tạo cài đặt site thành công',
            'id'      => $data['id'],
        ];
    }

    public function updateSetting(string $id, array $data): array
    {
        $this->validate($data, [
            'logo_url'               => v::optional(v::url()),
            'social_links'           => v::optional(v::arrayType()),
            'seo_global_title'       => v::optional(v::stringType()->length(1, 255)),
            'seo_global_description' => v::optional(v::stringType()),
            'require_email_verification' => v::optional(v::intType()->between(0, 1)),
            'maintenance_mode'           => v::optional(v::intType()->between(0, 1)),
        ]);

        if (isset($data['social_links']) && is_array($data['social_links'])) {
            $data['social_links'] = json_encode($data['social_links'], JSON_UNESCAPED_UNICODE);
        }

        $this->update('site_settings', $id, $data);

        return ['success' => 'Cập nhật cài đặt site thành công'];
    }

    public function deleteSetting(string $id): array
    {
        $this->delete('site_settings', $id);
        return ['success' => 'Xóa cài đặt site thành công'];
    }

    public function setUse(string $id): array
    {
        $this->beginTransaction();
        $this->pdo->exec("UPDATE site_settings SET is_use = 0");

        $stmt = $this->pdo->prepare("UPDATE site_settings SET is_use = 1 WHERE id = ?");
        $stmt->execute([$id]);

        $this->commit();
        return ['success' => 'Đã chọn cấu hình site đang sử dụng'];
    }

    public function getActiveSetting(): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM site_settings WHERE is_use = 1 LIMIT 1");
        $stmt->execute();
        $setting = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($setting) {
            if (!empty($setting['social_links'])) {
                $decoded = json_decode($setting['social_links'], true);
                $setting['social_links'] = $decoded ?: [];
            }

            if (!empty($setting['setting_json'])) {
                $decoded = json_decode($setting['setting_json'], true);
                $setting['setting_json'] = $decoded ?: [];
            }

            $setting['require_email_verification'] = (int)($setting['require_email_verification'] ?? 1);
            $setting['maintenance_mode'] = (int)($setting['maintenance_mode'] ?? 0);
        }

        return $setting ?: null;
    }
}
