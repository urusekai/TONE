import { apiRequest } from '@/services/httpClient';

export async function fetchCalendarEntries(month) {
  return await apiRequest(
    `/api/calendar/list.php?month=${encodeURIComponent(month)}`,
    {},
    '캘린더 기록을 불러오지 못했습니다.'
  );
}

export async function saveCalendarEntry(payload) {
  return await apiRequest(
    '/api/calendar/save.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    },
    '캘린더 기록 저장에 실패했습니다.'
  );
}

export async function ensureTodayCalendarEntry() {
  return await apiRequest(
    '/api/calendar/ensure-today.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({})
    },
    '오늘 캘린더 색 기록에 실패했습니다.'
  );
}

export async function fetchTodayCalendarPlaylist() {
  const result = await apiRequest('/api/playlist/daily.php', {}, '오늘의 톤을 불러오지 못했습니다.');
  const playlist = result?.playlist;
  const track = playlist?.track;

  if (!playlist?.id) {
    throw new Error('오늘의 톤 정보를 찾을 수 없습니다.');
  }

  return {
    id: null,
    entryDate: result?.date || '',
    memo: '',
    playlistId: playlist.id,
    name: playlist.color_name,
    number: playlist.pantone_code,
    color: playlist.color_hex,
    music: {
      title: track?.title || '',
      artist: track?.artist || '',
      cover: track?.cover_url || null
    }
  };
}
