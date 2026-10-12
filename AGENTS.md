# AGENTS.md

fuse.lab 브랜드 웹사이트. 정적 HTML 퍼블리싱 위에 필요한 기능만 순수 PHP 로 구현한다.

## 언어

- 답변, 진행 보고, 커밋 메시지 설명, 문서는 한국어. 코드·명령어·경로·식별자는 원문 그대로.

## 운영 환경 (기준)

- 카페24 10G 광아우토반 FullSSD+ — **PHP 8.2**, MariaDB, Apache(`.htaccess` 사용)
- 설치형 프로그램/프레임워크 없음. Composer·npm 빌드 없이 FTP 업로드만으로 동작해야 한다.
- 저장소 루트 = 웹 루트 (`www/` 에 그대로 업로드)

## 로컬 개발

- `setup.bat` (Windows) / `./setup.sh` (macOS·Linux) — Docker 기동 + DB 연결 확인
- 사이트 `http://localhost:8088`, phpMyAdmin `:8089`, MariaDB `localhost:3310` (`fuselab`/`fuselab`)
- 포트는 `.env` (`.env.example` 참고), 종료 `docker compose down`
- PHP CLI 는 컨테이너에서: `docker compose exec web php ...`

## 구조

| 경로 | 내용 |
| --- | --- |
| `*.html`, `sub-pages/` | 퍼블리싱 페이지 (사용자/관리자) |
| `css/`, `js/`, `script.js` | 스타일·스크립트. `GYU.css` / `SURO.css` / `WON.css` 는 팀원별 파일 |
| `aseets/` | 이미지·아이콘 (폴더명 오타지만 경로가 HTML 에 박혀 있으므로 변경 금지) |
| `app/` | PHP 공통 코드. `.htaccess` 로 웹 접근 차단 |
| `docker/`, `docker-compose.yml`, `setup.*` | 로컬 개발 전용. 배포 대상 아님 |
| `page-map.md` | PHP 파일 목록 표 |

외부 라이브러리는 CDN 사용 (GSAP, ScrollTrigger, three.js r128, Pretendard).

## PHP 규칙

- 모든 페이지 최상단: `require __DIR__ . '/app/bootstrap.php';` (하위 폴더는 상대경로 조정)
- PHP 8.2 문법까지만. 8.3+ 기능(typed class constants, `json_validate`, `|>` 등) 금지.
- DB 는 `db()` (PDO, utf8mb4) + prepared statement 만 사용. 문자열로 쿼리 조립 금지.
- 출력은 `e()` 로 이스케이프. 비밀번호는 `password_hash` / `password_verify`.
- 상태 변경 요청(POST)은 CSRF 토큰 검증.
- 설정값은 `app_config()`. 비밀값을 코드에 하드코딩 금지 — 운영 값은 `app/config.local.php` (git 제외).
- include 전용 파일은 `app/` 아래에 둔다. 웹에서 직접 열리는 파일만 루트/하위 폴더에 둔다.
- SQL 은 MariaDB 10.x 공통 문법 위주. 로컬은 10.6 이지만 운영 버전은 미확인이므로 최신 전용 기능은 피한다.

## 문서 유지

- PHP 파일을 추가·삭제·역할 변경하면 **같은 작업에서** `page-map.md` 표를 갱신한다.
  - 열: `파일 | 구분 | 설명 | 웹 접근`
- 실행 방법·포트·배포 절차가 바뀌면 `README.md` 도 갱신한다.

## 배포 (카페24)

1. `app/config.local.example.php` → `app/config.local.php` 복사 후 카페24 DB 정보 입력
2. 업로드 제외: `docker/`, `docker-compose.yml`, `setup.*`, `.env*`, `*.md`, `.git*`
   (실수로 올라가도 루트 `.htaccess` 가 차단)

## Git

- 팀원별 브랜치(`GYU`, `SURO`, `WON`) → `main` 으로 PR 머지.
- 기존 HTML/CSS/JS 경로·파일명 변경은 다른 브랜치와 충돌하므로 필요할 때만, 명시적으로.
