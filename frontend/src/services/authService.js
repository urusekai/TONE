function wait(ms) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

const USE_MOCK_AUTH_API = true;

// TODO: 백엔드 연결 시 이 함수를 fetch/axios API 호출로 교체하면 된다.
export async function checkDuplicateId(id) {
  if (!USE_MOCK_AUTH_API) {
    // const response = await fetch('/api/auth/check-duplicate', { ... });
    // return await response.json();
  }

  await wait(350);

  const takenIds = ['admin', 'test', 'tone'];
  const normalizedId = id.trim().toLowerCase();

  return {
    id,
    available: !takenIds.includes(normalizedId)
  };
}

// TODO: 백엔드 연결 전까지 mock으로 회원가입 성공/실패를 흉내낸다.
// 실제 연동 시에는 fetch/axios 요청으로 교체하면 된다.
export async function registerUser(payload) {
  if (!USE_MOCK_AUTH_API) {
    // const response = await fetch('/api/auth/register', {
    //   method: 'POST',
    //   headers: { 'Content-Type': 'application/json' },
    //   body: JSON.stringify(payload)
    // });
    // if (!response.ok) throw new Error('회원가입 실패');
    // return await response.json();
  }

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
