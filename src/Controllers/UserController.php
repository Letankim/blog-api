<?php

namespace App\Controllers;

use App\Config\Settings;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\UserModel;
use Firebase\JWT\JWT;
use League\OAuth2\Client\Provider\Google;
use Respect\Validation\Exceptions\ValidationException;

class UserController
{
    private Google $provider;
    private UserModel $model;

    public function __construct()
    {
        $settings = Settings::load()['google_oauth'] ?? [];
        $this->model = new UserModel();
        $this->provider = new Google([
            'clientId'     => $settings['clientId'] ?? '',
            'clientSecret' => $settings['clientSecret'] ?? '',
            'redirectUri'  => $settings['redirectUri'] ?? '',
        ]);
    }

    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            if (isset($data['email'])) {
                $data['email'] = strtolower(trim($data['email']));
            }
            if (isset($data['password'])) {
                $data['password'] = trim($data['password']);
            }

            $result = $this->model->register($data);
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


    public function getLoginUrl(Request $request, Response $response): Response
    {
        $authUrl = $this->provider->getAuthorizationUrl([
            'scope' => ['openid', 'email', 'profile']
        ]);

        $state = $this->provider->getState();

        $response = $response->withHeader(
            'Set-Cookie',
            'oauth_state=' . $state . '; Path=/; HttpOnly; SameSite=Lax; Max-Age=600'
        );

        return $this->jsonResponse($response, 200, [
            'url' => $authUrl . '&popup=1'
        ]);
    }

    public function handleGoogleCallback(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();

        if (!empty($query['error'])) {
            return $this->jsonResponse($response, 400, ['error' => 'Đăng nhập Google bị hủy']);
        }

        $code  = $query['code'] ?? null;
        $state = $query['state'] ?? null;

        $popup = $query['popup'] ?? null;

        if (!$code || !$state) {
        return $this->jsonResponse($response, 400, ['error' => 'State không hợp lệ hoặc đã hết hạn']);
    }

        $response = $response->withHeader(
            'Set-Cookie',
            'oauth_state=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT'
        );

        try {
            $accessToken = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
            $resourceOwner = $this->provider->getResourceOwner($accessToken);
            $userData = $resourceOwner->toArray();

            $email    = $userData['email'] ?? null;
            $googleId = $userData['sub'] ?? null;
            $name     = $userData['name'] ?? $userData['given_name'] ?? 'User';
            $avatar   = $userData['picture'] ?? null;

            if (!$email || !$googleId) {
                throw new \Exception('Không lấy được thông tin từ Google');
            }

            $user = $this->model->findByGoogleId($googleId);

            if (!$user) {
                $existingUser = $this->model->findByEmail($email);

                if ($existingUser) {
                    $this->model->linkGoogleToExistingUser($existingUser['id'], $googleId, $avatar);
                    $user = $existingUser;
                    $user['google_id'] = $googleId;
                    $user['login_method'] = 'google';
                } else {
                    $user = $this->model->createGoogleUser([
                        'email'      => $email,
                        'google_id'  => $googleId,
                        'username'   => $name,
                        'avatar_url' => $avatar
                    ]);
                }
            }

            $payload = [
                'id'   => $user['id'],
                'role' => $user['role'] ?? 'user',
                'exp'  => time() + 604800 
            ];

            $jwt = JWT::encode($payload, Settings::load()['JWT_SECRET'], 'HS256');
            $result =[
                'success' => true,
                'message' => 'Đăng nhập bằng Google thành công',
                'token'   => $jwt,
                'user'    => [
                    'id'           => $user['id'],
                    'username'     => $user['username'],
                    'email'        => $user['email'],
                    'role'         => $user['role'] ?? 'user',
                    'avatar_url'   => $user['avatar_url'] ?? null,
                    'login_method' => $user['login_method'] ?? 'google'
                ]
                ];

                $script = "<script>
                    window.opener.postMessage({
                        type: 'GOOGLE_LOGIN_SUCCESS',
                        token: " . json_encode($jwt) . ",
                        user: " . json_encode($result['user']) . "
                    }, '*');
                    window.close();
                </script>";
                $response->getBody()->write($script);
                return $response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => 'Lỗi Google OAuth: ' . $e->getMessage()]);
        }
    }

    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            // --- DEBUG LOGGING ---
            error_log("LOGIN PAYLOAD: " . json_encode($data));
            // ---------------------

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            if (isset($data['password'])) {
                $data['password'] = trim($data['password']);
            }

            $result = $this->model->login($data);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 401, ['error' => $e->getMessage()]);
        }
    }

    public function activate(Request $request, Response $response, array $args): Response
    {
        try {
            $token = $args['token'] ?? null;
            if (!$token) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu token']);
            }

            $result = $this->model->activateAccount($token);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function resendActivation(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $email = $data['email'] ?? null;
            if (!$email) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu email']);
            }

            $result = $this->model->resendActivation($email);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            $decoded = json_decode($e->getMessage(), true);
            if (is_array($decoded) && isset($decoded['expires_at'])) {
                return $this->jsonResponse($response, 400, [
                    'error' => $decoded['message'], 
                    'expires_at' => $decoded['expires_at']
                ]);
            }
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }


    public function adminRequestActivation(Request $request, Response $response, array $args): Response
    {
        try {
              $userId = $args['id'] ?? '';
            if (!$userId) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu user_id']);
            }

            $result = $this->model->adminRequestActivation($userId);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function adminActivateUser(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = $args['id'] ?? '';
            if (!$userId) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu user_id']);
            }

            $result = $this->model->adminActivateUser($userId);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    // === QUÊN MẬT KHẨU (GỬI OTP) ===
    public function forgotPassword(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $email = $data['email'] ?? null;
            if (!$email) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu email']);
            }
            $email = trim($email);

            $result = $this->model->forgotPassword($email);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            $decoded = json_decode($e->getMessage(), true);
            if (is_array($decoded) && isset($decoded['expires_at'])) {
                return $this->jsonResponse($response, 400, [
                    'error' => $decoded['message'], 
                    'expires_at' => $decoded['expires_at']
                ]);
            }
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    // === ĐẶT LẠI MẬT KHẨU ===
    public function resetPassword(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $required = ['email', 'otp', 'new_password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, 400, ['error' => "Thiếu $field"]);
                }
            }

            $email = trim($data['email']);
            $otp = trim($data['otp']);
            $newPassword = trim($data['new_password']);

            // --- DEBUG LOGGING ---
            error_log("RESET PW PAYLOAD: email=$email, otp=$otp, new_password_len=" . strlen($newPassword));
            // ---------------------

            $result = $this->model->resetPassword($email, $otp, $newPassword);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }


    public function changePasswordRequest(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user'); 
            if (!$user) {
                return $this->jsonResponse($response, 400, ['error' => 'Thông tin đầu vào không hợp lệ.']);
            }

            $result = $this->model->changePasswordRequest($user->id);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

     public function changePasswordOtp(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $user = $request->getAttribute('user'); 
            if (!$user) {
                return $this->jsonResponse($response, 400, ['error' => 'Thông tin đầu vào không hợp lệ.']);
            }
            $required = ['otp', 'new_password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->jsonResponse($response, 400, ['error' => "Thiếu $field"]);
                }
            }
            
            $otp = trim($data['otp']);
            $newPassword = trim($data['new_password']);

            $result = $this->model->changePasswordOtp($user->id, $otp, $newPassword);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function getProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user'); 
            $profile = $this->model->getProfile(userId: $user->id);
            return $this->jsonResponse($response, 200, ['data' => $profile]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->updateProfile($user->id, $data);
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

    // === UPLOAD AVATAR ===
    public function uploadAvatar(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $files = $request->getUploadedFiles();
            $avatar = $files['avatar'] ?? null;
            if (!$avatar || $avatar->getError() !== UPLOAD_ERR_OK) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu hoặc lỗi file']);
            }

            $result = $this->model->uploadAvatar($user->id, $avatar);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    // === ADMIN: DANH SÁCH NGƯỜI DÙNG ===
    public function getAll(Request $request, Response $response): Response
{
    try {
        $params = $request->getQueryParams();
        $result = $this->model->getAll($params); 

        return $this->jsonResponse($response, 200, [
            'data'       => $result['data'],
            'pagination' => $result['pagination'],
            'success'    => 'Lấy danh sách người dùng thành công'
        ]);
    } catch (\Exception $e) {
        return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
    }
}

    // === ADMIN: LẤY THEO ID ===
    public function getById(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID người dùng']);
            }

            $user = $this->model->getUserById($id);
            if (!$user) {
                return $this->jsonResponse($response, 404, ['error' => 'Không tìm thấy người dùng']);
            }

            return $this->jsonResponse($response, 200, ['data' => $user]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    // === ADMIN: CẬP NHẬT NGƯỜI DÙNG ===
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID người dùng']);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->jsonResponse($response, 400, ['error' => 'Dữ liệu không hợp lệ']);
            }

            $result = $this->model->updateUser($id, $data);
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

    // === ADMIN: XÓA NGƯỜI DÙNG ===
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            if (!$id) {
                return $this->jsonResponse($response, 400, ['error' => 'Thiếu ID người dùng']);
            }

            $result = $this->model->deleteUser($id);
            return $this->jsonResponse($response, 200, $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    // === JSON RESPONSE HELPER ===
    private function jsonResponse(Response $response, int $status, array $data): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json);
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}