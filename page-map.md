# Page Map

PHP 파일 목록. 파일을 추가/삭제하면 이 표도 함께 수정한다. 화면 설계는 [docs/design.md](docs/design.md).

## 페이지

| URL | 파일 | 화면 | 퍼블리싱 원본 | 레이아웃 |
| --- | --- | --- | --- | --- |
| `/` | `index.php` | 메인 (원페이지) | `index.html` | site, 로더 포함 |
| `/portfolio.php` | `portfolio.php` | 포트폴리오 목록 | `sub-pages/portfolio-sub.html` | site |
| `/portfolio-detail.php` | `portfolio-detail.php` | 포트폴리오 상세 | `sub-pages/portfolio-sub-detail.html` | site |
| `/admin/login.php` | `admin/login.php` | 관리자 로그인 | `admin-login.html` | admin (메뉴 없음) |
| `/admin/signup.php` | `admin/signup.php` | 관리자 회원가입 | `admin-signup.html` | admin (메뉴 없음) |
| `/admin/` | `admin/index.php` | 대시보드 | `admin-dashboard.html` | admin + 메뉴 |
| `/admin/board.php` | `admin/board.php` | 게시판 목록 | `admin-board.html` | admin + 메뉴 |
| `/admin/post-edit.php` | `admin/post-edit.php` | 게시글 작성/수정 | `admin-post-edit.html` | admin + 메뉴 |
| `/admin/chatbot.php` | `admin/chatbot.php` | 챗봇 설정 | `admin-chatbot.html` | admin + 메뉴 |
| `/admin/logout.php` | `admin/logout.php` | 로그아웃 (세션 삭제 → 로그인) | - | - |

## 공통 (웹 접근 차단)

| 파일 | 설명 |
| --- | --- |
| `app/bootstrap.php` | 모든 페이지 최상단 require. 타임존·세션·에러 표시, `e()` 이스케이프, `view()` partial 출력 |
| `app/config.php` | 설정 로더 (도커 환경변수 → `config.local.php` 덮어쓰기) |
| `app/config.local.example.php` | 카페24 운영용 설정 예시 (`config.local.php`로 복사, git 제외) |
| `app/db.php` | `db()` — PDO(MariaDB, utf8mb4) 싱글턴 |
| `app/views/site/head.php` | 사용자 `<head>` + `<body>` 시작 (`pageTitle`, `bodyClass`) |
| `app/views/site/header.php` | 사용자 GNB (`isHome`, `headerTheme`) |
| `app/views/site/footer.php` | 사용자 footer + 공통 스크립트 + 문서 끝 (`isHome`) |
| `app/views/admin/head.php` | 관리자 `<head>` + `<body>` 시작 (`pageTitle`, `styles`, `scripts`, `bodyData`) |
| `app/views/admin/layout-start.php` | 관리자 좌측 메뉴 + 본문 시작 (`active`) |
| `app/views/admin/layout-end.php` | 관리자 본문 끝 |
| `app/views/admin/foot.php` | 관리자 문서 끝 |
