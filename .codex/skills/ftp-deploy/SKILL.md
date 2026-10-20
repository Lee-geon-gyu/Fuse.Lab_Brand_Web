---
name: ftp-deploy
description: fuse.lab 프로젝트를 카페24에 FTP로 배포할 때 사용한다. 사이트 업로드, 배포, 라이브 반영 요청을 처리하며 관리자 콘텐츠 편집이나 로컬 수정만 하는 작업에는 사용하지 않는다.
---

# 카페24 FTP 배포

이 저장소의 Claude 배포 스킬을 Codex에서도 사용한다. 프로젝트 루트의 `AGENTS.md`를 따르고 같은 폴더의 [deploy.py](deploy.py)를 실행한다. 이 진입점은 두 스킬이 공유하는 [.claude/skills/ftp-deploy/deploy.py](../../../.claude/skills/ftp-deploy/deploy.py)를 호출한다. 모든 명령은 저장소 루트에서 실행한다. Python 표준 라이브러리와 Git이 필요하며 별도 플러그인은 필요 없다.

## 접속 설정

접속 정보는 루트 `.env`의 `FTP_HOST`, `FTP_PORT`, `FTP_USER`, `FTP_PASSWORD`, `FTP_REMOTE_DIR`(`/www`)을 사용한다. `DEPLOY_URL`이 있으면 검증할 사이트 주소로 쓰고, 없으면 `https://{FTP_HOST}`를 사용한다. 비밀번호와 비밀 설정값은 출력하거나 커밋하지 않는다.

## 절차

1. 배포 요청의 범위와 Git 변경 상태를 확인한다. 스킬 설치·변환이나 사용법 설명만 요청받았다면 배포를 실행하지 않는다. 배포할 변경은 커밋되어 있어야 하며, 관련 없는 변경을 함께 커밋하지 않는다. `--allow-dirty`는 사용자가 요청한 경우에만 사용한다. 미추적 파일은 배포 대상에 포함되지 않는다.
2. 목록을 확인한다. 이 명령도 FTP에 접속해 마지막 배포 커밋을 읽지만 원격 파일을 변경하지 않는다.

   ```text
   python .codex/skills/ftp-deploy/deploy.py --dry-run
   ```

3. 요청받은 배포 범위와 목록이 맞으면 업로드한다.

   ```text
   python .codex/skills/ftp-deploy/deploy.py
   ```

   - 원격 `/www/.deploy-rev`부터 `HEAD`까지 바뀐 파일을 업로드하고 삭제된 파일은 원격에서도 삭제한다. 완료 후 `.deploy-rev`를 갱신한다.
   - `.deploy-rev`가 없거나 `--all`이면 배포 대상 전체를 업로드하며 원격 파일 삭제는 하지 않는다. 원격 커밋이 로컬에 없으면 안내대로 Git 이력을 가져와 다시 확인한다. 오류를 피하려고 임의로 `--all`로 바꾸지 않는다.
   - 배포 대상에 `db/schema.sql` 변경이 있고 스키마 적용이 필요한 경우 `--schema`를 추가한다. 스크립트가 일회용 PHP를 올려 스키마를 적용하고 바로 삭제한다.
4. 라이브 `/`, `/portfolio.php`, `/admin/login.php`가 HTTP 200이고 `/app/config.local.php`, `/db/schema.sql`이 HTTP 403인지 확인한다. 화면 변경이 있으면 해당 페이지를 브라우저로 열어 화면과 관련 콘솔 오류를 확인한다. 업로드·삭제·검증 결과와 남은 문제를 한국어로 보고한다.

## 서버 파일 보호

- 개발용 파일과 에이전트 설정 폴더는 배포에서 제외한다: `docker/`, `docker-compose.yml`, `setup.*`, `.env*`, `.git*`, `*.md`, `.claude/`, `.agents/`, `.codex/`.
- `app/config.local.php`, `app/storage/`, `uploads/posts/`는 서버 전용이다. 삭제하거나 로컬 파일로 덮어쓰지 않는다.
- FTP 로그인 홈이 이미 `/www`이므로 항상 `/www/...` 절대 경로를 사용한다.
- `db/dump.sql`은 운영 DB에 다시 적용하지 않는다. 운영 관리자에서 바꾼 콘텐츠는 운영 DB와 업로드 파일에 있으므로 Git 배포로 로컬에 동기화되지 않는다.
- 운영 관리자 소스 탭으로 변경한 PHP는 서버 파일 자체가 바뀐다. 배포할 같은 파일에 서버 수정이 있다는 증거가 있으면 원격 수정본을 받아 비교·반영한 뒤 덮어쓴다.
- 운영 설정 변경까지 요청받은 경우에만 `app/config.local.php`를 취급한다. 로컬 Docker도 이 파일을 읽으므로 작업 전에 로컬 파일 유무를 확인하고, 기존 파일은 보존하며 임시 생성한 운영 설정만 업로드 후 정리한다.

## 가림막

원격 `/www/maintenance.html`이 가림막이다. 사용자가 가림막 전환을 요청하거나 이미 승인한 경우에만 진행한다. 켤 때 `/www/index.php`를 `index.php.off`로 옮기고 기존 `index.html`을 백업한 뒤 `maintenance.html`을 `index.html`로 복사한다. 끌 때는 백업과 `index.php.off`를 원래 이름으로 되돌린다. `DirectoryIndex index.php index.html` 순서를 유지하고 기존 백업을 덮어쓰지 않는다.
