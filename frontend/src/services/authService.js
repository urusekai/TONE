function wait(ms) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

const USE_MOCK_AUTH_API = import.meta.env.VITE_USE_MOCK_AUTH === 'true';
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/TONE/backend';

async function parseError(response, fallbackMessage) {
  try {
    const data = await response.json();
    if (data?.message) return data.message;
  } catch {
    // ignore JSON parse error
  }
  return fallbackMessage;
}

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

  const response = await fetch(
    `${API_BASE_URL}/api/auth/check-id.php?id=${encodeURIComponent(id.trim())}`
  );

  if (!response.ok) {
    throw new Error(await parseError(response, '중복확인에 실패했습니다.'));
  }

  return await response.json();
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
        profileColor: payload.profileColor
      }
    };
  }

  const response = await fetch(`${API_BASE_URL}/api/auth/register.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });

  if (!response.ok) {
    throw new Error(await parseError(response, '회원가입에 실패했습니다.'));
  }

  return await response.json();
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
        provider: 'local'
      }
    };
  }

  const response = await fetch(`${API_BASE_URL}/api/auth/login.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });

  if (!response.ok) {
    throw new Error(await parseError(response, '로그인에 실패했습니다.'));
  }

  return await response.json();
}

export async function logoutUser() {
  if (USE_MOCK_AUTH_API) {
    await wait(150);
    return { success: true };
  }

  const response = await fetch(`${API_BASE_URL}/api/auth/logout.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  });

  if (!response.ok) {
    throw new Error(await parseError(response, '로그아웃에 실패했습니다.'));
  }

  return await response.json();
}

export function startSocialLogin(provider) {
  if (!provider) {
    throw new Error('소셜 로그인 유형이 없습니다.');
  }

  window.location.href = `${API_BASE_URL}/api/auth/${provider}/login.php`;
}

export async function completeSocialSignup(payload) {
  if (USE_MOCK_AUTH_API) {
    await wait(400);

    if (!payload.email || !payload.nickname) {
      throw new Error('이메일과 닉네임을 입력해주세요.');
    }

    return {
      success: true,
      user: {
        user_uuid: 'mock-social-user',
        id: null,
        email: payload.email,
        nickname: payload.nickname,
        provider: payload.provider || 'kakao'
      }
    };
  }

  const response = await fetch(`${API_BASE_URL}/api/auth/social/complete.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });

  if (!response.ok) {
    throw new Error(await parseError(response, '소셜 회원가입에 실패했습니다.'));
  }

  return await response.json();
}
