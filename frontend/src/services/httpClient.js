import axios from 'axios';

const API_BASE_URL = '/backend';

function normalizePath(path) {
  return String(path || '').replace(/^\/+/, '');
}

export function buildApiUrl(path) {
  return `${API_BASE_URL}/${normalizePath(path)}`;
}

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true
});

function parseError(error, fallbackMessage) {
  const message = error?.response?.data?.message;
  if (typeof message === 'string' && message.trim()) {
    return message;
  }

  return fallbackMessage;
}

function parseAppError(data, fallbackMessage) {
  const message = data?.message;
  if (typeof message === 'string' && message.trim()) {
    return message;
  }

  return fallbackMessage;
}

export async function apiRequest(
  path,
  options = {},
  fallbackMessage = '요청 처리에 실패했습니다.'
) {
  const { body, ...restOptions } = options;

  try {
    const response = await apiClient.request({
      url: `/${normalizePath(path)}`,
      data: body,
      ...restOptions
    });

    if (
      response.data &&
      typeof response.data === 'object' &&
      response.data.success === false
    ) {
      throw new Error(parseAppError(response.data, fallbackMessage));
    }

    return response.data;
  } catch (error) {
    if (error instanceof Error && !error?.response) {
      throw error;
    }

    throw new Error(parseError(error, fallbackMessage), { cause: error });
  }
}
