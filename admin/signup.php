<?php
require __DIR__ . '/../app/bootstrap.php';

view('admin/head', [
    'pageTitle' => '관리자 회원가입',
    'styles' => ['/css/admin-signup.css'],
    'scripts' => ['/js/admin-signup.js'],
]);
?>
    <main>
      <form class="signup-card" id="signup-form" novalidate>
        <div class="signup-card__heading">
          <h1>관리자 회원가입</h1>
        </div>

        <p class="signup-card__intro">회원 정보를 입력해 주세요</p>

        <div class="field">
          <label for="admin-id">ID <span class="required" aria-hidden="true">*</span></label>
          <div class="field__row">
            <input id="admin-id" name="adminId" type="text" placeholder="ID를 입력해 주세요" autocomplete="username" required />
            <button class="action" id="check-id" type="button">중복확인</button>
          </div>
        </div>

        <div class="field">
          <label for="password">비밀번호 <span class="required" aria-hidden="true">*</span></label>
          <input id="password" name="password" type="password" placeholder="비밀번호를 입력해 주세요" autocomplete="new-password" required />
        </div>

        <div class="field">
          <label for="password-confirm">비밀번호 확인 <span class="required" aria-hidden="true">*</span></label>
          <input id="password-confirm" name="passwordConfirm" type="password" placeholder="비밀번호를 다시 입력해 주세요" autocomplete="new-password" required />
        </div>

        <div class="field">
          <label for="admin-name">이름 <span class="required" aria-hidden="true">*</span></label>
          <input id="admin-name" name="adminName" type="text" placeholder="이름을 입력해 주세요" autocomplete="name" required />
        </div>

        <div class="field">
          <label for="approval-code">승인 코드 <span class="required" aria-hidden="true">*</span></label>
          <input id="approval-code" name="approvalCode" type="text" placeholder="승인 코드를 입력해 주세요" required />
        </div>

        <div class="signup-card__footer">
          <button class="action" type="submit">회원가입</button>
        </div>
        <p class="form-message" id="form-message" role="status" aria-live="polite"></p>
      </form>
    </main>
<?php view('admin/foot'); ?>
