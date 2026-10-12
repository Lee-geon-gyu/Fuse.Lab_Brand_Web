# Page Map

PHP 파일 목록. 파일을 추가/삭제하면 이 표도 함께 수정한다.

| 파일 | 구분 | 설명 | 웹 접근 |
| --- | --- | --- | --- |
| `app/bootstrap.php` | 공통 | 모든 페이지 최상단 require. 타임존·세션·에러 표시·DB 헬퍼 로드, `e()` 이스케이프 | 차단 |
| `app/config.php` | 공통 | 설정 로더 (도커 환경변수 → `config.local.php` 덮어쓰기) | 차단 |
| `app/config.local.example.php` | 공통 | 카페24 운영용 설정 예시 (`config.local.php`로 복사, git 제외) | 차단 |
| `app/db.php` | 공통 | `db()` — PDO(MariaDB, utf8mb4) 싱글턴 | 차단 |
