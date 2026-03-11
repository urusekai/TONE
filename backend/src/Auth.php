<?php

final class Auth
{
    public static function currentUserUuid(): string
    {
        return trim((string) ($_SESSION['user_uuid'] ?? ''));
    }

    public static function requireAuthenticatedUser(): string
    {
        $userUuid = self::currentUserUuid();
        if ($userUuid !== '') {
            return $userUuid;
        }

        app_error('로그인이 필요합니다.', 401);
    }
}
