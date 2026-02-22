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

---

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
│
│── js/                     # 페이지별 스크립트
│
│── index.html              # 메인 페이지
│   ...                     # 페이지별 HTML 파일
│
│── .prettierrc             # 코드 포맷 설정 파일
```

## 🧑‍💻 작업 규칙

### 1️⃣ 브랜치 규칙

- `main` → 최종 완성본
- `develop` → 코드 통합할 브랜치 (main 브랜치 대신 사용하는 브랜치)
- `feature/기능이름` → 개인 작업 브랜치
- 작업하기 전 현재 브랜치가 작업할 브랜치가 맞는지 인지 확인하고 작업하기
- 현재 브랜치가 작업할 브랜치가 아니라면 `git checkout feature/기능이름` 으로 브랜치 이동 후 작업

### 2️⃣ 작업 순서

#### 🔹 1. develop 최신화

```bash
git checkout develop
git pull origin develop
```


#### 🔹 2. 리모트 브랜치를 기준으로 로컬 브랜치 생성 + 이동 (처음 1회)

```bash
git switch -c feature/기능이름 origin/feature/기능이름
```

> ⚠ 에러가 발생하면 아직 리모트에 해당 브랜치가 생성되지 않은 상태입니다.


#### 🔹 3. 작업 후 커밋

```bash
git add .
git commit -m "작업한 내용 쓰기"
```


#### 🔹 4. 리모트에 푸시

```bash
git push origin feature/기능이름
```
