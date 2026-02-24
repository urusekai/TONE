const calendarGrid = document.getElementById('calendarGrid');
const monthTitle = document.getElementById('monthTitle');
const memoInput = document.getElementById('memoInput');
const editBtn = document.querySelector('.btn-outline');
const saveBtn = document.querySelector('.btn-primary');
const prevBtn = document.getElementById('prevMonth');
const nextBtn = document.getElementById('nextMonth');

// [상태 관리] 현재 화면에 보여줄 기준 날짜 (실시간 반영)
let viewDate = new Date();
// 현재 선택된 날짜 키 (형식: "MM-DD")
let currentSelectedKey = `${String(viewDate.getMonth() + 1).padStart(2, '0')}-${String(viewDate.getDate()).padStart(2, '0')}`;

// 1. 날짜별 데이터 (월-일 형식으로 키값 변경 추천)
const calendarData = {
  '02-01': {
    name: 'Viva Magenta',
    number: '18-1750',
    keywords: ['#대담함', '#자신감', '#비범함'],
    color: '#BB2649',
    memo: '멈추고 싶지 않은 열기로 가득 찬, 나 자신이 주인공인 날.'
  },
  '02-02': {
    name: 'Classic Blue',
    number: '19-4052',
    keywords: ['#신뢰', '#안정', '#평온'],
    color: '#0F4C81',
    memo: '오늘은 마음이 아주 차분한 날이었다.'
  },
  '02-03': {
    name: 'Living Coral',
    number: '16-1546',
    keywords: ['#친근함', '#활기', '#다정함'],
    color: '#FF6F61',
    memo: '기분 좋은 리듬이 발끝에서부터 머리끝까지 간질거린다.'
  },
  '02-04': {
    name: 'Ultra Violet',
    number: '18-3838',
    keywords: ['#세련된', '#창의적', '#리드미컬'],
    color: '#5F4B8B',
    memo: '복잡한 생각은 비워두고, 오직 숨소리에만 집중한 시간.'
  },
  '02-05': {
    name: 'Pale Dogwood',
    number: '13-1404',
    keywords: ['#부드러움', '#평화'],
    color: '#fea1b8',
    memo: '벚꽃 같은 기분이 드는 하루!'
  },
  '02-06': {
    name: 'Cyber Yellow',
    number: '14-0760',
    keywords: ['#아드레날린', '#광채', '#하이라이트'],
    color: '#FFD300',
    memo: '심장 박동에 맞춰 세상이 함께 움직이는 기분이 든다.'
  },
  '02-07': {
    name: 'Warm Sand',
    number: '15-1214',
    keywords: ['#나른한', '#아늑함', '#편안함'],
    color: '#C5B097',
    memo: '서늘한 여백 속에 머물며 마음을 정돈한 고요한 오후.'
  },
  // 3월 데이터 예시
  '03-01': {
    name: 'Greenery',
    number: '15-0343',
    keywords: ['#새로움', '#희망'],
    color: '#88B04B',
    memo: '3월의 첫날, 활기차게 시작!'
  }
};

// 2. 달력 생성 함수
function renderCalendar() {
  calendarGrid.innerHTML = ''; // 기존 달력 제거

  const year = viewDate.getFullYear();
  const month = viewDate.getMonth();

  // 헤더 제목 업데이트 (예: 02월)
  monthTitle.textContent = `${String(month + 1).padStart(2, '0')}월`;

  const firstDay = new Date(year, month, 1).getDay();
  const lastDate = new Date(year, month + 1, 0).getDate();

  // 요일 빈칸 채우기
  for (let i = 0; i < firstDay; i++) {
    const empty = document.createElement('div');
    calendarGrid.appendChild(empty);
  }

  // 날짜 생성
  for (let day = 1; day <= lastDate; day++) {
    const dayEl = document.createElement('div');
    dayEl.classList.add('calendar-day');
    const formattedMonth = String(month + 1).padStart(2, '0');
    const formattedDay = String(day).padStart(2, '0');
    const dateKey = `${formattedMonth}-${formattedDay}`;

    dayEl.innerHTML = `<span>${formattedDay}</span><div class="day-dot"></div>`;

    // 해당 날짜에 데이터가 있으면 도트 색상 적용
    const dot = dayEl.querySelector('.day-dot');
    if (calendarData[dateKey]) {
      dot.style.backgroundColor = calendarData[dateKey].color;
    }

    // 선택 상태 유지
    if (dateKey === currentSelectedKey) {
      dayEl.classList.add('selected');
      updateDailyCard(dateKey);
    }

    dayEl.addEventListener('click', () => {
      disableEditMode();
      document.querySelectorAll('.calendar-day').forEach((el) => el.classList.remove('selected'));
      dayEl.classList.add('selected');
      currentSelectedKey = dateKey;
      updateDailyCard(dateKey);
    });

    calendarGrid.appendChild(dayEl);
  }
}

// 3. 카드 업데이트 함수
function updateDailyCard(key) {
  const data = calendarData[key];
  if (data) {
    document.querySelector('.tone-text h3').textContent = data.name;
    document.querySelector('.tone-text p').textContent = `${data.number}(팬톤 컬러넘버)`;
    document.querySelector('.tone-color-preview').style.background = data.color;
    memoInput.value = data.memo;
    document.querySelector('.keyword-tags').innerHTML = data.keywords
      .map((k) => `<span>${k}</span>`)
      .join('');
    document.getElementById('currentCount').textContent = data.memo.length;
  } else {
    document.querySelector('.tone-text h3').textContent = '기록 없음';
    document.querySelector('.tone-text p').textContent = '00-0000(팬톤 컬러넘버)';
    document.querySelector('.tone-color-preview').style.background = '#d9d9d9';
    memoInput.value = '';
    document.querySelector('.keyword-tags').innerHTML = '<span>#기록없음</span>';
    document.getElementById('currentCount').textContent = 0;
  }
}

// 4. 이벤트 리스너 (월 이동)
prevBtn.addEventListener('click', () => {
  viewDate.setMonth(viewDate.getMonth() - 1);
  renderCalendar();
});

nextBtn.addEventListener('click', () => {
  viewDate.setMonth(viewDate.getMonth() + 1);
  renderCalendar();
});

// 수정/저장 로직 (기존 코드 유지)
editBtn.addEventListener('click', () => {
  memoInput.readOnly = false;
  memoInput.focus();
  editBtn.textContent = '수정 중';
});

saveBtn.addEventListener('click', () => {
  if (memoInput.readOnly) return;
  if (!calendarData[currentSelectedKey]) {
    calendarData[currentSelectedKey] = {
      name: '새로운 기록',
      number: '00-0000',
      keywords: ['#기록'],
      color: '#d9d9d9',
      memo: ''
    };
  }
  calendarData[currentSelectedKey].memo = memoInput.value;
  disableEditMode();
  alert('저장되었습니다!');
});

function disableEditMode() {
  memoInput.readOnly = true;
  editBtn.textContent = '수정';
}

memoInput.addEventListener('input', () => {
  document.getElementById('currentCount').textContent = memoInput.value.length;
});

// 초기 실행
renderCalendar();
