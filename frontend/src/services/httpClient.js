const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || '/backend').replace(/\/$/, '');

export function buildApiUrl(path) {
  return `${API_BASE_URL}${path}`;
}

async function parseError(response, fallbackMessage) {
  try {
    const data = await response.json();
    if (data?.message) return data.message;
  } catch {
    // ignore JSON parse error
  }
  return fallbackMessage;
}

export async function apiRequest(path, options = {}, fallbackMessage = '요청 처리에 실패했습니다.') {
  const response = await fetch(buildApiUrl(path), {
    credentials: 'include',
    ...options
  });

  if (!response.ok) {
    throw new Error(await parseError(response, fallbackMessage));
  }

  return await response.json();
}
