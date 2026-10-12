<?php
require __DIR__ . '/../app/bootstrap.php';

if (current_admin() !== null) {
    redirect('/admin/');
}

$signupCode = (string) app_config()['signup_code'];
$adminId = '';
$adminName = '';
$adminEmail = '';
$message = $signupCode === '' ? '현재 관리자 회원가입이 닫혀 있습니다.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $signupCode !== '') {
    $adminId = trim((string) ($_POST['adminId'] ?? ''));
    $adminName = trim((string) ($_POST['adminName'] ?? ''));
    $adminEmail = trim((string) ($_POST['adminEmail'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['passwordConfirm'] ?? '');
    $approvalCode = trim((string) ($_POST['approvalCode'] ?? ''));

    if (!csrf_valid()) {
        $message = '요청이 만료되었습니다. 다시 시도해 주세요.';
    } elseif (!preg_match(ADMIN_LOGIN_ID_PATTERN, $adminId)) {
        $message = 'ID는 영문, 숫자, . _ @ - 조합 4~50자로 입력해 주세요.';
    } elseif (strlen($password) < ADMIN_PASSWORD_MIN) {
        $message = '비밀번호는 ' . ADMIN_PASSWORD_MIN . '자 이상 입력해 주세요.';
    } elseif ($password !== $passwordConfirm) {
        $message = '비밀번호가 일치하지 않습니다.';
    } elseif ($adminName === '' || mb_strlen($adminName) > 50) {
        $message = '이름을 50자 이내로 입력해 주세요.';
    } elseif (!admin_email_valid($adminEmail)) {
        $message = '이메일을 올바른 형식(' . ADMIN_EMAIL_MAX . '자 이내)으로 입력해 주세요.';
    } elseif (!hash_equals($signupCode, $approvalCode)) {
        $message = '승인 코드가 올바르지 않습니다.';
    } elseif (admin_login_id_exists($adminId)) {
        $message = '이미 사용 중인 ID입니다.';
    } elseif (admin_email_exists($adminEmail)) {
        $message = '이미 다른 계정에 등록된 이메일입니다.';
    } else {
        try {
            db()->prepare('INSERT INTO admins (login_id, password_hash, name, email) VALUES (?, ?, ?, ?)')
                ->execute([$adminId, password_hash($password, PASSWORD_DEFAULT), $adminName, $adminEmail]);
            flash('login', '회원가입이 완료되었습니다. 로그인해 주세요.');
            redirect('/admin/login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            $message = '이미 사용 중인 ID입니다.';
        }
    }
}

view('admin/head', [
    'pageTitle' => '관리자 회원가입',
    'styles' => ['/css/admin-signup.css'],
    'scripts' => ['/js/admin-signup.js'],
]);
?>
    <main>
      <form class="signup-card" id="signup-form" method="post" action="/admin/signup.php" novalidate>
        <?= csrf_field() ?>
        <div class="signup-card__heading">
          <h1>관리자 회원가입</h1>
        </div>

        <p class="signup-card__intro">회원 정보를 입력해 주세요</p>

        <div class="field">
          <label for="admin-id">ID <span class="required" aria-hidden="true">*</span></label>
          <div class="field__row">
            <input id="admin-id" name="adminId" type="text" value="<?= e($adminId) ?>" placeholder="ID를 입력해 주세요" autocomplete="username" pattern="[A-Za-z0-9._@\-]{4,50}" title="영문, 숫자, . _ @ - 조합 4~50자" required />
            <button class="action" id="check-id" type="button">중복확인</button>
          </div>
        </div>

        <div class="field">
          <label for="password">비밀번호 <span class="required" aria-hidden="true">*</span></label>
          <input id="password" name="password" type="password" placeholder="비밀번호를 입력해 주세요" autocomplete="new-password" minlength="<?= ADMIN_PASSWORD_MIN ?>" required />
        </div>

        <div class="field">
          <label for="password-confirm">비밀번호 확인 <span class="required" aria-hidden="true">*</span></label>
          <input id="password-confirm" name="passwordConfirm" type="password" placeholder="비밀번호를 다시 입력해 주세요" autocomplete="new-password" required />
        </div>

        <div class="field">
          <label for="admin-name">이름 <span class="required" aria-hidden="true">*</span></label>
          <input id="admin-name" name="adminName" type="text" value="<?= e($adminName) ?>" placeholder="이름을 입력해 주세요" autocomplete="name" maxlength="50" required />
        </div>

        <div class="field">
          <label for="admin-email">이메일 <span class="required" aria-hidden="true">*</span></label>
          <input id="admin-email" name="adminEmail" type="email" value="<?= e($adminEmail) ?>" placeholder="ID/비밀번호 찾기에 사용할 이메일을 입력해 주세요" autocomplete="email" maxlength="<?= ADMIN_EMAIL_MAX ?>" required />
        </div>

        <div class="field">
          <label for="approval-code">승인 코드 <span class="required" aria-hidden="true">*</span></label>
          <input id="approval-code" name="approvalCode" type="text" placeholder="승인 코드를 입력해 주세요" required />
        </div>

        <div class="signup-card__footer">
          <button class="action" type="submit">회원가입</button>
        </div>
        <p class="form-message" id="form-message" role="status" aria-live="polite"><?= e($message) ?></p>
      </form>
    </main>
<?php view('admin/foot'); ?>
