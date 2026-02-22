const calendarGrid = document.getElementById("calendarGrid");
const monthTitle = document.getElementById("monthTitle");
const memoInput = document.getElementById("memoInput");
const editBtn = document.querySelector(".btn-outline"); // 수정 버튼
const saveBtn = document.querySelector(".btn-primary"); // 저장 버튼

// 현재 선택된 날짜를 추적하기 위한 변수
let currentSelectedDay = String(new Date().getDate()).padStart(2, '0');

// 1. 날짜별 데이터
const calendarData = {
  "02": {
    name: "Classic Blue",
    number: "19-4052",
    keywords: ["#신뢰", "#안정", "#평온"],
    color: "#3f5f73",
    memo: "오늘은 마음이 아주 차분한 날이었다."
  },
  "05": {
    name: "Pale Dogwood",
    number: "13-1404",
    keywords: ["#부드러움", "#평화", "#순수"],
    color: "#fea1b8",
    memo: "벚꽃 같은 기분이 드는 하루!"
  }
};

// 초기 설정
const date = new Date();
const year = date.getFullYear();
const month = date.getMonth();
monthTitle.textContent = `${String(month + 1).padStart(2, '0')}월`;

const firstDay = new Date(year, month, 1).getDay();
const lastDate = new Date(year, month + 1, 0).getDate();

// 캘린더 생성
for (let day = 1; day <= lastDate; day++) {
  const dayEl = document.createElement("div");
  dayEl.classList.add("calendar-day");
  const formattedDay = String(day).padStart(2, '0');
  
  dayEl.innerHTML = `<span>${formattedDay}</span><div class="day-dot"></div>`;
  
  const dot = dayEl.querySelector(".day-dot");
  if (calendarData[formattedDay]) {
    dot.style.backgroundColor = calendarData[formattedDay].color;
  }

  dayEl.addEventListener("click", () => {
    // 수정 중일 때 날짜를 바꾸면 편집 모드 강제 종료
    disableEditMode();
    
    document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected'));
    dayEl.classList.add('selected');
    currentSelectedDay = formattedDay;
    updateDailyCard(formattedDay);
  });

  calendarGrid.appendChild(dayEl);

  if (day === parseInt(currentSelectedDay)) {
    dayEl.classList.add('selected');
    updateDailyCard(currentSelectedDay);
  }
}

// 2. 카드 업데이트 함수
function updateDailyCard(day) {
  const data = calendarData[day];
  if (data) {
    document.querySelector(".tone-text h3").textContent = data.name;
    document.querySelector(".tone-text p").textContent = `${data.number}(팬톤 컬러넘버)`;
    document.querySelector(".tone-color-preview").style.background = data.color;
    memoInput.value = data.memo;
    document.querySelector(".keyword-tags").innerHTML = data.keywords.map(k => `<span>${k}</span>`).join("");
    document.getElementById("currentCount").textContent = data.memo.length;
  } else {
    document.querySelector(".tone-text h3").textContent = "기록 없음";
    memoInput.value = "";
    document.getElementById("currentCount").textContent = 0;
  }
}

// --- 추가된 편집/저장 로직 ---

// 수정 버튼 클릭 시
editBtn.addEventListener("click", () => {
  memoInput.readOnly = false; // 읽기 전용 해제
  memoInput.focus();          // 바로 입력 가능하게 포커스
  memoInput.style.backgroundColor = "#fff"; // 편집 중임을 알리는 시각적 효과 (선택)
  editBtn.textContent = "수정 중";
});

// 저장 버튼 클릭 시
saveBtn.addEventListener("click", () => {
  if (memoInput.readOnly) return; // 이미 읽기 전용이면 무시

  // 1. 메모 데이터 업데이트
  if (!calendarData[currentSelectedDay]) {
    // 데이터가 없는 날짜에 처음 쓸 경우 객체 생성
    calendarData[currentSelectedDay] = { 
        name: "새로운 기록", 
        number: "00-0000", 
        keywords: ["#기록"], 
        color: "#d9d9d9", 
        memo: "" 
    };
  }
  calendarData[currentSelectedDay].memo = memoInput.value;

  // 2. 편집 모드 종료
  disableEditMode();
  alert("저장되었습니다!");
});

function disableEditMode() {
  memoInput.readOnly = true;
  memoInput.style.backgroundColor = "transparent";
  editBtn.textContent = "수정";
}

// 글자수 실시간 반영
memoInput.addEventListener("input", () => {
  document.getElementById("currentCount").textContent = memoInput.value.length;
});