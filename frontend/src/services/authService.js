import { apiRequest, buildApiUrl } from '@/services/httpClient';

function wait(ms) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

const USE_MOCK_AUTH_API = import.meta.env.VITE_USE_MOCK_AUTH === 'true';

export async function checkDuplicateId(id) {
  if (USE_MOCK_AUTH_API) {
    await wait(350);

    const takenIds = ['admin', 'test', 'tone'];
    const normalizedId = id.trim().toLowerCase();

    return {
      id,
      available: !takenIds.includes(normalizedId)
    };
  }

  return await apiRequest(
    `/api/auth/check-id.php?id=${encodeURIComponent(id.trim())}`,
    {},
    '중복확인에 실패했습니다.'
  );
}

export async function registerUser(payload) {
  if (USE_MOCK_AUTH_API) {
    await wait(500);

    if (!payload.profileColor) {
      throw new Error('프로필 색상을 선택해주세요.');
    }

    return {
      success: true,
      user: {
        id: payload.id,
        email: payload.email,
        nickname: payload.nickname,
        profileColor: payload.profileColor,
        membershipPlan: 'free'
      }
    };
  }

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
  if (USE_MOCK_AUTH_API) {
    await wait(350);

    if (!payload.id?.trim() || !payload.password) {
      throw new Error('아이디와 비밀번호를 입력해주세요.');
    }

    return {
      success: true,
      user: {
        user_uuid: 'mock-user-uuid',
        id: payload.id.trim(),
        email: 'mock@tone.local',
        nickname: 'Mock',
        provider: 'local',
        profileColor: '#B7AEA6',
        membershipPlan: 'free'
      }
    };
  }

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
  if (USE_MOCK_AUTH_API) {
    await wait(150);
    return { success: true };
  }

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
  if (USE_MOCK_AUTH_API) {
    await wait(400);

    if (!payload.email || !payload.nickname || !payload.profileColor) {
      throw new Error('이메일, 닉네임, 프로필 색상을 입력해주세요.');
    }

    return {
      success: true,
      user: {
        user_uuid: 'mock-social-user',
        id: null,
        email: payload.email,
        nickname: payload.nickname,
        provider: payload.provider || 'kakao',
        profileColor: payload.profileColor,
        membershipPlan: 'free'
      }
    };
  }

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
