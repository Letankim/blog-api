<?php

namespace App\Validation;

use Respect\Validation\Validator as v;

class ValidationRules
{
    // ==================== CƠ BẢN ====================

    public static function uuid()
    {
        return v::uuid();
    }

    public static function stringNotEmpty(int $min = 1)
    {
        $rule = v::stringType()->notEmpty();
        return $min > 1 ? $rule->length($min, null) : $rule;
    }

    public static function optional($rule)
    {
        return v::optional($rule);
    }

    public static function optionalUuid()
    {
        return v::optional(self::uuid());
    }

    // ==================== NỘI DUNG ====================

    public static function content(int $min = 1, int $max = 1000)
    {
        return v::stringType()->notEmpty()->length($min, $max);
    }

    public static function title(int $min = 3, int $max = 255)
    {
        return self::content($min, $max);
    }

    public static function shortDescription(int $max = 500)
    {
        return v::optional(v::stringType()->length(1, $max));
    }

    // ==================== NGƯỜI DÙNG ====================

    public static function email()
    {
        return v::email()->notEmpty();
    }

    public static function phone()
    {
        return v::phone()->regex('/^(0[3|5|7|8|9])+([0-9]{8})\b/');
    }

    public static function username(int $min = 3, int $max = 50)
    {
        return v::alnum('_')->noWhitespace()->length($min, $max);
    }

    public static function password(int $min = 6)
    {
        return v::stringType()->length($min, null)->regex('/^(?=.*[a-zA-Z])(?=.*\d)/');
    }

    // ==================== DANH MỤC / SLUG ====================

    public static function name(int $min = 2, int $max = 100)
    {
        return v::stringType()->notEmpty()->length($min, $max);
    }

    public static function slug()
    {
        return v::slug()->length(3, 255);
    }

    // ==================== TRẠNG THÁI ====================

    public static function status(array $allowed)
    {
        return v::in($allowed);
    }

    // ==================== SỐ & GIÁ ====================

    public static function positiveInt()
    {
        return v::intType()->positive();
    }

    public static function price()
    {
        return v::number()->min(0);
    }

    // ==================== FILE / URL ====================

    public static function imageUrl()
    {
        return v::optional(v::url()->notEmpty());
    }

    public static function uploadedFile()
    {
        return v::uploaded();
    }

    // ==================== KHÁC ====================

    public static function json()
    {
        return v::json();
    }

    public static function voucherCode()
    {
        return v::alnum()->length(4, 20);
    }

    // ==================== KẾT HỢP (TÙY CHỌN) ====================

    public static function register()
    {
        return [
            'username' => self::username(),
            'email'    => self::email(),
            'password' => self::password(6),
            'phone'    => self::optional(self::phone())
        ];
    }

    public static function post()
    {
        return [
            'title'       => self::title(),
            'content'     => self::content(10, 10000),
            'user_id'     => self::uuid(),
            'category_id' => self::optionalUuid(),
            'status'      => self::status(['draft', 'published', 'archived'])
        ];
    }
}