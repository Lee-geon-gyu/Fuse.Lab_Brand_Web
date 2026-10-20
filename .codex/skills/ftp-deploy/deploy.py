"""Codex용 FTP 배포 진입점. 실행 로직은 Claude 스킬과 공유한다."""

from pathlib import Path
import runpy


if __name__ == "__main__":
    shared_script = Path(__file__).resolve().parents[3] / ".claude/skills/ftp-deploy/deploy.py"
    runpy.run_path(str(shared_script), run_name="__main__")
