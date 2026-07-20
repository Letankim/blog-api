<?php

namespace App\Models;

use App\Config\settings;
use Exception;
use Firebase\JWT\JWT;
use lbuchs\WebAuthn\WebAuthn;

class PasskeyModel extends BaseModel
{
    private WebAuthn $webauthn;
    private string $rpName = 'My Blog Shop';

    public function __construct()
    {
        parent::__construct();

        $origin = Settings::load()['APP_URL'] ?? 'http://localhost:8000';
        $host   = parse_url($origin, PHP_URL_HOST) ?: 'localhost';

        $this->webauthn = new WebAuthn($this->rpName, $host);
    }

public function startRegistration(string $userId, string $username, string $displayName = ''): array
{
    if (preg_match('/^[a-f0-9-]{36}$/', $userId)) {
        $userIdRaw = hex2bin(str_replace('-', '', $userId));
    } else {
        $userIdRaw = $userId;
    }

    $rpId = parse_url(Settings::load()['APP_URL'] ?? 'https://bc78430f1359.ngrok-free.app', PHP_URL_HOST);

    $challengeRaw = random_bytes(32);
    $challengeB64 = base64_encode($challengeRaw);

    $publicKey = [
        'rp' => [
            'name' => $this->rpName,
            'id'   => $rpId,
        ],
        'user' => [
            'id'          => base64_encode($userIdRaw),
            'name'        => $username,
            'displayName' => $displayName ?: $username,
        ],
        'challenge'        => $challengeB64,
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],
            ['type' => 'public-key', 'alg' => -257],
        ],
        'timeout'           => 60000,
        'attestation'       => 'none',
        'excludeCredentials' => [],
        'authenticatorSelection' => [
            'authenticatorAttachment' => 'platform',
            'residentKey'            => 'required',
            'userVerification'       => 'required',
        ],
        'extensions' => ['credProps' => true]
    ];

    $this->saveChallenge($challengeB64, $userId, 'registration');

    return [
        'publicKey' => $publicKey,
        'challenge' => $challengeB64
    ];
}

    public function startLogin(?string $userEmail = null): array
    {
    $credentialIds = [];

    $sql = "SELECT passkey_credential_id FROM users WHERE passkey_credential_id IS NOT NULL AND status = 'active'";
        if ($userEmail) {
            $sql .= " AND email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userEmail]);
        } else {
            $stmt = $this->pdo->query($sql);
        }

        while ($row = $stmt->fetch()) {
            $rawId = base64_decode(
                strtr($row['passkey_credential_id'], '-_', '+/')
                . str_repeat('=', 3 - (3 + strlen($row['passkey_credential_id'])) % 4)
            );
            if ($rawId !== false) {
                $credentialIds[] = $rawId;
            }
        }

        $rpId = parse_url(Settings::load()['APP_URL'] ?? 'http://localhost:8000', PHP_URL_HOST) ?: 'localhost';
        
        $challengeRaw = random_bytes(32);
        $challengeB64 = base64_encode($challengeRaw);

        $publicKey = [
            'challenge'        => $challengeB64,        
            'timeout'          => 60000,
            'rpId'             => $rpId,
            'allowCredentials' => [],
            'userVerification' => 'required',
            'hints'            => ['client-device'],
            'mediation'        => 'conditional',
        ];

        if (!empty($credentialIds)) {
            $publicKey['allowCredentials'] = array_map(function($rawId) {
            return [
                'type' => 'public-key',
                'id'   => rtrim(strtr(base64_encode($rawId), '+/', '-_'), '=') 
            ];
        }, $credentialIds);
        }

        $this->saveChallenge($challengeB64, null, 'authentication');

        return [
            'publicKey' => $publicKey,
            'challenge' => $challengeB64 
        ];
    }

    public function finishRegistration(array $clientData): array
    {
        $challengeB64 = $clientData['challenge'] ?? null;
        if (!$challengeB64) throw new Exception('Thiếu challenge');

        $row = $this->getChallenge($challengeB64, 'registration');
        if (!$row) throw new Exception('Challenge không hợp lệ hoặc hết hạn');

        $result = $this->webauthn->processCreate(
            base64_decode($clientData['clientDataJSON']),
            base64_decode($clientData['attestationObject']),
            base64_decode($challengeB64)
        );

        if ($result === false) {
            throw new Exception('Tạo Passkey thất bại');
        }
        $credentialIdUrlSafe = rtrim(strtr(base64_encode($result->credentialId), '+/', '-_'), '=');
        $userHandle = $result->userHandle ?? null; 
        $publicKeyBinary = $result->credentialPublicKey;
        $this->pdo->prepare("
            UPDATE users SET
                passkey_credential_id = ?,
                passkey_public_key    = ?,
                passkey_sign_count    = ?,
                passkey_user_handle   = ?,
                passkey_transports    = ?
            WHERE id = ?
        ")->execute([
            $credentialIdUrlSafe,
            base64_encode($publicKeyBinary),
            $result->signatureCounter ?? 0,
            $userHandle ? $this->toUrlSafe($userHandle) : null,
            json_encode($result->transports ?? []),
            $row['user_id']
        ]);

        $this->deleteChallenge($challengeB64);
        return $this->getUserById($row['user_id']);
    }

    private function toUrlSafe($bin): string {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function fromUrlSafe($str): ?string {
        if (!$str) return null;
        $str = strtr($str, '-_', '+/');
        $mod = strlen($str) % 4;
        if ($mod) $str .= str_repeat('=', 4 - $mod);
        return base64_decode($str, true) ?: null;
    }

    public function finishLogin(array $clientData): array
    {
        $challengeB64    = $clientData['challenge'] ?? null;
        $credentialIdB64 = $clientData['credentialId'] ?? null;

        if (!$challengeB64 || !$credentialIdB64) {
            throw new Exception('Thiếu dữ liệu xác thực');
        }

        $credentialIdRaw = $this->fromUrlSafe($credentialIdB64);
        $challengeRaw    = base64_decode($challengeB64, true);
        $userHandleRaw   = !empty($clientData['userHandle']) ? base64_decode($clientData['userHandle'], true) : null;

        if (!$credentialIdRaw || $challengeRaw === false) {
            throw new Exception('Dữ liệu không hợp lệ');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE passkey_credential_id = ? AND status = 'active'");
        $stmt->execute([$credentialIdB64]);
        $user = $stmt->fetch();

        if (!$user) throw new Exception('Không tìm thấy tài khoản');

        $publicKeyRaw = base64_decode($user['passkey_public_key'], true);
        if ($publicKeyRaw === false) {
            throw new Exception('Public key trong DB bị hỏng');
        }

        try {
            $result = $this->webauthn->processGet(
                base64_decode($clientData['clientDataJSON'], true),      
                base64_decode($clientData['authenticatorData'], true),    
                base64_decode($clientData['signature'], true),       
                $publicKeyRaw,                                          
                $challengeRaw,                                            
                (int)($user['passkey_sign_count'] ?? 0),                   
                true,                                                     
                true      
            );

            if ($result === false) {
                throw new Exception('Xác thực thất bại');
            }

            $newSignCount = ($result === true)
                ? (int)($user['passkey_sign_count'] ?? 0) + 1
                : $result->signatureCounter;

            $this->pdo->prepare("UPDATE users SET passkey_sign_count = ? WHERE id = ?")
                ->execute([$newSignCount, $user['id']]);

        } catch (\Exception $e) {
            throw new Exception('Xác thực Passkey thất bại: ' . $e->getMessage());
        }

         $payload = [
            'id'   => $user['id'],
            'role' => $user['role']
        ];

        $this->deleteChallenge($challengeB64);
        $token = JWT::encode($payload, Settings::load()['JWT_SECRET'], 'HS256');

        return [
            'token'       => $token,
            'success'  => 'Đăng nhập thành công',
            'user'        => [
                'id'         => $user['id'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'avatar_url' => $user['avatar_url']
            ]
        ];
    }

    public function revokePasskey(string $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET
                passkey_credential_id = NULL,
                passkey_public_key    = NULL,
                passkey_sign_count    = 0,
                passkey_user_handle   = NULL,
                passkey_transports    = NULL
            WHERE id = ? AND status = 'active'
        ");

        return $stmt->execute([$userId]) && $stmt->rowCount() > 0;
    }

    private function saveChallenge(string $challengeB64, ?string $userId, string $type): void
    {
        $this->pdo->prepare("
            INSERT INTO passkey_challenges (id, challenge, user_id, type, expires_at)
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
        ")->execute([$this->generateUUID1(), $challengeB64, $userId, $type]);
    }

    private function getChallenge(string $challengeB64, string $type): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM passkey_challenges 
            WHERE challenge = ? AND type = ? AND expires_at > NOW()
        ");
        $stmt->execute([$challengeB64, $type]);
        return $stmt->fetch() ?: null;
    }

    private function deleteChallenge(string $challengeB64): void
    {
        $this->pdo->prepare("DELETE FROM passkey_challenges WHERE challenge = ?")
            ->execute([$challengeB64]);
    }

    private function getUserById(string $id): array
    {
        $user = parent::getById('users', $id);
        unset($user['password_hash']);
        return $user;
    }

    private function generateUUID1(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}