import { apiRequest } from '@/services/httpClient';

export async function fetchMyProfile() {
  return await apiRequest('/api/auth/me.php', {}, '사용자 정보를 불러오지 못했습니다.');
}

export async function updateMyProfile(payload) {
  return await apiRequest(
    '/api/auth/profile/update.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    },
    '프로필 수정에 실패했습니다.'
  );
}

export async function updateMyProfileColor(profileColor) {
  return await apiRequest(
    '/api/auth/profile/color.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ profileColor })
    },
    '프로필 색상 변경에 실패했습니다.'
  );
}

export async function withdrawMyAccount() {
  return await apiRequest(
    '/api/auth/withdraw.php',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    },
    '회원 탈퇴에 실패했습니다.'
  );
}
