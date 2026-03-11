import { apiRequest, buildApiUrl } from '@/services/httpClient';

export async function checkDuplicateId(id) {
  return await apiRequest(
    `/api/auth/check-id.php?id=${encodeURIComponent(id.trim())}`,
    {},
    '중복확인에 실패했습니다.'
  );
}

export async function registerUser(payload) {
  return await apiRequest(
    '/api/auth/register.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    },
    '회원가입에 실패했습니다.'
  );
}

export async function loginUser(payload) {
  return await apiRequest(
    '/api/auth/login.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    },
    '로그인에 실패했습니다.'
  );
}

export async function logoutUser() {
  return await apiRequest(
    '/api/auth/logout.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    },
    '로그아웃에 실패했습니다.'
  );
}

export function startSocialLogin(provider) {
  if (!provider) {
    throw new Error('소셜 로그인 유형이 없습니다.');
  }

  window.location.href = buildApiUrl(`/api/auth/${provider}/login.php`);
}

export async function completeSocialSignup(payload) {
  return await apiRequest(
    '/api/auth/social/complete.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    },
    '소셜 회원가입에 실패했습니다.'
  );
}
