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
<p align="center">
  <a href="http://toneapp.dothome.co.kr" target="_blank">
    <img alt="Live Demo" src="https://img.shields.io/badge/Live%20Demo-toneapp.dothome.co.kr-3F5F73?style=for-the-badge&logo=googlechrome&logoColor=white" />
  </a>
</p>

<br>

## ⚙ 기술 스택

<p align="center">
  <img alt="HTML5" hspace="3" src="https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white" />
  <img alt="CSS" hspace="3" src="https://img.shields.io/badge/css-%23663399.svg?style=for-the-badge&logo=css&logoColor=white" />
  <img alt="JavaScript" hspace="3" src="https://img.shields.io/badge/javascript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" />
  <img alt="Vue.js" hspace="3" src="https://img.shields.io/badge/vuejs-%2335495e.svg?style=for-the-badge&logo=vuedotjs&logoColor=%234FC08D" />
  <img alt="Vite" hspace="3" src="https://img.shields.io/badge/vite-%23646CFF.svg?style=for-the-badge&logo=vite&logoColor=white" />
  <img alt="Pinia" hspace="3" src="https://img.shields.io/badge/Pinia-FFD859?style=for-the-badge&logo=pinia&logoColor=black" />
  <img alt="Axios" hspace="3" src="https://img.shields.io/badge/Axios-5A29E4?style=for-the-badge&logo=axios&logoColor=white" />
  <img alt="Figma" hspace="3" src="https://img.shields.io/badge/Figma-F24E1E?style=for-the-badge&logo=figma&logoColor=white" />
  <br>
  <img alt="PHP" hspace="3" src="https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white" />
  <img alt="MySQL" hspace="3" src="https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white" />
  <img alt="Composer" hspace="3" src="https://img.shields.io/badge/composer-885630?style=for-the-badge&logo=composer&logoColor=white" />
  <img alt="GitHub Actions" hspace="3" src="https://img.shields.io/badge/GitHub%20Actions-2088FF?style=for-the-badge&logo=githubactions&logoColor=white" />
</p>

<br>

## ✨ 주요 기능

- `데일리 톤` : 매일 팬톤 컬러와 어울리는 플레이리스트를 제공합니다.
- `플레이리스트` : 색상 정보, 트랙 목록, 좋아요 수를 확인할 수 있습니다.
- `플레이어` : 미니 플레이어 / 메인 플레이어에서 음악을 재생할 수 있습니다.
- `MV Mode` : 메인 플레이어에서 곡과 함께 배경 영상을 볼 수 있습니다.
- `셔플 / 반복 재생` : 셔플, 전체 반복, 한 곡 반복 재생을 지원합니다.
- `캘린더` : 하루의 메모와 플레이리스트를 기록하고 캘린더에서 확인할 수 있습니다.
- `팔레트 로그` : 마음에 드는 플레이리스트를 저장해 기록으로 남길 수 있습니다.

<br>

## 🛠 개발 포인트

- `GitHub Actions 자동배포` : `main` 머지 시 빌드와 의존성 설치 후 닷홈 서버로 자동 배포되도록 구성했습니다.
- `GitHub 협업 프로세스` : 브랜치 전략, 커밋 컨벤션, Pull Request 템플릿으로 협업 흐름을 관리했습니다.
- `소셜 로그인 연동` : 카카오, 구글, 네이버 OAuth 로그인 흐름과 리다이렉트를 구현했습니다.
- `프론트-백엔드 전면 연동` : 주요 화면을 PHP API와 MySQL에 연결해 실제 데이터 기반으로 구현했습니다.
- `Cloudflare R2 미디어 관리` : 커버, 음원, 영상 파일을 Cloudflare R2 공개 URL 기반으로 관리했습니다.
- `Figma 기반 화면 설계` : Figma로 화면 구조와 UI를 설계하고 구현 단계에서 실제 화면에 맞게 조정했습니다.
- `닷홈 배포 환경 구성` : Vue history 모드와 PHP 백엔드 구조를 닷홈 환경에 맞게 배포했습니다.

<br>

## 📂 디렉터리 구조 설명

```bash
TONE/
├─ .github/                 # Pull Request 템플릿 폴더
├─ .vscode/                 # VS Code 공통 설정 폴더
├─ backend/                 # PHP 백엔드 폴더
│  ├─ api/                  # API 엔드포인트 파일 모음
│  ├─ database/             # schema.sql, reset.sql 등 DB 관련 파일
│  ├─ src/                  # DB 연결, 인증, URL 생성 등 공통 PHP 코드
│  ├─ vendor/               # Composer 설치 패키지
│  ├─ .env                  # 로컬 백엔드 환경변수
│  ├─ .env.example          # 환경변수 예시 파일
│  ├─ bootstrap.php         # 공통 초기화 파일
│  └─ composer.json         # 백엔드 의존성 목록
├─ frontend/                # Vue 3 프론트엔드 폴더
│  ├─ src/
│  │  ├─ assets/            # 아이콘, 이미지, 공통 CSS
│  │  ├─ components/        # 재사용 UI 컴포넌트
│  │  ├─ layouts/           # 공통 화면 레이아웃
│  │  ├─ router/            # 라우터 설정
│  │  ├─ services/          # API 요청 함수와 서비스 모음
│  │  ├─ stores/            # Pinia 상태 관리
│  │  ├─ views/             # 페이지 단위 화면
│  │  ├─ App.vue            # 앱 최상위 컴포넌트
│  │  └─ main.js            # Vue 앱 시작 파일
│  ├─ public/               # 정적 파일 폴더
│  ├─ package.json          # 프론트엔드 의존성 목록
│  └─ vite.config.js        # Vite 설정 파일
├─ legacy/                  # Vue 전환 전 원본 HTML/CSS 보관 폴더
├─ .gitattributes           # 줄바꿈(LF) 통일 설정
├─ .prettierrc              # 코드 정렬 규칙
└─ README.md                # 프로젝트 설명 문서
```

<br>

## 📜 컨벤션

### 1. 브랜치명

- `feat/작업명` : 기능 추가
- `fix/작업명` : 버그 수정
- `refactor/작업명` : 구조 정리, 코드 개선
- `style/작업명` : 스타일 수정
- `chore/작업명` : 설정, 환경, 파일 정리

예시: `feat/calendar-api`

### 2. 커밋 메시지

- `feat: 내용`
- `fix: 내용`
- `refactor: 내용`
- `style: 내용`
- `chore: 내용`

예시: `feat: 캘린더 API 연결`

<br>

## 🧩 실행 방법 (로컬에서 XAMPP 사용)

### 1. 프로젝트 위치 확인

프로젝트 폴더를 `htdocs` 아래에 둡니다.

```bash
C:\xampp\htdocs\TONE
```

### 2. XAMPP 실행

XAMPP Control Panel에서 아래 2개를 실행합니다.

```bash
Apache  # PHP 실행용
MySQL   # DB 실행용
```

### 3. DB 생성 + 스키마 적용

`phpMyAdmin`(http://localhost/phpmyadmin)에 접속한 뒤 아래 순서대로 진행합니다.

```bash
1) tone 데이터베이스 생성
2) backend/database/reset.sql 파일 내용 복사해서 콘솔에 붙여넣고 실행 (Ctrl + Enter)
3) backend/database/schema.sql 파일 내용 복사해서 콘솔에 붙여넣고 실행 (Ctrl + Enter)
```

### 4. backend 의존성 설치 (composer install)

backend 폴더로 이동한 뒤 `composer install`을 실행해 의존성을 설치합니다.
(`frontend`의 `npm install`과 같은 역할입니다.)

```bash
cd backend
composer install
```

### 5. backend 환경변수(.env) 설정

- 전달받은 `.env` 파일을 `backend/.env` 위치에 넣습니다.
- `DB_USER`, `DB_PASS` 값에는 자신의 MySQL 아이디와 비밀번호를 입력합니다.
- `.env.example` 파일은 `.env` 형식을 보여주는 예시 파일입니다.

### 6. frontend 의존성 설치 + 실행

frontend 폴더로 이동한 뒤 아래 명령어를 실행합니다.

```bash
cd frontend
npm install
npm run dev
```

실행 후 브라우저에서 아래 주소로 접속합니다.

```bash
http://localhost:5173
```
