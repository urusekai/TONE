function wait(ms) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

// TODO: 백엔드 연결 시 이 함수를 fetch/axios API 호출로 교체하면 된다.
export async function checkDuplicateId(id) {
  await wait(350);

  const takenIds = ['admin', 'test', 'tone'];
  const normalizedId = id.trim().toLowerCase();

  return {
    id,
    available: !takenIds.includes(normalizedId)
  };
}
