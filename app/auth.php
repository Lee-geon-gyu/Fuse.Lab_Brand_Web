<?php
/**
 * 관리자 세션 인증.
 * 보호 페이지는 bootstrap 다음 줄에 require_admin();
 */

const ADMIN_LOGIN_ID_PATTERN = '/^[A-Za-z0-9._@-]{4,50}$/';
const ADMIN_PASSWORD_MIN = 8;

/** 로그인한 관리자 (없으면 null). 삭제된 계정은 세션이 남아 있어도 null. */
function current_admin(): ?array
{
    static $admin = false;
    if ($admin === false) {
        $admin = null;
        if (isset($_SESSION['admin_id'])) {
            $stmt = db()->prepare('SELECT id, login_id, name FROM admins WHERE id = ?');
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch() ?: null;
        }
    }
    return $admin;
}

function require_admin(): array
{
    $admin = current_admin();
    if ($admin === null) {
        redirect('/admin/login.php');
    }
    return $admin;
}

/** 아이디·비밀번호가 맞으면 세션에 로그인시키고 true */
function attempt_admin_login(string $loginId, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE login_id = ?');
    $stmt->execute([$loginId]);
    $row = $stmt->fetch();

    // 없는 아이디도 같은 시간이 걸리도록 더미 해시로 검증 (임의 문자열의 bcrypt 해시)
    $hash = $row['password_hash'] ?? '$2y$10$LMFenYZCUX2YQiQhZNSlDOlAPw2mqtp8rftxMRUpKJB3YOjY8dHMW';
    if (!password_verify($password, $hash) || !$row) {
        return false;
    }

    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$row['id']]);

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $row['id'];
    return true;
}

function logout_admin(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function admin_login_id_exists(string $loginId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM admins WHERE login_id = ?');
    $stmt->execute([$loginId]);
    return (bool) $stmt->fetchColumn();
}
