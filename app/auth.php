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

const ADMIN_EMAIL_MAX = 255;
const ADMIN_RESET_TTL_MINUTES = 60;
const ADMIN_RECOVERY_INTERVAL_SECONDS = 30;

function admin_email_valid(string $email): bool
{
    return $email !== '' && strlen($email) <= ADMIN_EMAIL_MAX && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function admin_email_exists(string $email): bool
{
    $stmt = db()->prepare('SELECT 1 FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    return (bool) $stmt->fetchColumn();
}

/** 이메일로 가입된 계정이 있으면 ID 안내 메일 발송 (결과는 호출자에게 알리지 않음 — 계정 노출 방지) */
function send_admin_id_reminder(string $email): void
{
    $stmt = db()->prepare('SELECT login_id, name, email FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    foreach ($stmt->fetchAll() as $row) {
        send_mail(
            $row['email'],
            '[fuse.lab] 관리자 ID 안내',
            $row['name'] . "님, 요청하신 관리자 ID를 안내드립니다.\n\nID: " . $row['login_id']
            . "\n\n본인이 요청하지 않았다면 이 메일을 무시해 주세요."
        );
    }
}

/**
 * ID·이메일이 일치하면 재설정 토큰을 만들고 링크 메일 발송.
 * @param string $resetUrl 토큰을 붙일 재설정 페이지 절대 URL (예: https://host/admin/reset-password.php)
 */
function request_admin_password_reset(string $loginId, string $email, string $resetUrl): void
{
    $stmt = db()->prepare('SELECT id, name, email FROM admins WHERE login_id = ? AND email = ?');
    $stmt->execute([$loginId, $email]);
    $admin = $stmt->fetch();
    if (!$admin) {
        return;
    }

    $token = bin2hex(random_bytes(32));
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // 이전에 발급한 미사용 토큰은 무효화
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL')
            ->execute([$admin['id']]);
        $pdo->prepare('INSERT INTO password_resets (admin_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))')
            ->execute([$admin['id'], hash('sha256', $token), ADMIN_RESET_TTL_MINUTES]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    send_mail(
        $admin['email'],
        '[fuse.lab] 관리자 비밀번호 재설정',
        $admin['name'] . "님, 아래 링크에서 새 비밀번호를 설정해 주세요. 링크는 "
        . ADMIN_RESET_TTL_MINUTES . "분 동안 한 번만 사용할 수 있습니다.\n\n"
        . $resetUrl . '?' . http_build_query(['token' => $token])
        . "\n\n본인이 요청하지 않았다면 이 메일을 무시해 주세요."
    );
}

/** 사용 가능한(존재·미사용·미만료) 재설정 토큰이면 ['id', 'admin_id', 'login_id'], 아니면 null */
function find_valid_password_reset(string $token): ?array
{
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
        return null;
    }
    $stmt = db()->prepare(
        'SELECT r.id, r.admin_id, a.login_id FROM password_resets r JOIN admins a ON a.id = r.admin_id
         WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()'
    );
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetch() ?: null;
}

/** 토큰을 사용 처리하고 비밀번호 변경. 동시에 다른 요청이 먼저 사용했으면 false */
function complete_admin_password_reset(array $reset, string $password): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $used = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ? AND used_at IS NULL AND expires_at > NOW()');
        $used->execute([$reset['id']]);
        if ($used->rowCount() !== 1) {
            $pdo->rollBack();
            return false;
        }
        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['admin_id']]);
        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
