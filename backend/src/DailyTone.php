<?php

class DailyTone
{
    public static function getTodayKey(): string
    {
        return (new DateTimeImmutable('today'))->format('Y-m-d');
    }

    public static function ensureTodayEntry(PDO $pdo, string $userUuid, ?string $entryDate = null): array
    {
        $resolvedEntryDate = trim((string) ($entryDate ?? self::getTodayKey()));
        $existingEntry = self::findEntry($pdo, $userUuid, $resolvedEntryDate);

        if ($existingEntry) {
            return $existingEntry;
        }

        $playlistId = self::pickRandomPlaylistId($pdo);
        $saveStmt = $pdo->prepare(
            'INSERT INTO calendar_entries (user_uuid, entry_date, playlist_id, memo)
             VALUES (:user_uuid, :entry_date, :playlist_id, \'\')
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $saveStmt->execute([
            'user_uuid' => $userUuid,
            'entry_date' => $resolvedEntryDate,
            'playlist_id' => $playlistId
        ]);

        $savedEntry = self::findEntry($pdo, $userUuid, $resolvedEntryDate);
        if (!$savedEntry) {
            throw new RuntimeException('오늘의 톤 기록을 확인할 수 없습니다.');
        }

        return $savedEntry;
    }

    private static function findEntry(PDO $pdo, string $userUuid, string $entryDate): ?array
    {
        $entryStmt = $pdo->prepare(
            'SELECT id, entry_date, playlist_id, memo
             FROM calendar_entries
             WHERE user_uuid = :user_uuid
               AND entry_date = :entry_date
             LIMIT 1'
        );
        $entryStmt->execute([
            'user_uuid' => $userUuid,
            'entry_date' => $entryDate
        ]);
        $entry = $entryStmt->fetch();

        return $entry ?: null;
    }

    private static function pickRandomPlaylistId(PDO $pdo): int
    {
        $playlistStmt = $pdo->query(
            'SELECT id
             FROM playlists
             ORDER BY RAND()
             LIMIT 1'
        );
        $playlist = $playlistStmt->fetch();

        if (!$playlist) {
            throw new RuntimeException('플레이리스트를 찾을 수 없습니다.');
        }

        return (int) $playlist['id'];
    }
}
