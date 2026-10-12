<?php
/**
 * 관리자 비밀번호 재설정 (메일로 받은 ?token= 링크).
 * 토큰이 유효하면 새 비밀번호 입력 폼, 저장 후 로그인 화면으로 이동.
 */
require __DIR__ . '/../app/bootstrap.php';
// 주소창의 토큰이 외부 링크 Referer 로 새지 않도록
header('Referrer-Policy: no-referrer');

$token = (string) ($_GET['token'] ?? '');
$reset = find_valid_password_reset($token);
$message = '';

if ($reset !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['passwordConfirm'] ?? '');

    if (!csrf_valid()) {
        $message = '요청이 만료되었습니다. 다시 시도해 주세요.';
    } elseif (strlen($password) < ADMIN_PASSWORD_MIN) {
        $message = '비밀번호는 ' . ADMIN_PASSWORD_MIN . '자 이상 입력해 주세요.';
    } elseif ($password !== $passwordConfirm) {
        $message = '비밀번호가 일치하지 않습니다.';
    } elseif (complete_admin_password_reset($reset, $password)) {
        flash('login', '비밀번호가 변경되었습니다. 새 비밀번호로 로그인해 주세요.');
        redirect('/admin/login.php');
    } else {
        $reset = null;
    }
}

view('admin/head', [
    'pageTitle' => '비밀번호 재설정',
    'styles' => ['/css/admin-login.css'],
]);
?>
    <main>
<?php if ($reset === null): ?>
      <div class="login-card">
        <div class="login-card__heading"><h1>비밀번호 재설정</h1></div>
        <p class="feedback" role="alert">링크가 올바르지 않거나 만료되었습니다. 로그인 화면에서 비밀번호 찾기를 다시 요청해 주세요.</p>
        <div class="login-links">
          <a href="/admin/login.php">로그인 화면으로</a>
        </div>
      </div>
<?php else: ?>
      <form class="login-card" id="reset-form" method="post" action="/admin/reset-password.php?<?= e(http_build_query(['token' => $token])) ?>">
        <?= csrf_field() ?>
        <div class="login-card__heading"><h1>비밀번호 재설정</h1></div>

        <div class="field">
          <label for="reset-login-id">ID</label>
          <input id="reset-login-id" type="text" value="<?= e($reset['login_id']) ?>" autocomplete="username" readonly />
        </div>

        <div class="field">
          <label for="new-password">새 비밀번호</label>
          <input id="new-password" name="password" type="password" placeholder="<?= ADMIN_PASSWORD_MIN ?>자 이상 입력해 주세요" autocomplete="new-password" minlength="<?= ADMIN_PASSWORD_MIN ?>" required />
        </div>

        <div class="field">
          <label for="new-password-confirm">새 비밀번호 확인</label>
          <input id="new-password-confirm" name="passwordConfirm" type="password" placeholder="비밀번호를 다시 입력해 주세요" autocomplete="new-password" minlength="<?= ADMIN_PASSWORD_MIN ?>" required />
        </div>

        <div class="login-card__footer">
          <button class="primary-button" type="submit">비밀번호 변경</button>
        </div>
        <p class="feedback" role="status" aria-live="polite"><?= e($message) ?></p>
        <div class="login-links">
          <a href="/admin/login.php">로그인 화면으로</a>
        </div>
      </form>
<?php endif; ?>
    </main>
<?php view('admin/foot'); ?>
