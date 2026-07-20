<?php
namespace App\Templates;

class EmailTemplate
{
    private static string $logoUrl = 'https://cuuho.3docorp.vn/logo.png';
    private static string $websiteUrl = 'https://3docorp.vn/blog';
    private static string $address = '600 Nguyễn Văn Cừ, An Bình, Ninh Kiều, Cần Thơ';
    private static string $phone = '0865341745';
    private static string $email = 'support@3docorp.vn';

    // === 1. KÍCH HOẠT TÀI KHOẢN ===
    public static function getActivationEmail(string $fullname, string $activationLink): array
    {
        $html = self::renderTemplate('Kích Hoạt Tài Khoản', $fullname, "
            <p class='message'>
                Cảm ơn bạn đã đăng ký tài khoản tại <strong>3DO Blog</strong>. 
                Vui lòng nhấn vào nút dưới đây để kích hoạt tài khoản của bạn:
            </p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$activationLink}' class='cta-button'>Kích Hoạt Ngay</a>
            </div>
            <p class='message' style='font-size: 14px;'>
                Liên kết sẽ hết hạn sau <strong>30 phút</strong>. 
                Nếu bạn không yêu cầu, vui lòng bỏ qua email này.
            </p>
        ");

        $plainText = "Kính gửi {$fullname},\n\n"
            . "Cảm ơn bạn đã đăng ký. Vui lòng truy cập liên kết để kích hoạt tài khoản:\n"
            . "{$activationLink}\n\n"
            . "Liên kết hết hạn sau 30 phút.\n\n"
            . "---\n"
            . self::getFooterText();

        return ['html' => $html, 'plainText' => $plainText];
    }

    // === 2. QUÊN MẬT KHẨU ===
    public static function getPasswordResetEmail(string $fullname, string $newPassword): array
    {
        $html = self::renderTemplate('Đặt Lại Mật Khẩu', $fullname, "
            <p class='message'>
                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
                Đây là mật khẩu tạm thời mới:
            </p>
            <div class='password-box'>
                <span class='password-label'>Mật Khẩu Tạm Thời</span>
                <div class='password-value'>{$newPassword}</div>
            </div>
            <div class='warning'>
                <strong>Lưu ý:</strong> Hãy đăng nhập ngay và đổi mật khẩu trong phần cài đặt.
            </div>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . self::$websiteUrl . "/login' class='cta-button'>Đăng Nhập Ngay</a>
            </div>
        ");

        $plainText = "Kính gửi {$fullname},\n\n"
            . "Mật khẩu tạm thời của bạn là: {$newPassword}\n\n"
            . "Đăng nhập tại: " . self::$websiteUrl . "/login\n\n"
            . "---\n"
            . self::getFooterText();

        return ['html' => $html, 'plainText' => $plainText];
    }

    // === 3. GỬI OTP ===
    public static function getOTPEmail(string $fullname, string $otp): array
    {
        $html = self::renderTemplate('Mã OTP Xác Thực', $fullname, "
            <p class='message'>
                Mã OTP của bạn để xác thực tài khoản hoặc giao dịch là:
            </p>
            <div class='password-box' style='background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%); border-left: 4px solid #f59e0b;'>
                <span class='password-label'>Mã OTP</span>
                <div class='password-value' style='color: #d97706; font-size: 32px;'>{$otp}</div>
            </div>
            <div class='warning'>
                <strong>Bảo mật:</strong> Mã OTP chỉ có hiệu lực trong <strong>5 phút</strong>. 
                Không chia sẻ mã này với bất kỳ ai.
            </div>
            <p class='message' style='font-size: 14px;'>
                Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.
            </p>
        ");

        $plainText = "Kính gửi {$fullname},\n\n"
            . "Mã OTP của bạn là: {$otp}\n\n"
            . "Hiệu lực trong 5 phút.\n\n"
            . "---\n"
            . self::getFooterText();

        return ['html' => $html, 'plainText' => $plainText];
    }

    // === TEMPLATE CHUNG ===
    private static function renderTemplate(string $title, string $fullname, string $customContent): string
    {
        // Lấy giá trị class property ra biến để nội suy trong heredoc
        $logoUrl = self::$logoUrl;
        $address = self::$address;
        $phone = self::$phone;
        $email = self::$email;
        $websiteUrl = self::$websiteUrl;

        return <<<EOT
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - 3DO Blog</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; padding: 20px; }
        .email-container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(6, 182, 212, 0.15); overflow: hidden; }
        .header { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); padding: 40px 20px; text-align: center; }
        .logo { max-width: 120px; height: auto; margin-bottom: 20px; }
        .header h1 { color: #fff; font-size: 28px; font-weight: 700; }
        .header p { color: #ecf9fd; font-size: 14px; margin-top: 8px; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 16px; color: #1e293b; margin-bottom: 20px; line-height: 1.6; }
        .greeting strong { color: #06b6d4; }
        .message { font-size: 15px; color: #475569; line-height: 1.8; margin-bottom: 20px; }
        .password-box { background: linear-gradient(135deg, #ecf9fd 0%, #f0f9fc 100%); border-left: 4px solid #06b6d4; padding: 20px; border-radius: 8px; margin: 30px 0; text-align: center; }
        .password-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .password-value { font-size: 28px; font-weight: 700; color: #06b6d4; font-family: 'Courier New', monospace; letter-spacing: 2px; }
        .warning { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 20px 0; font-size: 14px; color: #92400e; line-height: 1.6; }
        .cta-button { display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3); transition: all 0.2s; }
        .cta-button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4); }
        .divider { height: 1px; background: #e2e8f0; margin: 30px 0; }
        .footer { background: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer-title { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 15px; }
        .contact-info { font-size: 13px; color: #64748b; line-height: 1.8; }
        .contact-info a { color: #06b6d4; text-decoration: none; }
        .copyright { font-size: 12px; color: #94a3b8; margin-top: 15px; }
        .icon { display: inline-block; margin-right: 8px; font-weight: bold; color: #06b6d4; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{$logoUrl}" alt="3DO Blog" class="logo">
            <h1>{$title}</h1>
            <p>Nền tảng viết blog và chia sẻ tri thức</p>
        </div>
        <div class="content">
            <p class="greeting">Kính gửi <strong>{$fullname}</strong>,</p>
            {$customContent}
        </div>
        <div class="divider"></div>
        <div class="footer">
            <div class="footer-title">Thông Tin Liên Hệ</div>
            <div class="contact-info">
                <div><span class="icon">📍</span> {$address}</div>
                <div><span class="icon">📞</span> <a href="tel:{$phone}">{$phone}</a></div>
                <div><span class="icon">✉️</span> <a href="mailto:{$email}">{$email}</a></div>
                <div><span class="icon">🌐</span> <a href="{$websiteUrl}">{$websiteUrl}</a></div>
            </div>
            <div class="copyright">
                &copy; 2025 3DO Blog. Bản quyền thuộc về 3DO Corporation.
            </div>
        </div>
    </div>
</body>
</html>
EOT;
    }

    private static function getFooterText(): string
    {
        return "Liên hệ:\n"
            . "Address: " . self::$address . "\n"
            . "Phone: " . self::$phone . "\n"
            . "Email: " . self::$email . "\n"
            . "Website: " . self::$websiteUrl . "\n\n"
            . "© 2025 3DO Blog.";
    }
}
