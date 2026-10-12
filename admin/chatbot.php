<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

view('admin/head', [
    'pageTitle' => '챗봇 설정',
    'styles' => ['/css/admin-chatbot.css', '/css/admin-layout.css'],
    'scripts' => ['/js/admin-chatbot.js'],
]);
view('admin/layout-start', ['active' => 'chatbot']);
?>
    <main>
      <form class="settings-card" id="chatbot-settings">
        <div class="settings-card__heading"><h1>챗봇 설정</h1></div>

        <div class="form-row">
          <label for="ai-name">AI 이름</label>
          <input class="text-field" id="ai-name" name="aiName" type="text" placeholder="플레이스 홀더" />
        </div>

        <div class="form-row">
          <label for="guide-text">안내문구</label>
          <input class="text-field" id="guide-text" name="guideText" type="text" placeholder="플레이스 홀더" />
        </div>

        <div class="form-row">
          <span class="row-label" id="icon-label">아이콘</span>
          <div class="upload-control">
            <label class="upload-button" for="icon-file">
              <img id="icon-preview" src="/aseets/icons/admin/all-inbox.svg" alt="" width="24" height="24" />
              <span>파일 선택</span>
              <input id="icon-file" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" aria-labelledby="icon-label" />
            </label>
            <span class="upload-name" id="upload-name" aria-live="polite"></span>
          </div>
        </div>

        <div class="form-row">
          <label for="theme-color">테마 색상</label>
          <input class="text-field" id="theme-color" name="themeColor" type="text" inputmode="text" placeholder="플레이스 홀더" aria-describedby="color-help" />
          <span id="color-help" hidden>16진수 색상 코드, 예: #6a00ff</span>
        </div>

        <div class="form-row">
          <span class="row-label" id="tone-label">말투 설정</span>
          <fieldset class="tone-options" aria-labelledby="tone-label">
            <label class="tone-option">
              <input type="radio" name="tone" value="friendly" checked />
              <span class="tone-option__name">친근하게</span>
              <span class="tone-option__example"><span>밝고 친근한 예시</span><span>~ 알려드릴게요 😄</span></span>
            </label>
            <label class="tone-option">
              <input type="radio" name="tone" value="calm" />
              <span class="tone-option__name">차분하게</span>
              <span class="tone-option__example"><span>차분하고 신뢰감 예시</span><span>~ 안내드리겠습니다.</span></span>
            </label>
            <label class="tone-option">
              <input type="radio" name="tone" value="warm" />
              <span class="tone-option__name">따듯하게</span>
              <span class="tone-option__example"><span>밝고 친근한 예시</span><span>~ 같이 살펴볼까요?</span></span>
            </label>
            <label class="tone-option">
              <input type="radio" name="tone" value="concise" />
              <span class="tone-option__name">간결하게</span>
              <span class="tone-option__example"><span>간결하고 사실적 예시</span><span>~ 안내드립니다.</span></span>
            </label>
          </fieldset>
        </div>

        <div class="welcome-field">
          <label for="welcome-message">환영 메세지</label>
          <textarea id="welcome-message" name="welcomeMessage">안녕하세요, 궁금하신 점을 편하게 물어보세요 😄</textarea>
        </div>

        <div class="form-actions">
          <button class="delete-button" id="delete-settings" type="button">삭제</button>
          <button class="quiet-button" id="cancel-settings" type="button">취소</button>
          <button class="quiet-button" id="draft-settings" type="button">임시저장</button>
          <button class="save-button" type="submit">저장</button>
        </div>
      </form>
    </main>
    <div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<?php
view('admin/layout-end');
view('admin/foot');
