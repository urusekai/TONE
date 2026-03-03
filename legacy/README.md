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

## 🖥 실행 방법

Vue / PHP 세팅 완료되면 추가 예정

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
