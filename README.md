<!-- logo -->
<p align="center">
  <img height="50" alt="로고아이콘" src="https://github.com/user-attachments/assets/3fc6e4fd-8389-49c9-949e-c5a81c711546" />
</p>
<h1 align="center">
  <img height="50" alt="TONE" src="https://github.com/user-attachments/assets/db5a5be2-9907-4588-a5fc-b882e17943eb" />
</h1>
<p align="center">
TONE은 사용자에게 매일 팬톤컬러 기반의 색상과 그에 맞는 플레이리스트를 제공하여<br/>
하루의 감정을 색과 음악으로 기록할 수 있는 뮤직 플랫폼입니다.
</p>

## 📂 디렉터리 구조

```
TONE/
│── .vscode/                # VSCode 설정 파일
│   └── settings.json
│
│── assets/                 # 정적 파일 폴더
│   ├── icons/              # 아이콘 이미지
│   └── images/             # 일반 이미지
│
│── css/                    # 페이지별 스타일 파일
│   ├── reset.css           # 기본 스타일 초기화
│   ├── common.css          # 공통 스타일
│   └── font.css            # 폰트 설정
│       ...                 # 페이지별 CSS 파일
│
│── js/                     # 페이지별 스크립트
│
│── index.html              # 스플래시(엔트리) 페이지
│── main.html               # 메인(For You) 페이지
│   ...                     # 페이지별 HTML 파일
│
│── .prettierrc             # 코드 포맷 설정 파일
```

## 📜 컨벤션

### 1. 브랜치 규칙

- `main` : 최종 배포/제출용
- `develop` : 개발 통합 브랜치
- `feature/브랜치명` : 기능 추가
- `refactor/브랜치명` : UI 수정, HTML/CSS 구조 변경

#### 브랜치명 예시

- `feature/calendar`
- `refactor/playlist`

### 2. 커밋 메시지 규칙

- `feat:` 기능 추가
- `refactor:` UI 수정, 구조 변경
- `fix:` 버그 수정

#### 커밋 메시지 예시

- `feat: 캘린더 페이지 구현`
- `refactor: 캘린더 페이지 레이아웃 수정`
- `fix: 플레이어 진행바 오류 수정`

## 🛠 작업 순서

### 1. develop 브랜치 최신화

```bash
git checkout develop
git pull origin develop
```

### 2. (처음 1회만) develop 브랜치를 기반으로 새 작업 브랜치 생성 후 이동

```bash
git checkout -b 브랜치명
```

> 예시: `git checkout -b feature/calendar`

### 3. 현재 브랜치가 작업할 브랜치가 맞는지 확인

```bash
git branch
```

> 현재 브랜치가 다르면 `git checkout 브랜치명` 으로 이동

### 4. 작업 후 커밋

```bash
git add .
git commit -m "feat: 작업 내용"
```

> 예시: `git commit -m "feat: 캘린더 페이지 구현"`

### 5. 푸시

```bash
git push origin 브랜치명
```

> 예시: `git push origin feature/calendar`
