# AGENTS.md

fuse.lab 브랜드 웹사이트. 정적 HTML 퍼블리싱 위에 필요한 기능만 순수 PHP 로 구현한다.

## 언어

- 답변, 진행 보고, 커밋 메시지 설명, 문서는 한국어. 코드·명령어·경로·식별자는 원문 그대로.

## 운영 환경 (기준)

- 카페24 10G 광아우토반 FullSSD+ — **PHP 8.4** (2026-10 확인: 8.4.21), MariaDB, Apache(`.htaccess` 사용)
- 설치형 프로그램/프레임워크 없음. Composer·npm 빌드 없이 FTP 업로드만으로 동작해야 한다.
- 저장소 루트 = 웹 루트 (`www/` 에 그대로 업로드)

## 로컬 개발

- `setup.bat` (Windows) / `./setup.sh` (macOS·Linux) — Docker 기동 + DB 연결 확인 + `db/schema.sql` 적용 + 빈 DB 면 `db/dump.sql` 적용
- DB 데이터는 git 에 없으므로 공유할 콘텐츠는 `db/dump.sql` 로 갱신해 커밋 (명령은 README). 계정·견적문의 등 개인정보 테이블은 덤프에 넣지 않는다
- 사이트 `http://localhost:8088`, phpMyAdmin `:8089`, MariaDB `localhost:3310` (`fuselab`/`fuselab`)
- 포트는 `.env` (`.env.example` 참고), 종료 `docker compose down`
- PHP CLI 는 컨테이너에서: `docker compose exec web php ...`

## 구조

| 경로 | 내용 |
| --- | --- |
| `*.html`, `sub-pages/` | 퍼블리싱 원본 (사용자/관리자). PHP 로 옮겨도 삭제하지 않고 유지 |
| `index.php`, `portfolio*.php`, `admin/` | 퍼블리싱 화면을 공통 레이아웃으로 조합한 PHP 페이지 |
| `css/`, `js/`, `script.js` | 스타일·스크립트. `GYU.css` / `SURO.css` / `WON.css` 는 팀원별 파일 |
| `aseets/` | 이미지·아이콘 (폴더명 오타지만 경로가 HTML 에 박혀 있으므로 변경 금지) |
| `app/` | PHP 공통 코드 (`app/views/` = 레이아웃 partial). `.htaccess` 로 웹 접근 차단 |
| `docker/`, `docker-compose.yml`, `setup.*` | 로컬 개발 전용. 배포 대상 아님 |
| `page-map.md` | PHP 파일 목록 표 |
| `docs/design.md` | 사이트맵·화면 흐름·화면별 기능·DB 초안·미정 사항 |

외부 라이브러리는 CDN 사용 (GSAP, ScrollTrigger, three.js r128, Pretendard).

## PHP 규칙

- 모든 페이지 최상단: `require __DIR__ . '/app/bootstrap.php';` (`admin/` 은 `/../app/bootstrap.php`)
- 레이아웃은 `view()` 로 조합: 사용자 `site/head` → `site/header` → 본문 → `site/footer`,
  관리자 `admin/head` → `admin/layout-start` → 본문 → `admin/layout-end` → `admin/foot` (로그인·회원가입은 layout 생략)
- PHP 페이지의 에셋·링크는 루트 기준 절대경로(`/css/...`, `/admin/board.php`). `.html` 원본은 상대경로 그대로 둔다.
- `.html` 과 `.php` 가 함께 쓰는 JS 에서 페이지 이동 경로는 `<body data-*>` 로 받는다 (예: `data-editor-url`), 없으면 `.html` 경로.
- PHP 8.2 문법까지만 (로컬 도커가 `php:8.2-apache`). 8.3+ 기능(typed class constants, `json_validate`, `|>` 등) 금지.
- DB 는 `db()` (PDO, utf8mb4) + prepared statement 만 사용. 문자열로 쿼리 조립 금지.
- 출력은 `e()` 로 이스케이프. 비밀번호는 `password_hash` / `password_verify`.
- 변수(DB·설정값)로 출력하는 모든 칸은 텍스트 넘침 처리: 표 셀·한 줄 레이블 → 말줄임 + `title`, 카드 제목 → line-clamp, 본문·메모 → 줄바꿈(`overflow-wrap: anywhere`). flex/grid 자식은 `min-width: 0` 확인. 사용자 화면 공통 보정은 `css/site-overflow.css`.
- 사용자 페이지에서 관리자가 바꿀 문구는 `<?= slot('키', '기본 문구') ?>` (여러 줄 `'multiline'`, HTML `'html'`, 가공 전 값 `slot_value()`)로 적는다. 키·기본값·유형은 문자열 리터럴로 (관리자 > 서브페이지 편집의 문구 탭이 소스를 읽어 칸을 만듦). 자세한 내용은 README.
- 사용자 페이지는 `site/head`·`site/footer` 를 거쳐야 서브페이지 관리의 CSS·JS·HTML 블록이 붙는다 (`subpage_current()`).
- 상태 변경 요청(POST)은 CSRF 토큰 검증: 폼에 `<?= csrf_field() ?>`, 처리 시 `csrf_valid()`.
- 관리자 페이지(로그인·회원가입·중복확인 제외)는 bootstrap 바로 다음에 `require_admin();`.
- 테이블 추가·변경은 `db/schema.sql` 에 `CREATE TABLE IF NOT EXISTS` 로 적는다 (setup 이 매번 적용, 카페24 는 phpMyAdmin 에서 실행).
  기존 테이블 컬럼 변경은 `ALTER TABLE` 을 별도로 적고 실행 방법을 README 에 남긴다.
- 설정값은 `app_config()`. 비밀값을 코드에 하드코딩 금지 — 운영 값은 `app/config.local.php` (git 제외).
- include 전용 파일은 `app/` 아래에 둔다. 웹에서 직접 열리는 파일만 루트/하위 폴더에 둔다.
- SQL 은 MariaDB 10.x 공통 문법 위주. 로컬은 10.6 이지만 운영 버전은 미확인이므로 최신 전용 기능은 피한다.

## 문서 유지

- PHP 파일을 추가·삭제·역할 변경하면 **같은 작업에서** `page-map.md` 표를 갱신한다.
  - 열: `URL | 파일 | 화면 | 퍼블리싱 원본 | 레이아웃` (공통 파일은 별도 표)
- 퍼블리싱 `.html` 을 수정했다면 대응하는 `.php` 본문에도 같은 변경을 반영한다.
  로그인·회원가입·챗봇 `.html` 의 인라인 `<style>`/`<script>` 는 PHP 용으로 `css/admin-{login,signup,chatbot}.css`, `js/admin-{login,signup,chatbot}.js` 에 분리돼 있으니 함께 수정한다.
- 실행 방법·포트·배포 절차가 바뀌면 `README.md` 도 갱신한다.

## 배포 (카페24)

1. `app/config.local.example.php` → `app/config.local.php` 복사 후 카페24 DB 정보·`signup_code`·`mail_from`·`site_url` 입력
2. 카페24 DB 관리(phpMyAdmin)에서 `db/schema.sql` 실행 (시안 데이터가 필요하면 `db/seed-*.sql` 도 한 번씩)
3. 기본 관리자 `admin` / `qwer1234` (`db/schema.sql` 이 넣음) 로 로그인 후 비밀번호 변경. 추가 관리자는 `/admin/signup.php` 에서 승인 코드로 가입 (필요 없으면 `signup_code` 를 `''` 로 비워 닫을 수 있음)
4. 업로드 제외: `docker/`, `docker-compose.yml`, `setup.*`, `.env*`, `*.md`, `.git*`, `.claude/`, `.codex/`, `app/storage/`, `uploads/posts/` (로컬 업로드 파일)
   (실수로 올라가도 루트 `.htaccess` 가 차단)
5. 코드 배포: `deploy` 브랜치에 push 하면 GitHub Actions(`.github/workflows/deploy.yml`)가 FTP 로 변경분 배포. 수동은 `ftp-deploy` 스킬. 설정은 README `자동 배포 (GitHub Actions)`

## 포트폴리오 이미지 작업

- 사용자가 포트폴리오 이미지 제작용 웹사이트 URL을 주면, 별도 지정이 없는 한 히어로 이미지·pro 상세 이미지·썸네일 세 장을 만든다.
- 히어로: 사이트의 브랜드 분위기에 맞는 배경을 ImageGen으로 제작하고 최종 크기는 정확히 1920×1080px로 저장한다. 중앙은 사용자가 로고를 넣을 수 있도록 넉넉히 비우고 로고·문구·웹 UI를 넣지 않는다.
- pro: 사이트를 먼저 1920×1080 PC 브라우저 화면으로 렌더링해 전체 내용을 캡처하고, 결과 이미지만 가로 1280px로 비율에 맞춰 축소한다. 브라우저 폭을 1280px로 설정해 캡처하지 않는다. 원래 PC 레이아웃을 유지하고 스크롤로 나타나는 콘텐츠와 마지막 영역까지 확인한다.
- 슬라이더·캐러셀·화면 전환 영역은 대표 화면 하나만 담는다. 다른 슬라이드를 보여주려고 같은 섹션을 반복해서 붙이지 않는다. 예: PROCESS나 포트폴리오 슬라이더는 각각 한 번만 캡처한다.
- 3D 전시·화면 전환형 사이트는 대표 공간과 주요 기능 화면을 PC 크기로 각각 캡처해 세로로 연결한다. 아주 긴 페이지는 구간별로 캡처해 반복·누락 없이 연결한다.
- 썸네일: 기존 `kumu.png`처럼 사이트 첫 화면의 특징이 보이는 560×560px 정사각형 이미지로 만든다. 로고와 주요 문구가 잘리지 않도록 캡처 영역을 고른다.
- 결과는 `aseets/imgs/portfolio-sub/`에 `<brand>-hero.png`, `<brand>-pro.png`, `<brand>.png` 이름으로 저장한다. 관리자 업로드 제한인 파일당 10MB 이하를 확인하고, 기존 파일이 있으면 사용자 요청 없이 덮어쓰지 않는다.

## Codex 프로젝트 스킬

- Codex 용 스킬은 `.codex/skills/` 에 둔다. 이 경로의 스킬은 이 지침을 통해 찾아 사용한다.
- fuse.lab 사이트의 FTP 업로드·배포·라이브 반영을 요청받으면 먼저 `.codex/skills/ftp-deploy/SKILL.md` 를 읽고 따른다. 스킬 생성·정리나 사용법 설명만 요청받은 경우에는 실제 배포를 실행하지 않는다.

## Git

- 팀원별 브랜치(`GYU`, `SURO`, `WON`) → `main` 으로 PR 머지.
- 기존 HTML/CSS/JS 경로·파일명 변경은 다른 브랜치와 충돌하므로 필요할 때만, 명시적으로.
