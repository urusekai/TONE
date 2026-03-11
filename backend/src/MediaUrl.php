<?php

class MediaUrl
{
    public static function buildCoverUrl(?string $filename): string
    {
        return self::buildUrl($filename, $_ENV['R2_COVER_PREFIX'] ?? 'covers');
    }

    public static function buildAudioUrl(?string $filename): string
    {
        return self::buildUrl($filename, $_ENV['R2_AUDIO_PREFIX'] ?? 'audio');
    }

    public static function buildVideoUrl(?string $filename): string
    {
        return self::buildUrl($filename, $_ENV['R2_VIDEO_PREFIX'] ?? 'video');
    }

    private static function buildUrl(?string $filename, string $prefix): string
    {
        $trimmedFilename = trim((string) $filename);
        if ($trimmedFilename === '') {
            return '';
        }

        $baseUrl = rtrim((string) ($_ENV['R2_PUBLIC_BASE_URL'] ?? ''), '/');
        if ($baseUrl === '') {
            return '';
        }

        $encodedPrefix = self::encodePath($prefix);
        $encodedFilename = rawurlencode($trimmedFilename);

        if ($encodedPrefix === '') {
            return $baseUrl . '/' . $encodedFilename;
        }

        return $baseUrl . '/' . $encodedPrefix . '/' . $encodedFilename;
    }

    private static function encodePath(string $path): string
    {
        $segments = array_filter(
            array_map('trim', explode('/', str_replace('\\', '/', $path))),
            static fn (string $segment): bool => $segment !== ''
        );

        if ($segments === []) {
            return '';
        }

        return implode('/', array_map('rawurlencode', $segments));
    }
}
