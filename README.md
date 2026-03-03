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

<br>

## ⚙ 기술 스택

![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)
![CSS](https://img.shields.io/badge/css-%23663399.svg?style=for-the-badge&logo=css&logoColor=white)
![Vue.js](https://img.shields.io/badge/vuejs-%2335495e.svg?style=for-the-badge&logo=vuedotjs&logoColor=%234FC08D)
![Vite](https://img.shields.io/badge/vite-%23646CFF.svg?style=for-the-badge&logo=vite&logoColor=white)
![Axios](https://img.shields.io/badge/Axios-5A29E4?style=for-the-badge&logo=axios&logoColor=white)
![Pinia](https://img.shields.io/badge/Pinia-FFD859?style=for-the-badge&logo=pinia&logoColor=black)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)

<br>

## 📂 프로젝트 폴더 설명

```bash
TONE/
├─ .github/                 # Pull Request 작성 시 사용하는 템플릿 폴더
├─ .vscode/                 # VS Code 팀 공통 설정 폴더
├─ backend/                 # 백엔드엔드 (PHP) 작업 폴더
├─ frontend/                # 프론트엔드 (Vue3) 작업 폴더
│  ├─ src/
│  │  ├─ assets/            # 아이콘, 이미지, 공통 CSS 파일
│  │  ├─ components/        # 재사용 UI 컴포넌트 (헤더, 플레이어, 모달 등)
│  │  ├─ layouts/           # 공통 화면 구조
│  │  ├─ router/            # URL 경로와 페이지 연결
│  │  ├─ services/          # API 요청 함수 모음 (axios 사용)
│  │  ├─ stores/            # Pinia 상태 관리 (플레이어 상태 등)
│  │  ├─ views/             # 각 페이지 화면 (메인, 검색, 마이페이지 등)
│  │  ├─ App.vue            # 전체 앱의 최상위 컴포넌트
│  │  └─ main.js            # Vue 앱 시작 파일
│  ├─ public/               # 정적 파일 위치 (거의 수정하지 않음)
│  ├─ package.json          # 프론트엔드 라이브러리 목록
│  └─ vite.config.js        # Vite 설정 파일 (alias 등)
├─ legacy/                  # Vue 전환 전 HTML/CSS 원본 보관 폴더 (수정 X)
├─ .gitattributes           # 줄바꿈(LF) 통일 설정 파일
├─ .prettierrc              # 코드 자동 정렬 규칙 파일
└─ README.md                # 프로젝트 설명 문서
```

<br>

## 📜 컨벤션

### 1. 브랜치명

- `feature/브랜치명` : 기능 추가
- `refactor/브랜치명` : UI 수정, 코드 구조 변경
- `fix/브랜치명` : 버그 수정
- `style/브랜치명` : CSS 수정

### 2. 커밋 메시지

- `feat: 커밋 메시지 내용` : 기능 추가
- `refactor: 커밋 메시지 내용` : UI 수정, 코드 구조 변경
- `fix: 커밋 메시지 내용` : 버그 수정
- `style: 커밋 메시지 내용` : CSS 수정

<br>

## 🛠 작업 순서

### 0. 주의사항

<strong>Git 명령어는 반드시 프로젝트 루트에서만 실행 (TONE/)</strong><br>
<strong>npm 명령어는 TONE/frontend 폴더에서 실행</strong><br>
<strong>develop 브랜치에서 직접 작업하지 않기</strong><br>

### 1. develop 브랜치 최신화 (프로젝트 루트에서 실행하기)

```bash
git checkout develop
git pull origin develop
```

### 2. develop 브랜치를 기반으로 새 작업 브랜치 생성 후 이동

```bash
git switch -c 브랜치명 # ex) git switch -c feature/calendar
```

### 3. 현재 브랜치가 작업할 브랜치가 맞는지 확인

```bash
git branch
```

> 현재 브랜치가 다르면 `git switch 브랜치명` 으로 이동

### 4. vue3 실행 (터미널에서 frontend로 이동하여 실행)

```bash
cd frontend
npm install # 최초 1회 또는 라이브러리 변경 시
npm run dev #
```

### 5. 작업 후 커밋 (터미널에서 프로젝트 루트로 이동하여 실행)

```bash
cd .. # (TONE/frontend -> TONE/)
git add .
git commit -m "feat: 작업내용" # ex) git commit -m "feat: 캘린더 페이지 구현"
```

### 6. 원격 저장소에 푸시

```bash
git push origin 브랜치명 # ex) git push origin feature/calendar
```
