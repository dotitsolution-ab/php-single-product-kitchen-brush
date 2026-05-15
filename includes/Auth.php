<?php

declare(strict_types=1);

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $stmt = db()->prepare('SELECT * FROM admin_users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int)$user['id'];
        $_SESSION['admin_user_name'] = (string)$user['name'];
        return true;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['admin_user_id']);
    }

    public static function userName(): string
    {
        return (string)($_SESSION['admin_user_name'] ?? 'Admin');
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            redirect('admin/login.php');
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_user_id'], $_SESSION['admin_user_name']);
        session_regenerate_id(true);
    }
}

