<?php
namespace App\Models;

use Respect\Validation\Validator as v;
use Firebase\JWT\JWT;
use App\Config\Settings;
use App\Services\MailService;
use PDO;
use PDOStatement;

class UserModel extends BaseModel
{
    private MailService $mailService;

    public function __construct()
    {
        parent::__construct();
        $this->mailService = new MailService();
    }

    public function register(array $data): array
    {
        $this->validate($data, [
            'username'     => v::stringType()->length(3, 255)->notEmpty(),
            'email'        => v::email()->notEmpty(),
            'password'     => v::stringType()->length(6)->notEmpty(),
            'phone_number' => v::optional(v::phone())
        ]);

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->execute(['email' => $data['email'], 'username' => $data['username']]);
        if ($stmt->fetch()) {
            throw new \Exception('Email hoặc username đã tồn tại');
        }

        $data['id'] = $this->generateUUID();
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        $data['role'] = $data['role'] ?? 'user';
        $data['status'] = 'pending';

        $this->create('users', $data);
        $this->createVerification($data['id'], 'activation', $data['email'], $data['username']);

        return ['success' => 'Đăng ký thành công. Vui lòng kiểm tra email để kích hoạt.'];
    }

 private function createVerification(string $userId, string $type, string $email, string $fullname): void
{
    $token = $type === 'activation'
        ? bin2hex(random_bytes(30))
        : sprintf("%06d", rand(0, 999999));

    $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
    $expires = $type === 'activation'
        ? (clone $now)->modify('+1 hour')
        : (clone $now)->modify('+15 minutes');

    $hashedToken = $type === 'password_reset' ? password_hash($token, PASSWORD_DEFAULT) : $token;
    $otpCode = $type === 'password_reset' ? $token : null;

    $stmt = $this->pdo->prepare("
        INSERT INTO user_verifications (id,user_id, type, token, otp_code, expires_at) 
        VALUES (:id, :user_id, :type, :token, :otp_code, :expires)
        ON DUPLICATE KEY UPDATE 
            token = VALUES(token), 
            otp_code = VALUES(otp_code), 
            expires_at = VALUES(expires_at),
            created_at = NOW()
    ");

    $stmt->execute([
        'id' => $this->generateUUID(),
        'user_id'   => $userId,
        'type'      => $type,
        'token'     => $hashedToken,
        'otp_code'  => $otpCode,
        'expires'   => $expires->format('Y-m-d H:i:s') 
    ]);

    if ($type === 'activation') {
        $link = Settings::load()['APP_URL'] . "/api/v1/users/activate/{$token}";
        $this->mailService->sendActivationEmail($email, $fullname, $link);
    } else {
        $this->mailService->sendOTPEmail($email, $fullname, $token);
    }
}

    public function activateAccount(string $token): array
    {
        $stmt = $this->pdo->prepare("
            SELECT uv.user_id, u.email, u.username 
            FROM user_verifications uv
            JOIN users u ON uv.user_id = u.id
            WHERE uv.type = 'activation' 
            AND uv.token = :token 
            AND uv.expires_at > NOW()
        ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$row) {
            throw new \Exception('Link kích hoạt không hợp lệ hoặc đã hết hạn');
        }

        $this->pdo->prepare("UPDATE users SET status = 'active', is_verified=1, email_verified_at = NOW() WHERE id = ?")
            ->execute([$row->user_id]);

        $this->pdo->prepare("DELETE FROM user_verifications WHERE user_id = ? AND type = 'activation'")
            ->execute([$row->user_id]);

        return ['success' => 'Kích hoạt tài khoản thành công'];
    }

    public function resendActivation(string $email): array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, status, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ((!$user || ($user->status !== 'pending' && $user->is_verified !== 0))) {
            throw new \Exception('Tài khoản không tồn tại hoặc đã được kích hoạt');
        }

        $this->createVerification($user->id, 'activation', $email, $user->username);
        return ['success' => 'Đã gửi lại link kích hoạt'];
    }

public function login(array $data): array
{
    $this->validate($data, [
        'email'    => v::email()->notEmpty(),
        'password' => v::stringType()->length(6)->notEmpty(),
    ]);

    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data['password'], $user['password_hash'])) {
        throw new \Exception('Email hoặc mật khẩu không chính xác');
    }

    if ($user['status'] !== 'active') {
        throw new \Exception('Tài khoản chưa được kích hoạt hoặc bị khóa');
    }

    $payload = [
        'id'   => $user['id'],
        'role' => $user['role']
    ];

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


public function findByGoogleId(string $googleId): ?array
{
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$googleId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

public function findByEmail(string $email): ?array
{
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

public function linkGoogleToExistingUser(string $userId, string $googleId, ?string $avatarUrl = null): void
{
    $sql = "UPDATE users SET google_id = ?, login_method = 'google' WHERE id = ?";
    $params = [$googleId, $userId];

    if ($avatarUrl) {
        $sql = "UPDATE users SET google_id = ?, login_method = 'google', avatar_url = ?, is_verified = 1, status = 'active' WHERE id = ?";
        $params = [$googleId, $avatarUrl, $userId];
    }

    $this->pdo->prepare($sql)->execute($params);
}

public function createGoogleUser(array $data): array
{
    $id = $this->generateUUID();
    $username = $this->generateUniqueUsername($data['username']);

    $sql = "INSERT INTO users 
            (id, username, email, google_id, avatar_url, status, is_verified, role, login_method, created_at) 
            VALUES (?, ?, ?, ?, ?, 'active', 1, 'user', 'google', NOW())";

    $this->pdo->prepare($sql)->execute([
        $id,
        $username,
        $data['email'],
        $data['google_id'],
        $data['avatar_url'] ?? null
    ]);

    return [
        'id'         => $id,
        'username'   => $username,
        'email'      => $data['email'],
        'role'       => 'user',
        'avatar_url' => $data['avatar_url'] ?? null,
        'login_method' => 'google'
    ];
}

private function generateUniqueUsername(string $base): string
{
    $base = preg_replace('/[^a-zA-Z0-9]/', '', $base) ?: 'user';
    $username = $base;

    $i = 1;
    while (true) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) break;
        $username = $base . $i;
        $i++;
    }
    return $username;
}
    

    // === QUÊN MẬT KHẨU ===
    public function forgotPassword(string $email): array
    {
        $stmt = $this->pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$user) {
            throw new \Exception('Email không tồn tại');
        }

        $this->createVerification($user->id, 'password_reset', $email, $user->username);
        return ['success' => 'Đã gửi mã OTP đến email'];
    }

    
    // === THAY ĐỔI MẬT KHẨU ===
    public function changePasswordRequest(string $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$user) {
            throw new \Exception('Người dùng không tồn tại');
        }

        $this->createVerification($user->id, 'password_reset', $user->email, $user->username);
        return ['success' => 'Đã gửi mã OTP đến email'];
    }

    public function changePasswordOtp(string $userId, string $otp, string $newPassword): array
    {
        $stmt = $this->pdo->prepare("
            SELECT uv.user_id, uv.token, uv.otp_code 
            FROM user_verifications uv
            JOIN users u ON uv.user_id = u.id
            WHERE u.id = :userId 
            AND uv.type = 'password_reset' 
            AND uv.expires_at > NOW()
        ");
        $stmt->execute(['userId' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$row) {
            throw new \Exception('Không tìm thấy yêu cầu đặt lại mật khẩu');
        }

        $valid = !empty($row->otp_code)
            ? ($row->otp_code === $otp)
            : password_verify($otp, $row->token);

        if (!$valid) {
            throw new \Exception('Mã OTP không đúng hoặc đã hết hạn');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([$newHash, $row->user_id]);

        $this->pdo->prepare("DELETE FROM user_verifications WHERE user_id = ? AND type = 'password_reset'")
            ->execute([$row->user_id]);

        return ['success' => 'Đặt lại mật khẩu thành công'];
    }

    public function resetPassword(string $email, string $otp, string $newPassword): array
    {
        $stmt = $this->pdo->prepare("
            SELECT uv.user_id, uv.token, uv.otp_code 
            FROM user_verifications uv
            JOIN users u ON uv.user_id = u.id
            WHERE u.email = :email 
            AND uv.type = 'password_reset' 
            AND uv.expires_at > NOW()
        ");
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$row) {
            throw new \Exception('Không tìm thấy yêu cầu đặt lại mật khẩu');
        }

        $valid = !empty($row->otp_code)
            ? ($row->otp_code === $otp)
            : password_verify($otp, $row->token);

        if (!$valid) {
            throw new \Exception('Mã OTP không đúng hoặc đã hết hạn');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([$newHash, $row->user_id]);

        $this->pdo->prepare("DELETE FROM user_verifications WHERE user_id = ? AND type = 'password_reset'")
            ->execute([$row->user_id]);

        return ['success' => 'Đặt lại mật khẩu thành công'];
    }

    public function getProfile(string $userId): array
    {
        $user = parent::getById('users', $userId);
        if (!$user || !is_array($user)) {
            throw new \Exception('Không tìm thấy người dùng');
        }
        unset($user['password_hash']);
        return $user;
    }

    public function updateProfile(string $userId, array $data): array
    {
        $allowed = ['username', 'phone_number', 'avatar_url'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        $this->validate($filtered, [
            'username'     => v::optional(v::stringType()->length(3, 255)),
            'phone_number' => v::optional(v::phone()),
            'avatar_url'   => v::optional(v::url()),
        ]);

        if (!empty($filtered['username'])) {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$filtered['username'], $userId]);
            if ($stmt->fetch()) {
                throw new \Exception('Username đã tồn tại');
            }
        }

        parent::update('users', $userId, $filtered);
        return ['success' => 'Cập nhật hồ sơ thành công'];
    }

    // === UPLOAD AVATAR ===
    public function uploadAvatar(string $userId, $file): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Lỗi upload file');
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            throw new \Exception('Chỉ cho phép JPG, PNG, GIF');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new \Exception('Định dạng file không hợp lệ (MIME Type không đúng)');
        }

        if (getimagesize($file['tmp_name']) === false) {
            throw new \Exception('File tải lên không phải là hình ảnh hợp lệ');
        }

        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = $userId . '.' . $ext;
        $path = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \Exception('Không thể lưu file');
        }

        $url = Settings::load()['APP_URL'] . "/uploads/avatars/{$filename}";
        $this->pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?")
            ->execute([$url, $userId]);

        return ['success' => 'Upload avatar thành công', 'avatar_url' => $url];
    }

    // === ADMIN FUNCTIONS ===
    public function getAll(array $params = []): array
{
    $result = $this->getAllWithPaginationAndFilter('users', $params);
    
    foreach ($result['data'] as &$user) {
        unset($user['password_hash']);
    }

    return $result;
}
    public function getUserById(string $id): ?array
    {
        $user = parent::getById('users', $id);
        return is_array($user) ? $user : null;
    }

    public function updateUser(string $id, array $data): array
    {
        $this->validate($data, [
            'username'     => v::optional(v::stringType()->length(3, 255)),
            'phone_number' => v::optional(v::phone()),
            'avatar_url'   => v::optional(v::url()),
            'status'       => v::optional(v::in(['pending','active','inactive','banned'])),
            'role'         => v::optional(v::in(['user','admin'])),
        ]);

        parent::update('users', $id, $data);
        return ['success' => 'Cập nhật người dùng thành công'];
    }

    public function deleteUser(string $id): array
    {
        parent::delete('users', $id);
        return ['success' => 'Xóa người dùng thành công'];
    }

public function adminActivateUser(string $userId): array
{
    $stmt = $this->pdo->prepare("SELECT id, email, username, status, is_verified FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        throw new \Exception('Không tìm thấy người dùng');
    }

    if ($user->status === 'active' && $user->is_verified) {
        return ['success' => 'Tài khoản đã được kích hoạt trước đó'];
    }

    $this->pdo->prepare("UPDATE users 
        SET status = 'active', is_verified = 1, email_verified_at = NOW() 
        WHERE id = ?")
        ->execute([$userId]);

    return ['success' => 'Đã kích hoạt tài khoản thành công và gửi thông báo cho người dùng'];
}

public function adminRequestActivation(string $userId): array
{
    $stmt = $this->pdo->prepare("SELECT id, email, username, status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        throw new \Exception('Không tìm thấy người dùng');
    }

    try {
        $this->pdo->beginTransaction();

        $this->createVerification($user->id, 'activation', $user->email, $user->username);

        if ($user->status !== 'pending') {
            $update = $this->pdo->prepare("UPDATE users SET status = 'pending' WHERE id = ?");
            $update->execute([$user->id]);
        }

        $this->pdo->commit();

        return ['success' => 'Đã gửi lại email kích hoạt và cập nhật trạng thái người dùng thành pending'];
    } catch (\Exception $e) {
        $this->pdo->rollBack();
        throw new \Exception('Lỗi khi gửi lại email kích hoạt: ' . $e->getMessage());
    }
}

}