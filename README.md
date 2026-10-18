### Fuse.Lab_Brand_Web



## Hello we are fuse.labs member



## I am team leader "Lee"

---

## 개발 환경

- 운영: 카페24 10G 광아우토반 FullSSD+ (PHP 8.2 / MariaDB), 설치형 프레임워크 없이 순수 PHP
- 로컬: Docker (`php:8.2-apache` + `mariadb:10.6` + phpMyAdmin)

새 컴퓨터 세팅: [Docker Desktop](https://www.docker.com/products/docker-desktop/) 설치·실행 → 저장소 clone → 아래 실행

- Windows: `setup.bat` 더블클릭
- macOS / Linux: `./setup.sh`

Docker 확인 → `.env` 생성(없을 때만) → 컨테이너 빌드/기동 → DB 연결 확인 → `db/schema.sql` 적용 → `db/dump.sql` 적용(빈 DB 일 때만) → 접속 주소 출력. 다시 실행해도 안전.

| 서비스 | 주소 |
| --- | --- |
| 사이트 | http://localhost:8088 |
| 관리자 | http://localhost:8088/admin/ |
| phpMyAdmin | http://localhost:8089 |
| MariaDB (외부 클라이언트) | `localhost:3310` / `fuselab` / `fuselab` |

포트 충돌 시 `.env` 의 `WEB_PORT`, `PMA_PORT`, `DB_PORT_HOST` 값을 바꾸고 setup 을 다시 실행.

### 관리자 계정

기본 계정 `admin` / `qwer1234` 가 `db/schema.sql` 실행 시 들어간다 (이름 `관리자`, 이메일 `admin@fuselab.example`). 같은 ID 가 이미 있으면 비밀번호를 덮어쓰지 않는다.
공개 저장소에 비밀번호가 적혀 있으므로 운영에서는 배포 직후 비밀번호를 바꾼다 (로그인 화면 > 비밀번호 찾기, 또는 phpMyAdmin 에서 `password_hash` 교체).

추가 계정은 `/admin/signup.php` 에서 승인 코드로 가입한다 (ID·비밀번호·이름·이메일).

- 로컬 승인 코드: `fuselab-dev` (`.env` 의 `ADMIN_SIGNUP_CODE` 로 변경)
- 운영 승인 코드: `app/config.local.php` 의 `signup_code` (비우면 가입 불가)
- ID/비밀번호 찾기: 로그인 화면 다이얼로그 → 가입 이메일로 ID 안내 또는 비밀번호 재설정 링크(1시간 유효) 메일
  - 로컬은 메일을 실제로 보내지 않고 `app/storage/mail.log` 에 남긴다 (재설정 링크도 여기서 확인)
  - 이메일이 없는 계정(이메일 기능 이전 가입)은 phpMyAdmin 에서 `admins.email` 을 채워야 찾기 가능

### 로컬 공유 데이터 (`db/dump.sql`)

DB 데이터는 Docker 볼륨에 있어서 git 으로 공유되지 않는다. 그래서 콘텐츠 테이블(`boards`, `board_tables`, `page_slots`, `subpages`, `portfolios`, `posts`, `post_files`, `chatbot_settings`) 데이터만 `db/dump.sql` 로 커밋한다.
계정(`admins`)·견적문의·비밀번호 재설정·방문 집계는 넣지 않는다 (공개 저장소). 계정은 `db/schema.sql` 의 기본 관리자를 쓴다.

- setup 은 `posts`·`portfolios` 가 둘 다 비어 있을 때만 `db/dump.sql` 을 넣는다. 로컬에서 고친 데이터는 덮어쓰지 않는다
- 덤프로 초기화: `docker compose down -v` (로컬 DB 삭제) → setup 다시 실행
- 덤프 갱신 (내 로컬 데이터를 공유할 때, cmd·PowerShell·bash 공통):

```
docker compose exec -T db mariadb-dump -ufuselab -pfuselab --no-tablespaces --no-create-info --insert-ignore --complete-insert --skip-extended-insert --skip-dump-date --skip-comments --skip-add-locks --skip-disable-keys --skip-triggers --default-character-set=utf8mb4 -r /tmp/dump.sql fuselab boards board_tables page_slots subpages portfolios posts post_files chatbot_settings
docker compose cp db:/tmp/dump.sql db/dump.sql
```

컨테이너 안에서 파일로 쓰고 복사하는 이유: PowerShell 의 `>` 리다이렉트는 UTF-16 으로 저장해 한글이 깨진다.
업로드한 이미지(`uploads/posts/`)는 git 제외라서 덤프에 업로드 이미지 경로가 들어가면 다른 PC 에서는 깨진다. 공유할 이미지는 `aseets/` 경로를 쓴다.

덤프 없이 시안 데이터만 넣을 때는 아래를 한 번씩 실행 (데이터가 이미 있으면 아무것도 넣지 않음. 카페24 는 phpMyAdmin 에서 같은 파일 실행)

```
docker compose exec -T db mariadb -ufuselab -pfuselab fuselab < db/seed-portfolios.sql
docker compose exec -T db mariadb -ufuselab -pfuselab fuselab < db/seed-portfolio.sql
```

## 구조

- 저장소 루트 = 웹 루트 (카페24 `www/` 에 그대로 업로드)
- `*.html`, `sub-pages/` — 퍼블리싱 원본 (유지)
- `index.php`, `portfolio*.php`, `admin/` — 퍼블리싱 화면을 공통 레이아웃으로 조합한 PHP 페이지 (관리자: http://localhost:8088/admin/)
- `app/` — PHP 공통 코드 (bootstrap, 설정, DB, `views/` 레이아웃). `.htaccess` 로 웹 접근 차단
- `uploads/posts/` — 게시판 첨부·포트폴리오·챗봇 아이콘 이미지 (git 제외, `uploads/.htaccess` 로 스크립트 실행 차단)
- `app/storage/` — 로컬 메일 로그 (git 제외, 웹 접근 차단)
- PHP 파일 목록: [page-map.md](page-map.md) / 화면 설계: [docs/design.md](docs/design.md)

## 카페24 배포

1. `app/config.local.example.php` → `app/config.local.php` 로 복사 후 카페24 DB 정보, `signup_code`, `mail_from`(보내는 사람), `site_url`(사이트 주소, 재설정 메일 링크·공유 썸네일(`og:image`) 절대 주소용) 입력
2. 카페24 DB 관리(phpMyAdmin) > SQL 에서 `db/schema.sql` 실행. 이미 이전 버전 스키마를 적용한 DB 에 다시 실행해도 안전함 (`admins.email`, `posts.portfolio_id`, `boards.type` 은 `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` 로 추가됨, `boards`·`board_tables` 테이블·기본 `포트폴리오` 게시판도 이때 생김)
   - 게시판 DB 관리 전환 이전(공지사항·이벤트·자료실이 코드에 고정돼 있던 버전)에서 올리는 경우: 그 세 게시판 글은 목록 필터에 더 이상 나오지 않으므로 아래 [게시판 관리](#게시판-관리)의 1회성 정리를 한다
3. 기본 계정 `admin` / `qwer1234` 로 `/admin/login.php` 로그인 후 비밀번호 변경 (이메일을 실제 주소로 바꿔야 비밀번호 찾기 메일을 받을 수 있음)
4. 시안 데이터가 필요하면 `db/seed-portfolios.sql`(포트폴리오 목록 6개) → `db/seed-portfolio.sql`(메인 슬라이드 4개, VIEW PROJECT 를 목록 1~4번째에 연결) 순서로 한 번씩 실행
5. `uploads/.htaccess` 를 함께 업로드. `uploads/posts/` 는 첫 업로드 때 자동 생성되며, 실패하면 FTP 로 만들고 쓰기 권한(755/707) 부여
6. 배포 후 확인: 견적문의 제출, ID/비밀번호 찾기 메일 수신(카페24 `mail()` 발송 여부 미확인), 이미지 업로드
7. `docker/`, `docker-compose.yml`, `*.md` 는 업로드하지 않음 (업로드돼도 `.htaccess` 로 차단)

## 관리자 기능

| 메뉴 | 기능 |
| --- | --- |
| 대시보드 | 월별 방문자(주차별 막대)·챗봇 사용률(방문 세션 대비 챗봇을 연 세션), 관리자 계정 현황, 최근 견적문의·게시글 |
| 견적문의 | 메인 폼으로 들어온 문의 목록·검색·상세, 상태(접수 완료/검토 중/답변 대기/처리 완료/반려)·메모 저장, 삭제 |
| 게시판 | 게시판별 글 작성/수정/노출/삭제, 첨부. 포트폴리오 게시판 글 = 메인 슬라이드 |
| 게시판 관리 | 게시판 생성(키+이름+유형)·이름 변경·순서 변경·삭제(글·첨부 또는 표 데이터 함께 삭제). 유형 `글 목록` = 게시글, `표` = 열·행을 직접 편집(`/admin/board-table.php`). 게시판 이름은 겹칠 수 없음(예: `포트폴리오-main` / `포트폴리오-sub`). 고정 게시판 `포트폴리오-main`(메인 슬라이드)·`포트폴리오-sub`(포트폴리오 목록·상세, 관리 화면 `/admin/portfolios.php`)은 삭제 불가 |
| 서브페이지 관리 | 서브페이지 목록(기존 PHP 페이지 + 만든 페이지) → 편집에서 HTML·CSS·JS, 기존 페이지는 `slot()` 문구도. `새 서브페이지 만들기`(주소+이름) → 목록에 추가되고 `/page/주소` 로 열림 (노출을 켜야 방문자에게 보임). 프로젝트 항목은 게시판 > 포트폴리오-sub |
| 챗봇 설정 | 사용자 사이트 우하단 챗봇의 이름·안내문구·아이콘·색상·말투·환영 메시지 + AI 연동(Claude API 키·모델). 키가 있으면 Claude 가 답하고, 키가 없거나 호출이 실패하면 키워드 규칙 기반 응답. API 키는 `chatbot_ai` 테이블에만 저장(화면에는 가린 값, `db/dump.sql` 제외). 운영 반영은 `db/schema.sql` 재실행 |

방문자 수는 사용자 페이지(메인·포트폴리오 목록·상세)를 연 세션을 하루 1회씩 센다.

### 서브페이지 (`/admin/subpages.php`)

- **기존 페이지**: 루트에서 `view('site/head'` 를 쓰는 PHP (메인·`page.php` 제외). 관리자가 본문 아래 HTML 블록·CSS(`<head>` 의 `<style>`)·JS(`<body>` 끝 `<script>`)를 덧붙인다. 처음 저장할 때 `subpages` 행이 생김
  - **소스 탭**: PHP 파일 자체를 서버에서 직접 수정. 저장 전 PHP 문법 검사(오류면 저장 안 함), 편집 중 파일이 바뀌었으면 거부, 저장마다 이전 파일을 `app/storage/source-backups/` 에 백업(파일별 최근 20개, 화면에서 되돌리기). 파일의 원래 줄바꿈(LF/CRLF) 유지
  - 서버에서 고친 소스는 git 에 없다. 유지할 변경이면 FTP 로 받아 커밋하고, 다음 FTP 배포가 서버 수정본을 덮어쓰지 않게 주의
  - 관리자 계정 = 서버 PHP 실행 권한이 된다. 필요 없으면 `app/config.local.php` 에 `'source_edit' => false` (탭이 사라짐). 서버에서 `app/storage/`·루트 PHP 파일에 웹서버 쓰기 권한이 있어야 동작
- **만든 페이지**: `새 서브페이지 만들기` → 숨김 상태로 생성 → HTML(본문 전체)·CSS·JS 작성 → 설정에서 노출. `/page/{주소}` (`.htaccess` 리라이트 → `page.php`). 숨김 페이지는 로그인한 관리자에게만 보임
- HTML·CSS·JS 는 이스케이프 없이 그대로 출력한다 (관리자만 입력)

#### 페이지 문구 슬롯 (`slot()`)

사용자 페이지(루트 `*.php`, `app/views/site/*.php`)의 문구를 관리자에서 바꾸게 하려면 PHP 에 슬롯을 적는다. 서브페이지 편집의 `문구` 탭이 소스의 호출을 읽어 칸을 만들므로 관리자 화면 코드는 고칠 필요가 없다.

```php
<h2><?= slot('heading', 'WEB EXPERIENCES CRAFTED') ?></h2>      <!-- 한 줄 (이스케이프) -->
<p><?= slot('intro', '', 'multiline') ?></p>                     <!-- 여러 줄 (이스케이프 + 줄바꿈) -->
<div><?= slot('notice_html', '', 'html') ?></div>                <!-- HTML (관리자 입력 그대로) -->
<?php view('site/head', ['pageTitle' => slot_value('page_title', '포트폴리오 목록')]); ?>  <!-- 가공 전 값 -->
```

- 키·기본값·유형은 문자열 그대로 적는다 (변수로 넘기면 관리 화면에 나오지 않음). 키는 영소문자·숫자·`_.-` 60자 이내
- 값은 `page_slots` 에 "파일 경로 + 키" 로 저장. 같은 키를 한 파일에서 여러 번 쓰면 같은 값이 나온다
- `html` 슬롯은 이스케이프하지 않으므로 레이아웃·스크립트가 그대로 들어간다 (관리자만 입력)

### 게시판 관리

게시판 종류는 `boards` 테이블에 있고 관리자 > 게시판 관리(`/admin/boards.php`)에서 바꾼다. 기본은 `포트폴리오`(키 `portfolio`) 하나.

- 키(`posts.board` 에 저장)는 영소문자·숫자·하이픈 20자 이내, 만든 뒤 변경 불가. 이름·순서는 언제든 변경
- 삭제하면 그 게시판 글과 업로드 이미지·첨부파일까지 함께 지운다 (확인창 후, 되돌릴 수 없음)
- 유형은 만들 때 고른다 (`글 목록` / `표`, 이후 변경 불가). `표` 게시판은 게시판 목록·글작성 선택지에 나오지 않고, 게시판 관리의 `표 편집`에서 열 추가·이름·순서·삭제, 행 추가·순서·삭제, 칸 입력 후 저장한다 (열 30개·행 1,000개·칸 1,000자까지)
- `portfolio` 는 메인 슬라이드에 쓰이므로 삭제 불가 (이름 변경은 가능)

예전 고정 게시판(`notice` 공지사항·`event` 이벤트·`resources` 자료실) 글 1회성 정리 — `db/schema.sql` 재실행 후 둘 중 하나:

1. 관리자에서 (권장, 첨부파일까지 삭제): 게시판 목록 → 게시판 `전체` → 해당 글 체크 → 삭제.
   또는 게시판 관리에서 같은 키(`notice` 등)로 게시판을 잠깐 만든 뒤 그 게시판을 삭제하면 글·첨부가 한 번에 지워진다
2. phpMyAdmin SQL (첨부 행은 `ON DELETE CASCADE` 로 함께 삭제, 서버의 `uploads/posts/` 파일은 남으므로 필요하면 FTP 로 정리):

```sql
DELETE FROM posts WHERE board IN ('notice', 'event', 'resources');
```

### 메인 포트폴리오 관리

관리자 > 게시판에서 게시판을 `포트폴리오` 로 골라 글을 쓰면 메인 포트폴리오 슬라이드가 된다.

- 노출된 글을 먼저 쓴 순서대로 보여주고, 글 수만큼 슬라이드가 늘어남 (번호 `01 / 05` 자동)
- 제목 = 큰 제목, 상단 문구 = 제목 위 작은 글자, 내용 = 설명(줄바꿈 유지), 배경 이미지 필수 (JPG·PNG·WEBP·GIF 10MB 이하)
- 숨김 처리하면 메인에서 빠짐
- 관리자로 로그인한 채 메인을 열면 슬라이드마다 `이 슬라이드 수정` 버튼과 화면 오른쪽 아래 `포트폴리오 슬라이드 추가` 메뉴가 보임. 저장하면 메인으로 돌아옴
