# Page Map

PHP 파일 목록. 파일을 추가/삭제하면 이 표도 함께 수정한다. 화면 설계는 [docs/design.md](docs/design.md).

## 페이지

| URL | 파일 | 화면 | 퍼블리싱 원본 | 레이아웃 |
| --- | --- | --- | --- | --- |
| `/` | `index.php` | 메인 (원페이지). 포트폴리오 슬라이드 = 게시판 `포트폴리오` 노출 글, 견적문의 폼 → `/inquiry.php`. 관리자 로그인 시 슬라이드 수정 링크·관리자 바 | `index.html` | site, 로더 포함 |
| `/inquiry.php` | `inquiry.php` | 견적문의 폼 제출 (POST+CSRF, `Accept: application/json` 이면 JSON, 아니면 `/#contact` 로 redirect + flash) | - | - |
| `/portfolio.php` | `portfolio.php` | 포트폴리오 목록 (`portfolios`). 분류 탭 `?category=`, `+` 더보기 (`?partial=1&page=N` → JSON 조각) | `sub-pages/portfolio-sub.html` | site |
| `/portfolio-detail.php?id=` | `portfolio-detail.php` | 포트폴리오 상세. 없거나 숨김이면 404 | `sub-pages/portfolio-sub-detail.html` | site |
| `/page/{주소}` | `page.php` | 관리자가 만든 서브페이지 (`.htaccess` 리라이트 → `?slug=`). HTML·CSS·JS 는 서브페이지 관리에서. 숨김이면 관리자만, 없으면 404. `css/custom-page.css` | - | site |
| `/chatbot-open.php` | `chatbot-open.php` | 사용자 챗봇을 처음 열 때 사용 집계 (POST+CSRF, JSON) | - | - |
| `/chatbot-chat.php` | `chatbot-chat.php` | 사용자 챗봇 AI 답변 (POST+CSRF `messages` JSON → Claude API, 세션당 시간 30회). 실패 시 위젯이 규칙 기반 응답 | - | - |
| `/admin/login.php` | `admin/login.php` | 관리자 로그인 (POST: 인증 → `/admin/`) + ID/비밀번호 찾기 다이얼로그 | `admin-login.html` | admin (메뉴 없음) |
| `/admin/signup.php` | `admin/signup.php` | 관리자 회원가입 (POST: 승인 코드 확인 → 계정 생성(이메일 포함) → 로그인 화면) | `admin-signup.html` | admin (메뉴 없음) |
| `/admin/recover.php` | `admin/recover.php` | ID 찾기·비밀번호 재설정 메일 요청 JSON (POST+CSRF, 세션당 30초 제한) | - | - |
| `/admin/reset-password.php?token=` | `admin/reset-password.php` | 메일 링크로 여는 비밀번호 재설정 | - | admin (메뉴 없음) |
| `/admin/` | `admin/index.php` | 대시보드 🔒 (월별 방문·챗봇 사용률, 관리자 계정 현황, 최근 견적문의·게시글) | `admin-dashboard.html` | admin + 메뉴 |
| `/admin/dashboard-api.php` | `admin/dashboard-api.php` | 대시보드 JSON 🔒 (GET `stats&month=YYYY-MM`, `lists`) | - | - |
| `/admin/inquiries.php` | `admin/inquiries.php` | 견적문의 목록(상태 필터·검색·페이지)·상세(`?id=`)·상태/메모 저장·삭제 🔒 | - | admin + 메뉴 |
| `/admin/board.php` | `admin/board.php` | 게시판 목록 🔒 (게시판 선택지 = `boards` 테이블) | `admin-board.html` | admin + 메뉴 |
| `/admin/boards.php` | `admin/boards.php` | 게시판 관리 🔒 (생성(유형: 글 목록/표)·이름 변경·순서 이동·삭제(글·첨부 또는 표 데이터 함께), 포트폴리오 삭제 불가. POST+CSRF 후 PRG) | - | admin + 메뉴 |
| `/admin/board-table.php?board=` | `admin/board-table.php` | 표 게시판 편집 🔒 (열·행 추가·순서·삭제, 칸 입력 → 표 전체 JSON 저장, POST+CSRF 후 PRG). `js/admin-board-table.js`, `css/admin-board-table.css` | - | admin + 메뉴 |
| `/admin/post-edit.php` | `admin/post-edit.php` | 게시글 작성/수정 🔒 (`?return=/` 이면 저장 후 그 경로로) | `admin-post-edit.html` | admin + 메뉴 |
| `/admin/posts-api.php` | `admin/posts-api.php` | 게시판 JSON API 🔒 (GET `list`·`get`, POST+CSRF `save`·`bulk`·`delete`) | - | - |
| `/admin/portfolios.php` | `admin/portfolios.php` | 게시판 `포트폴리오-sub` = 포트폴리오 목록 표 🔒 (분류 필터, 일괄 노출·숨김·삭제, 순서 이동·저장). 메뉴는 게시판 | - | admin + 메뉴 |
| `/admin/portfolio-edit.php` | `admin/portfolio-edit.php` | 포트폴리오 등록/수정 🔒 (썸네일·대표·상세 이미지 업로드) | - | admin + 메뉴 |
| `/admin/subpages.php` | `admin/subpages.php` | 서브페이지 목록 🔒 (기존 PHP 페이지 + 만든 페이지, `새 서브페이지 만들기`, 만든 페이지 삭제. POST+CSRF 후 PRG) | - | admin + 메뉴 |
| `/admin/subpage-edit.php?file=`·`?id=` | `admin/subpage-edit.php` | 서브페이지 편집 🔒 (탭: 문구(`slot()`)·HTML·CSS·JS·설정, 기존 페이지는 소스(PHP 파일 직접 수정·백업 되돌리기, `source_edit` 설정)). `js/admin-subpage-edit.js`, `css/admin-subpages.css` | - | admin + 메뉴 |
| `/admin/chatbot.php` | `admin/chatbot.php` | 챗봇 설정 🔒 | `admin-chatbot.html` | admin + 메뉴 |
| `/admin/chatbot-api.php` | `admin/chatbot-api.php` | 챗봇 설정 JSON 🔒 (POST+CSRF `save`(아이콘 포함)·`delete`·`save_ai`(API 키·모델)·`delete_ai_key`) | - | - |
| `/admin/logout.php` | `admin/logout.php` | 로그아웃 (POST + CSRF만, 그 외 `/admin/` 으로) | - | - |
| `/admin/check-id.php` | `admin/check-id.php` | 회원가입 ID 중복확인 JSON (`?loginId=`) | - | - |

🔒 = `require_admin()` — 로그인하지 않으면 `/admin/login.php` 로 이동

## 공통 (웹 접근 차단)

| 파일 | 설명 |
| --- | --- |
| `app/bootstrap.php` | 모든 페이지 최상단 require. 타임존·세션·에러 표시, `e()`, `redirect()`, `site_url()`(절대 주소 기준, 설정 `site_url` 또는 요청 호스트), `asset()`(내부 CSS·JS 경로에 `?v=수정시각`), `flash()`, `view()` |
| `app/config.php` | 설정 로더 (도커 환경변수 → `config.local.php` 덮어쓰기). `db`, `debug`, `signup_code`, `mail_from`, `site_url`, `source_edit` |
| `app/config.local.example.php` | 카페24 운영용 설정 예시 (`config.local.php`로 복사, git 제외) |
| `app/db.php` | `db()` — PDO(MariaDB, utf8mb4) 싱글턴 |
| `app/csrf.php` | `csrf_token()`, `csrf_field()`, `csrf_valid()` |
| `app/auth.php` | `current_admin()`, `require_admin()`, `attempt_admin_login()`, `logout_admin()`, `admin_login_id_exists()`, 이메일 확인, ID 안내·비밀번호 재설정 토큰 발급/검증/완료 |
| `app/posts.php` | 게시판: `BOARD_PORTFOLIO`, `BOARD_TYPES`, `board_list(?type)`(키 => 이름, 글 화면은 `'post'`)·`board_admin_list()`·`board_type()`·`board_exists()`·`board_create()`·`board_rename()`·`board_delete()`·`board_move()`, `portfolio_slides()`, `post_list()`, `post_find()`, `post_store_upload()`(업로드 공통), `post_delete()` |
| `app/board-tables.php` | 표 게시판: `board_table()`(열·행 읽기), `board_table_clean()`(검증), `board_table_save()` |
| `app/inquiries.php` | 견적문의: `INQUIRY_STATUSES`, 검증·저장·검색·상태/메모 변경·삭제 |
| `app/portfolios.php` | 포트폴리오: `PORTFOLIO_CATEGORIES`, 공개 목록·관리 목록·저장·순서·노출·삭제 |
| `app/slots.php` | 페이지 문구 슬롯: `slot()`(유형별 출력), `slot_value()`, `slot_scan()`(소스에서 슬롯 수집), `slot_save()` |
| `app/subpages.php` | 서브페이지: `subpage_list()`(기존 PHP + 만든 페이지), `subpage_for_file()`, `subpage_find()`, `subpage_create()`, `subpage_save()`, `subpage_delete()`, `subpage_current()`(site/head·footer 가 CSS·HTML·JS 주입) / 소스 편집: `subpage_source()`, `subpage_source_save()`(해시 확인·문법 검사·백업), `subpage_source_backups()`, `subpage_source_restore()` |
| `app/chatbot.php` | 챗봇: `CHATBOT_TONES`, `chatbot_settings()`(행 없으면 기본값), 검증·저장·초기화 / AI: `chatbot_ai()`, `chatbot_ai_save()`, `chatbot_ai_reply()`(Claude Messages API, curl) |
| `app/stats.php` | `track_visit()`, `track_chatbot_use()` (세션당 하루 1회 `visit_stats` 집계), 대시보드 집계 함수 |
| `app/mail.php` | `send_mail()` — 운영 `mail()`, 로컬(debug) 은 `app/storage/mail.log` 에 기록 |
| `app/views/site/head.php` | 사용자 `<head>` + `<body>` 시작 (`pageTitle`, `bodyClass`, `isHome` = 메인 전용 Lenis, `styles` = 페이지 CSS). 파비콘·OG 메타(`aseets/img/og-image.jpg`). 공통 CSS 뒤에 `css/motion.css`(글자 등장)·`css/site-overflow.css`(DB 값 텍스트 넘침 보정) 로드 |
| `app/views/site/header.php` | 사용자 GNB (`isHome`, `headerTheme`). Portfolio → `/portfolio.php`, 나머지는 메인 섹션 |
| `app/views/site/footer.php` | 사용자 footer + 챗봇 위젯 + 공통 스크립트(`script.js` 다음 `js/motion.js` = 패럴랙스·글자 등장·상세 화살표) + 문서 끝 (`isHome`) |
| `app/views/site/chatbot.php` | 사용자 챗봇 위젯 마크업 (설정값 반영) |
| `app/views/site/portfolio-items.php` | 포트폴리오 목록 항목 (3개씩 한 줄) |
| `app/views/admin/head.php` | 관리자 `<head>` + `<body>` 시작 (`pageTitle`, `styles`, `scripts`, `bodyData`) |
| `app/views/admin/layout-start.php` | 관리자 좌측 메뉴(로그인 이름·로그아웃 폼) + 본문 시작 (`active`) |
| `app/views/admin/layout-end.php` | 관리자 본문 끝 |
| `app/views/admin/foot.php` | 관리자 문서 끝 |

## DB

| 파일 | 테이블 |
| --- | --- |
| `db/schema.sql` | `admins` (+ `email` ALTER), `password_resets`, `boards`(+ 고정 `portfolio`=포트폴리오-main·`portfolio-sub`=포트폴리오-sub, + `type` ALTER), `board_tables`, `page_slots`, `subpages`, `posts` (+ `portfolio_id` ALTER), `post_files`, `inquiries`, `portfolios`, `chatbot_settings`, `chatbot_ai`(API 키, 덤프 제외), `visit_stats`. 여러 번 실행해도 안전 |
| `db/seed-portfolio.sql` | 메인 포트폴리오 슬라이드 시안 4개 (`posts` 에 포트폴리오 글이 없을 때만). `seed-portfolios.sql` 다음에 실행하면 VIEW PROJECT 를 목록 1~4번째에 연결 |
| `db/seed-portfolios.sql` | 포트폴리오 목록/상세 시안 6개 (`portfolios` 가 비어 있을 때만) |
| `db/dump.sql` | 로컬 공유 데이터 (`boards`, `board_tables`, `page_slots`, `subpages`, `portfolios`, `posts`, `post_files`, `chatbot_settings` INSERT IGNORE). setup 이 빈 DB 에만 적용. 갱신 방법은 README |
