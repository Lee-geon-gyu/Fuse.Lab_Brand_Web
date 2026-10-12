<?php
require __DIR__ . '/../app/bootstrap.php';

view('admin/head', [
    'pageTitle' => '관리자 로그인',
    'styles' => ['/css/admin-login.css'],
    'scripts' => ['/js/admin-login.js'],
]);
?>
    <main>
      <form class="login-card" id="login-form" novalidate>
        <div class="login-card__heading"><h1>관리자 로그인</h1></div>

        <div class="field">
          <label for="login-id">이메일(ID)</label>
          <input id="login-id" name="loginId" type="text" placeholder="ID 또는 이메일을 입력해 주세요" autocomplete="username" required />
        </div>

        <div class="field">
          <label for="login-password">비밀번호</label>
          <input id="login-password" name="password" type="password" placeholder="비밀번호를 입력해 주세요" autocomplete="current-password" required />
        </div>

        <div class="login-links">
          <a href="/admin/signup.php">회원가입</a>
          <span class="login-links__divider" aria-hidden="true"><img src="/aseets/icons/admin/login-divider.svg" alt="" width="21" height="1" /></span>
          <button id="open-recovery" type="button">ID/비밀번호 찾기</button>
        </div>

        <div class="login-card__footer">
          <button class="primary-button" type="submit">로그인</button>
        </div>
        <p class="feedback" id="login-feedback" role="status" aria-live="polite"></p>
      </form>
    </main>

    <dialog class="recovery-dialog" id="recovery-dialog" aria-labelledby="recovery-title">
      <div class="recovery-dialog__header">
        <h2 id="recovery-title">ID/비밀번호 찾기</h2>
        <button class="recovery-dialog__close" id="close-recovery" type="button" aria-label="닫기">×</button>
      </div>
      <div class="recovery-tabs" role="tablist" aria-label="계정 찾기 종류">
        <button id="id-tab" type="button" role="tab" aria-controls="id-panel" aria-selected="true">ID 찾기</button>
        <button id="password-tab" type="button" role="tab" aria-controls="password-panel" aria-selected="false">비밀번호 찾기</button>
      </div>
      <form class="recovery-panel" id="id-panel" role="tabpanel" aria-labelledby="id-tab" novalidate>
        <div class="recovery-field">
          <label for="find-email">가입한 이메일</label>
          <input id="find-email" type="email" placeholder="이메일을 입력해 주세요" autocomplete="email" required />
        </div>
        <button class="primary-button" type="submit">ID 찾기</button>
      </form>
      <form class="recovery-panel" id="password-panel" role="tabpanel" aria-labelledby="password-tab" novalidate hidden>
        <div class="recovery-field">
          <label for="find-id">ID</label>
          <input id="find-id" type="text" placeholder="ID를 입력해 주세요" autocomplete="username" required />
        </div>
        <div class="recovery-field">
          <label for="reset-email">가입한 이메일</label>
          <input id="reset-email" type="email" placeholder="이메일을 입력해 주세요" autocomplete="email" required />
        </div>
        <button class="primary-button" type="submit">비밀번호 찾기</button>
      </form>
      <p class="feedback" id="recovery-feedback" role="status" aria-live="polite"></p>
    </dialog>
<?php view('admin/foot'); ?>
