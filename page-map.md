# Page Map

PHP 파일 목록. 파일을 추가/삭제하면 이 표도 함께 수정한다. 화면 설계는 [docs/design.md](docs/design.md).

## 페이지

| URL | 파일 | 화면 | 퍼블리싱 원본 | 레이아웃 |
| --- | --- | --- | --- | --- |
| `/` | `index.php` | 메인 (원페이지). 포트폴리오 슬라이드 = 게시판 `포트폴리오` 노출 글, 견적문의 폼 → `/inquiry.php`. 관리자 로그인 시 슬라이드 수정 링크·관리자 바 | `index.html` | site, 로더 포함 |
| `/inquiry.php` | `inquiry.php` | 견적문의 폼 제출 (POST+CSRF, `Accept: application/json` 이면 JSON, 아니면 `/#contact` 로 redirect + flash) | - | - |
| `/portfolio.php` | `portfolio.php` | 포트폴리오 목록 (`portfolios`). 분류 탭 `?category=`, `+` 더보기 (`?partial=1&page=N` → JSON 조각) | `sub-pages/portfolio-sub.html` | site |
| `/portfolio-detail.php?id=` | `portfolio-detail.php` | 포트폴리오 상세. 없거나 숨김이면 404 | `sub-pages/portfolio-sub-detail.html` | site |
| `/chatbot-open.php` | `chatbot-open.php` | 사용자 챗봇을 처음 열 때 사용 집계 (POST+CSRF, JSON) | - | - |
| `/admin/login.php` | `admin/login.php` | 관리자 로그인 (POST: 인증 → `/admin/`) + ID/비밀번호 찾기 다이얼로그 | `admin-login.html` | admin (메뉴 없음) |
| `/admin/signup.php` | `admin/signup.php` | 관리자 회원가입 (POST: 승인 코드 확인 → 계정 생성(이메일 포함) → 로그인 화면) | `admin-signup.html` | admin (메뉴 없음) |
| `/admin/recover.php` | `admin/recover.php` | ID 찾기·비밀번호 재설정 메일 요청 JSON (POST+CSRF, 세션당 30초 제한) | - | - |
| `/admin/reset-password.php?token=` | `admin/reset-password.php` | 메일 링크로 여는 비밀번호 재설정 | - | admin (메뉴 없음) |
| `/admin/` | `admin/index.php` | 대시보드 🔒 (월별 방문·챗봇 사용률, 관리자 계정 현황, 최근 견적문의·게시글) | `admin-dashboard.html` | admin + 메뉴 |
| `/admin/dashboard-api.php` | `admin/dashboard-api.php` | 대시보드 JSON 🔒 (GET `stats&month=YYYY-MM`, `lists`) | - | - |
| `/admin/inquiries.php` | `admin/inquiries.php` | 견적문의 목록(상태 필터·검색·페이지)·상세(`?id=`)·상태/메모 저장·삭제 🔒 | - | admin + 메뉴 |
| `/admin/board.php` | `admin/board.php` | 게시판 목록 🔒 | `admin-board.html` | admin + 메뉴 |
| `/admin/post-edit.php` | `admin/post-edit.php` | 게시글 작성/수정 🔒 (`?return=/` 이면 저장 후 그 경로로) | `admin-post-edit.html` | admin + 메뉴 |
| `/admin/posts-api.php` | `admin/posts-api.php` | 게시판 JSON API 🔒 (GET `list`·`get`, POST+CSRF `save`·`bulk`·`delete`) | - | - |
| `/admin/portfolios.php` | `admin/portfolios.php` | 포트폴리오 목록 표 🔒 (분류 필터, 일괄 노출·숨김·삭제, 순서 이동·저장) | - | admin + 메뉴 |
| `/admin/portfolio-edit.php` | `admin/portfolio-edit.php` | 포트폴리오 등록/수정 🔒 (썸네일·대표·상세 이미지 업로드) | - | admin + 메뉴 |
| `/admin/chatbot.php` | `admin/chatbot.php` | 챗봇 설정 🔒 | `admin-chatbot.html` | admin + 메뉴 |
| `/admin/chatbot-api.php` | `admin/chatbot-api.php` | 챗봇 설정 JSON 🔒 (POST+CSRF `save`(아이콘 포함)·`delete`) | - | - |
| `/admin/logout.php` | `admin/logout.php` | 로그아웃 (POST + CSRF만, 그 외 `/admin/` 으로) | - | - |
| `/admin/check-id.php` | `admin/check-id.php` | 회원가입 ID 중복확인 JSON (`?loginId=`) | - | - |

🔒 = `require_admin()` — 로그인하지 않으면 `/admin/login.php` 로 이동

## 공통 (웹 접근 차단)

| 파일 | 설명 |
| --- | --- |
| `app/bootstrap.php` | 모든 페이지 최상단 require. 타임존·세션·에러 표시, `e()`, `redirect()`, `flash()`, `view()` |
| `app/config.php` | 설정 로더 (도커 환경변수 → `config.local.php` 덮어쓰기). `db`, `debug`, `signup_code`, `mail_from`, `site_url` |
| `app/config.local.example.php` | 카페24 운영용 설정 예시 (`config.local.php`로 복사, git 제외) |
| `app/db.php` | `db()` — PDO(MariaDB, utf8mb4) 싱글턴 |
| `app/csrf.php` | `csrf_token()`, `csrf_field()`, `csrf_valid()` |
| `app/auth.php` | `current_admin()`, `require_admin()`, `attempt_admin_login()`, `logout_admin()`, `admin_login_id_exists()`, 이메일 확인, ID 안내·비밀번호 재설정 토큰 발급/검증/완료 |
| `app/posts.php` | 게시판: `POST_BOARDS`, `portfolio_slides()`, `post_list()`, `post_find()`, `post_store_upload()`(업로드 공통), `post_delete()` |
| `app/inquiries.php` | 견적문의: `INQUIRY_STATUSES`, 검증·저장·검색·상태/메모 변경·삭제 |
| `app/portfolios.php` | 포트폴리오: `PORTFOLIO_CATEGORIES`, 공개 목록·관리 목록·저장·순서·노출·삭제 |
| `app/chatbot.php` | 챗봇: `CHATBOT_TONES`, `chatbot_settings()`(행 없으면 기본값), 검증·저장·초기화 |
| `app/stats.php` | `track_visit()`, `track_chatbot_use()` (세션당 하루 1회 `visit_stats` 집계), 대시보드 집계 함수 |
| `app/mail.php` | `send_mail()` — 운영 `mail()`, 로컬(debug) 은 `app/storage/mail.log` 에 기록 |
| `app/views/site/head.php` | 사용자 `<head>` + `<body>` 시작 (`pageTitle`, `bodyClass`, `isHome` = 메인 전용 Lenis, `styles` = 페이지 CSS) |
| `app/views/site/header.php` | 사용자 GNB (`isHome`, `headerTheme`). Portfolio → `/portfolio.php`, 나머지는 메인 섹션 |
| `app/views/site/footer.php` | 사용자 footer + 챗봇 위젯 + 공통 스크립트 + 문서 끝 (`isHome`) |
| `app/views/site/chatbot.php` | 사용자 챗봇 위젯 마크업 (설정값 반영) |
| `app/views/site/portfolio-items.php` | 포트폴리오 목록 항목 (3개씩 한 줄) |
| `app/views/admin/head.php` | 관리자 `<head>` + `<body>` 시작 (`pageTitle`, `styles`, `scripts`, `bodyData`) |
| `app/views/admin/layout-start.php` | 관리자 좌측 메뉴(로그인 이름·로그아웃 폼) + 본문 시작 (`active`) |
| `app/views/admin/layout-end.php` | 관리자 본문 끝 |
| `app/views/admin/foot.php` | 관리자 문서 끝 |

## DB

| 파일 | 테이블 |
| --- | --- |
| `db/schema.sql` | `admins` (+ `email` ALTER), `password_resets`, `posts` (+ `portfolio_id` ALTER), `post_files`, `inquiries`, `portfolios`, `chatbot_settings`, `visit_stats`. 여러 번 실행해도 안전 |
| `db/seed-portfolio.sql` | 메인 포트폴리오 슬라이드 시안 4개 (`posts` 에 포트폴리오 글이 없을 때만). `seed-portfolios.sql` 다음에 실행하면 VIEW PROJECT 를 목록 1~4번째에 연결 |
| `db/seed-portfolios.sql` | 포트폴리오 목록/상세 시안 6개 (`portfolios` 가 비어 있을 때만) |
| `db/dump.sql` | 로컬 공유 데이터 (`portfolios`, `posts`, `post_files`, `chatbot_settings` INSERT IGNORE). setup 이 빈 DB 에만 적용. 갱신 방법은 README |
