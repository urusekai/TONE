import { apiRequest } from '@/services/httpClient';

export async function fetchPlaylistDetail(playlistId) {
  const normalizedPlaylistId = String(playlistId || '').trim();
  if (!normalizedPlaylistId) {
    throw new Error('플레이리스트 정보가 없습니다.');
  }

  return await apiRequest(
    `/api/playlist/detail.php?id=${encodeURIComponent(normalizedPlaylistId)}`,
    {},
    '플레이리스트 정보를 불러오지 못했습니다.'
  );
}

export function toPlayerPlaylist(value) {
  return {
    id: String(value?.id || ''),
    pantone_code: String(value?.pantone_code || ''),
    color_name: String(value?.color_name || ''),
    color_hex: String(value?.color_hex || '#D5E1E8'),
    liked: Boolean(value?.liked),
    saved: Boolean(value?.saved),
    like_count: Number(value?.like_count || 0)
  };
}

export function toPlayerTrack(track, playlist) {
  return {
    id: String(track?.id || ''),
    title: String(track?.title || ''),
    artist: String(track?.artist || ''),
    cover_url: String(track?.cover_url || ''),
    audio_url: String(track?.audio_url || ''),
    video_url: String(track?.video_url || ''),
    duration_ms: Number(track?.duration_ms || 0),
    color_name: String(playlist?.color_name || ''),
    pantone_code: String(playlist?.pantone_code || ''),
    color_hex: String(playlist?.color_hex || '#D5E1E8')
  };
}

export async function playPlaylistFirstTrack(player, playlistId, options = {}) {
  if (!player) return null;

  const detailResult = await fetchPlaylistDetail(playlistId);
  const playlist = detailResult?.playlist ?? null;
  const tracks = Array.isArray(detailResult?.tracks) ? detailResult.tracks : [];

  if (!playlist?.id || tracks.length < 1) {
    throw new Error('재생할 트랙을 찾을 수 없습니다.');
  }

  player.setCurrentPlaylist(toPlayerPlaylist(playlist));
  player.setQueue(
    tracks.map((track) => toPlayerTrack(track, playlist)),
    {
      startIndex: 0,
      autoplay: options.autoplay ?? true,
      open_mode: options.openMode ?? 'main'
    }
  );

  return detailResult;
}
