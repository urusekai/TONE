<!-- logo -->
<p align="center">
  <a href="https://toneapp.dothome.co.kr" target="_blank">
    <img width="220" height="220" alt="logo" src="https://github.com/user-attachments/assets/2387ca27-efc6-4d15-a69a-92c8dddbe63c" />
  </a>
</p>
<p align="center">
  <a href="https://toneapp.dothome.co.kr" target="_blank">
    <img width="120" alt="logo-text" src="https://github.com/user-attachments/assets/2b1036b8-0ad2-4819-b6df-5300e53b25d2" />
  </a>
</p>
<p align="center">
TONE은 사용자에게 매일 팬톤컬러 기반의 색상과 그에 어울리는 플레이리스트를 제공하고<br/>
하루의 감정을 색과 음악으로 기록할 수 있는 모바일 뮤직 플랫폼입니다.
</p>
<p align="center">
  <a href="https://toneapp.dothome.co.kr" target="_blank">
    <img alt="toneapp.dothome.co.kr" src="https://img.shields.io/badge/toneapp.dothome.co.kr-3F5F73?style=for-the-badge" />
  </a>
</p>

<br>

## 🖥️ 주요 기능 & 화면

<table align="center">
  <tr>
    <td align="center"><b>[ 소셜 로그인 ]</b><br>카카오 / 네이버 / 구글</td>
    <td align="center"><b>[ 메인 페이지 ]</b><br/>컬러차트 / 팔레트로그 / 카테고리</b></td>
    <td align="center"><b>[ 데일리 스펙트럼 ]</b><br>설문을 통한 색 추천과 AI 해설</b></td>
  </tr>
  <tr>
    <td align="center" width="300">
      <img src="https://github.com/user-attachments/assets/101f73fb-82b1-426b-9afd-b1f8fb8bc40b" width="250"/>
    </td>
    <td align="center" width="300">
      <img src="https://github.com/user-attachments/assets/b7df23ee-94ff-4f51-ba69-d675cab03468" width="250"/>
    </td>
    <td align="center" width="300">
      <img src="https://github.com/user-attachments/assets/33757b7d-5d00-4f69-aa45-df8dcfb15544" width="250"/>
    </td>
  </tr>
</table>
<table align="center">
  <tr>
    <td align="center"><b>[ 플레이어 ]</b><br>미니&메인플레이어 전환 / MV재생</b></td>
    <td align="center"><b>[ 검색 ]</b><br>AI 기반 플레이리스트 추천과 설명</b></td>
    <td align="center"><b>[ 캘린더 ]</b><br>오늘의 색과 감정을 아카이빙</td>
  </tr>
  <tr>
    <td align="center" width="300">
      <img src="https://github.com/user-attachments/assets/3cc1f92f-8b76-4eb3-b9ee-c718b17a2ff6" width="250"/>
    </td>
    <td align="center" width="300">
      <img src="https://github.com/user-attachments/assets/50c1eb64-76d9-4c32-a2ad-23e83990302b" width="250"/>
    </td>
    <td align="center" width="300">
      <img src="https://github.com/user-attachments/assets/ad6d21ea-fa26-4a92-a30a-19d1874dbf7f" width="250"/>
    </td>
  </tr>
</table>

<br>

## 🛠 구현 포인트

- **백엔드 API 및 DB 연동** : 모든 주요 기능을 PHP API와 데이터베이스에 연결해 직접 구현했습니다.
- **Pinia 상태 관리** : 인증, 플레이어, 캘린더, 팔레트 로그 상태를 Pinia로 관리했습니다.
- **OpenAI API 연동** : OpenAI의 API를 활용해 데일리스펙트럼 추천과 자연어 검색 기능을 구현했습니다.
- **소셜 로그인 연동** : 카카오, 구글, 네이버 Oauth를 통해 소셜 로그인을 구현했습니다.
- **배포 자동화** : `main` 머지 시 GitHub Actions를 통해 빌드와 닷홈 서버 배포까지의 자동화를 구현했습니다.
- **미디어 분리 운영** : Cloudflare R2 기반으로 커버, 음원, 영상 리소스를 분리해서 관리했습니다.

<br>

## ⚙ 기술 스택

**프론트엔드**

<p>
  <img alt="Vue.js" hspace="3" src="https://img.shields.io/badge/vuejs-%2335495e.svg?style=flat-square&logo=vuedotjs&logoColor=%234FC08D" />
  <img alt="Vue Router" hspace="3" src="https://img.shields.io/badge/Vue%20Router-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white" />
  <img alt="Vite" hspace="3" src="https://img.shields.io/badge/vite-%23646CFF.svg?style=flat-square&logo=vite&logoColor=white" />
  <img alt="Pinia" hspace="3" src="https://img.shields.io/badge/Pinia-FFD859?style=flat-square&logo=pinia&logoColor=black" />
  <img alt="Axios" hspace="3" src="https://img.shields.io/badge/Axios-5A29E4?style=flat-square&logo=axios&logoColor=white" />
</p>

**백엔드 & AI**

<p>
  <img alt="PHP" hspace="3" src="https://img.shields.io/badge/php-%23777BB4.svg?style=flat-square&logo=php&logoColor=white" />
  <img alt="MySQL" hspace="3" src="https://img.shields.io/badge/mysql-4479A1.svg?style=flat-square&logo=mysql&logoColor=white" />
  <img alt="Composer" hspace="3" src="https://img.shields.io/badge/composer-885630?style=flat-square&logo=composer&logoColor=white" />
  <img alt="OpenAI API" hspace="3" src="https://img.shields.io/badge/OpenAI%20API-412991?style=flat-square&logo=openai&logoColor=white" />
</p>

**배포 & 인프라**

<p>
  <img alt="GitHub Actions" hspace="3" src="https://img.shields.io/badge/GitHub%20Actions-2088FF?style=flat-square&logo=githubactions&logoColor=white" />
  <img alt="Cloudflare R2" hspace="3" src="https://img.shields.io/badge/Cloudflare%20R2-F38020?style=flat-square&logo=cloudflare&logoColor=white" />
</p>

<br>

## 📂 폴더 구조

```bash
TONE/
├─ .github/                    # GitHub 협업/배포 설정
│  ├─ workflows/               # GitHub Actions 워크플로우
│  │  └─ deploy.yml            # 프론트 빌드 + 닷홈 FTP 자동 배포
│  ├─ deploy-exclude.txt       # 배포 패키지 제외 목록
│  └─ pull_request_template.md # PR 작성 템플릿
├─ .vscode/                    # 에디터 공통 설정
├─ backend/
│  ├─ api/                     # 엔드포인트 진입점 모음
│  │  ├─ auth/                 # 일반 로그인, 소셜 로그인, 프로필 관련 API
│  │  ├─ calendar/             # 감정 기록 캘린더 API
│  │  ├─ categories/           # 카테고리/플레이리스트 목록 API
│  │  ├─ palette-logs/         # 저장/좋아요 기록 API
│  │  └─ playlist/             # 일일 추천, 상세, 좋아요 API
│  ├─ database/                # schema.sql, reset.sql, track_list.json
│  ├─ prompts/                 # 추천 생성용 프롬프트 템플릿
│  ├─ src/                     # Auth, Database, MediaUrl 공통 로직
│  ├─ vendor/                  # Composer 의존성
│  ├─ .env.example             # 백엔드 환경변수 예시
│  ├─ bootstrap.php            # 백엔드 공통 초기화
│  ├─ composer.json            # 백엔드 의존성 설정
│  └─ composer.lock            # Composer 의존성 잠금 파일
├─ frontend/
│  ├─ public/                  # favicon, 배포용 정적 파일
│  ├─ src/                     # 프론트 애플리케이션 소스
│  │  ├─ assets/               # 이미지, 아이콘, 공통 스타일
│  │  ├─ components/           # 재사용 UI 컴포넌트
│  │  ├─ data/                 # 설문/추천 관련 정적 데이터
│  │  ├─ layouts/              # 앱/인증 레이아웃
│  │  ├─ router/               # Vue Router 설정
│  │  ├─ services/             # axios 기반 API 요청 로직
│  │  ├─ stores/               # Pinia 상태 관리
│  │  ├─ utils/                # 인증 검증, alert 유틸
│  │  ├─ views/                # 페이지 단위 화면
│  │  ├─ App.vue               # 최상위 Vue 앱 컴포넌트
│  │  └─ main.js               # 프론트 앱 진입 파일
│  ├─ index.html               # Vite HTML 엔트리
│  ├─ package.json             # 프론트 스크립트/의존성 설정
│  └─ vite.config.js           # Vite 개발/빌드 설정
├─ .gitattributes              # Git 텍스트/줄바꿈 속성
├─ .gitignore                  # Git 추적 제외 규칙
├─ .prettierrc                 # 코드 포맷팅 규칙
└─ README.md                   # 프로젝트 소개 및 가이드
```

<br>

## 📜 컨벤션

### 브랜치 전략

- `main` : 운영 배포 브랜치
- `develop` : 기능 통합 및 배포 전 검증 브랜치
- `feat/*`, `fix/*`, `refactor/*`, `chore/*` : 개별 작업 브랜치

### 브랜치명

- `feat/작업명` : 기능 추가
- `fix/작업명` : 버그 수정
- `refactor/작업명` : 구조 정리, 코드 개선
- `style/작업명` : 스타일 수정
- `chore/작업명` : 설정, 환경, 파일 정리
- 예시 : `feat/calendar-api`

### 커밋 메시지

- `feat: 내용`
- `fix: 내용`
- `refactor: 내용`
- `style: 내용`
- `chore: 내용`
- 예시 : `feat: 캘린더 API 연결`
