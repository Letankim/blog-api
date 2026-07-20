<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use App\Templates\EmailTemplate;
use App\Config\settings;

class MailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->setupSMTP();
    }

    private function setupSMTP(): void
{
    $host       = Settings::get('MAIL_HOST');
    $username   = Settings::get('MAIL_USERNAME');
    $password   = Settings::get('MAIL_PASSWORD');
    $encryption = Settings::get('MAIL_ENCRYPTION', 'tls');
    $port       = Settings::get('MAIL_PORT', 587);
    $fromEmail  = Settings::get('MAIL_FROM_EMAIL');  
    $fromName   = Settings::get('MAIL_FROM_NAME', '3DO Blog');

    if (empty($host) || empty($username) || empty($password) || empty($fromEmail)) {
        throw new \RuntimeException("Cấu hình email không đầy đủ. Kiểm tra .env (MAIL_*)");
    }

    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        throw new \RuntimeException("FROM_EMAIL không hợp lệ: $fromEmail");
    }

    $this->mailer->CharSet = 'UTF-8';
    $this->mailer->Encoding = 'base64';
    $this->mailer->isSMTP();
    $this->mailer->Host       = $host;
    $this->mailer->SMTPAuth   = true;
    $this->mailer->Username   = $username;
    $this->mailer->Password   = $password;
    $this->mailer->SMTPSecure = $encryption;
    $this->mailer->Port       = $port;
    $this->mailer->setFrom($fromEmail, $fromName);
}

    public function send(string $to, string $subject, string $htmlBody, string $plainText = ''): bool
    {
        try {
            // === RESET TRƯỚC KHI GỬI (QUAN TRỌNG!) ===
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $htmlBody;
            $this->mailer->AltBody = $plainText ?: strip_tags($htmlBody);

            $this->mailer->send();
            return true;
        } catch (\Exception $e) {
            error_log("MailService Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendActivationEmail(string $to, string $fullname, string $activationLink): bool
    {
        $template = EmailTemplate::getActivationEmail($fullname, $activationLink);
        return $this->send($to, 'Kích Hoạt Tài Khoản - 3DO Blog', $template['html'], $template['plainText']);
    }

    public function sendPasswordResetEmail(string $to, string $fullname, string $newPassword): bool
    {
        $template = EmailTemplate::getPasswordResetEmail($fullname, $newPassword);
        return $this->send($to, 'Đặt Lại Mật Khẩu - 3DO Blog', $template['html'], $template['plainText']);
    }

    public function sendOTPEmail(string $to, string $fullname, string $otp): bool
    {
        $template = EmailTemplate::getOTPEmail($fullname, $otp);
        return $this->send($to, 'Mã OTP Xác Thực - 3DO Blog', $template['html'], $template['plainText']);
    }
}