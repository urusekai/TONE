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

        http_response_code(401);
        echo json_encode(['message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
