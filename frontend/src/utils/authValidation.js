const ID_PATTERN = /^[a-zA-Z0-9_]{4,20}$/;
const PROFILE_COLOR_PATTERN = /^#[0-9A-Fa-f]{6}$/;

function getTextLength(value) {
  return Array.from(value).length;
}

function isValidEmail(email) {
  if (typeof document === 'undefined') {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  const input = document.createElement('input');
  input.type = 'email';
  input.value = email;
  return input.checkValidity();
}

export function validateRegisterPayload(payload, options = {}) {
  const { requireProfileColor = true } = options;
  const id = String(payload.id ?? '').trim();
  const email = String(payload.email ?? '').trim();
  const password = String(payload.password ?? '');
  const nickname = String(payload.nickname ?? '').trim();
  const profileColor = String(payload.profileColor ?? '').trim();

  if (id === '' || email === '' || password === '' || nickname === '') {
    return '필수 항목을 모두 입력해주세요.';
  }

  if (!ID_PATTERN.test(id)) {
    return '아이디는 4~20자의 영문, 숫자, 언더스코어(_)만 가능합니다.';
  }

  if (!isValidEmail(email)) {
    return '이메일 형식이 올바르지 않습니다.';
  }

  if (password.length < 8) {
    return '비밀번호는 8자 이상이어야 합니다.';
  }

  if (getTextLength(nickname) < 2 || getTextLength(nickname) > 5) {
    return '닉네임은 2~5자로 입력해주세요.';
  }

  if (requireProfileColor) {
    if (profileColor === '') {
      return '프로필 색상을 선택해주세요.';
    }

    if (!PROFILE_COLOR_PATTERN.test(profileColor)) {
      return '프로필 색상 형식이 올바르지 않습니다.';
    }
  }

  return null;
}

export function validateSocialSignupPayload(payload) {
  const provider = String(payload.provider ?? '').trim();
  const providerId = String(payload.providerId ?? '').trim();
  const email = String(payload.email ?? '').trim();
  const nickname = String(payload.nickname ?? '').trim();
  const profileColor = String(payload.profileColor ?? '').trim();

  if (!['kakao', 'google', 'naver'].includes(provider)) {
    return '지원하지 않는 소셜 로그인 유형입니다.';
  }

  if (providerId === '') {
    return '소셜 사용자 정보가 올바르지 않습니다.';
  }

  if (!isValidEmail(email)) {
    return '이메일 형식이 올바르지 않습니다.';
  }

  if (getTextLength(nickname) < 2 || getTextLength(nickname) > 5) {
    return '닉네임은 2~5자로 입력해주세요.';
  }

  if (!PROFILE_COLOR_PATTERN.test(profileColor)) {
    return '프로필 색상을 선택해주세요.';
  }

  return null;
}

export function validateProfileUpdatePayload(payload) {
  const email = String(payload.email ?? '').trim();
  const nickname = String(payload.nickname ?? '').trim();
  const password = String(payload.password ?? '');
  const profileColor = String(payload.profileColor ?? '').trim();

  if (email === '' || nickname === '') {
    return '이메일과 닉네임은 필수입니다.';
  }

  if (!isValidEmail(email)) {
    return '이메일 형식이 올바르지 않습니다.';
  }

  if (getTextLength(nickname) < 2 || getTextLength(nickname) > 5) {
    return '닉네임은 2~5자로 입력해주세요.';
  }

  if (profileColor !== '' && !PROFILE_COLOR_PATTERN.test(profileColor)) {
    return '프로필 색상 형식이 올바르지 않습니다.';
  }

  if (password !== '' && password.length < 8) {
    return '비밀번호는 8자 이상이어야 합니다.';
  }

  return null;
}

export function validateRegisterId(id) {
  const trimmedId = String(id ?? '').trim();

  if (trimmedId === '') {
    return '아이디를 먼저 입력해주세요.';
  }

  if (!ID_PATTERN.test(trimmedId)) {
    return '아이디는 4~20자의 영문, 숫자, 언더스코어(_)만 가능합니다.';
  }

  return null;
}
