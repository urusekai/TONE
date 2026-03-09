const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || '/backend').replace(/\/$/, '');

export function buildApiUrl(path) {
  return `${API_BASE_URL}${path}`;
}

async function parseError(response, fallbackMessage) {
  const rawText = await response.text();
  if (!rawText) return fallbackMessage;

  try {
    const data = JSON.parse(rawText);
    if (typeof data?.message === 'string' && data.message.trim()) {
      return data.message;
    }
  } catch {
    // Fall through to plain-text handling when the server returns non-JSON output.
  }

  if (rawText.trim()) {
    return rawText.trim();
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
