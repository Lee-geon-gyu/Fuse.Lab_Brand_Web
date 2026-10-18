<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$settings = chatbot_settings();
$ai = chatbot_ai_public();
$defaultIcon = '/aseets/icons/admin/all-inbox.svg';

view('admin/head', [
    'pageTitle' => '챗봇 설정',
    'styles' => ['/css/admin-chatbot.css', '/css/admin-layout.css'],
    'scripts' => ['/js/admin-chatbot.js'],
    'bodyData' => [
        'chatbot-api' => '/admin/chatbot-api.php',
        'csrf' => csrf_token(),
        'default-icon' => $defaultIcon,
    ],
]);
view('admin/layout-start', ['active' => 'chatbot']);
?>
    <main>
      <form class="settings-card" id="chatbot-settings" style="--preview-color: <?= e($settings['theme_color']) ?>">
        <div class="settings-card__heading"><h1>챗봇 설정</h1></div>

        <div class="form-row">
          <label for="ai-name">AI 이름</label>
          <input class="text-field" id="ai-name" name="aiName" type="text" maxlength="50" required placeholder="예: fuse.lab AI" value="<?= e($settings['ai_name']) ?>" />
        </div>

        <div class="form-row">
          <label for="guide-text">안내문구</label>
          <input class="text-field" id="guide-text" name="guideText" type="text" maxlength="200" placeholder="챗봇 이름 아래에 보이는 문구" value="<?= e($settings['guide_text']) ?>" />
        </div>

        <div class="form-row">
          <span class="row-label" id="icon-label">아이콘</span>
          <div class="upload-control">
            <label class="upload-button" for="icon-file">
              <img id="icon-preview" src="<?= e($settings['icon'] ?? $defaultIcon) ?>" data-icon="<?= e($settings['icon'] ?? '') ?>" alt="" width="24" height="24" />
              <span>파일 선택</span>
              <input id="icon-file" type="file" accept="image/png,image/jpeg,image/webp" aria-labelledby="icon-label" />
            </label>
            <span class="upload-name" id="upload-name" aria-live="polite"></span>
          </div>
        </div>

        <div class="form-row">
          <label for="theme-color">테마 색상</label>
          <input class="text-field" id="theme-color" name="themeColor" type="text" inputmode="text" maxlength="7" placeholder="#6a00ff" aria-describedby="color-help" value="<?= e($settings['theme_color']) ?>" />
          <span id="color-help" hidden>16진수 색상 코드, 예: #6a00ff</span>
        </div>

        <div class="form-row">
          <span class="row-label" id="tone-label">말투 설정</span>
          <fieldset class="tone-options" aria-labelledby="tone-label">
            <label class="tone-option">
              <input type="radio" name="tone" value="friendly"<?= $settings['tone'] === 'friendly' ? ' checked' : '' ?> />
              <span class="tone-option__name">친근하게</span>
              <span class="tone-option__example"><span>밝고 친근한 예시</span><span>~ 알려드릴게요 😄</span></span>
            </label>
            <label class="tone-option">
              <input type="radio" name="tone" value="calm"<?= $settings['tone'] === 'calm' ? ' checked' : '' ?> />
              <span class="tone-option__name">차분하게</span>
              <span class="tone-option__example"><span>차분하고 신뢰감 예시</span><span>~ 안내드리겠습니다.</span></span>
            </label>
            <label class="tone-option">
              <input type="radio" name="tone" value="warm"<?= $settings['tone'] === 'warm' ? ' checked' : '' ?> />
              <span class="tone-option__name">따듯하게</span>
              <span class="tone-option__example"><span>밝고 친근한 예시</span><span>~ 같이 살펴볼까요?</span></span>
            </label>
            <label class="tone-option">
              <input type="radio" name="tone" value="concise"<?= $settings['tone'] === 'concise' ? ' checked' : '' ?> />
              <span class="tone-option__name">간결하게</span>
              <span class="tone-option__example"><span>간결하고 사실적 예시</span><span>~ 안내드립니다.</span></span>
            </label>
          </fieldset>
        </div>

        <div class="welcome-field">
          <label for="welcome-message">환영 메세지</label>
          <textarea id="welcome-message" name="welcomeMessage" maxlength="500"><?= e($settings['welcome_message']) ?></textarea>
        </div>

        <div class="form-actions">
          <button class="delete-button" id="delete-settings" type="button">삭제</button>
          <button class="quiet-button" id="cancel-settings" type="button">취소</button>
          <button class="quiet-button" id="draft-settings" type="button">임시저장</button>
          <button class="save-button" type="submit">저장</button>
        </div>
      </form>

      <form class="settings-card" id="chatbot-ai" autocomplete="off">
        <div class="settings-card__heading">
          <h2>AI 연동 (Claude API)</h2>
          <p class="settings-card__desc" id="ai-status"><?= $ai['configured'] ? '연결됨 · 키 ' . e($ai['maskedKey']) : '미설정 · 규칙 기반 자동 응답을 사용 중입니다.' ?></p>
        </div>

        <div class="form-row">
          <label for="ai-key">API 키</label>
          <input class="text-field" id="ai-key" name="apiKey" type="password" maxlength="255" autocomplete="new-password" spellcheck="false" placeholder="<?= $ai['configured'] ? '변경할 때만 새 키를 입력' : 'sk-ant-...' ?>" />
        </div>

        <div class="form-row">
          <label for="ai-model">모델</label>
          <input class="text-field" id="ai-model" name="model" type="text" maxlength="100" spellcheck="false" placeholder="<?= e(CHATBOT_AI_DEFAULT_MODEL) ?>" value="<?= e($ai['model']) ?>" />
        </div>

        <div class="form-actions">
          <button class="delete-button" id="delete-ai-key" type="button"<?= $ai['configured'] ? '' : ' hidden' ?>>키 삭제</button>
          <button class="save-button" type="submit">저장</button>
        </div>
      </form>
    </main>
    <div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<?php
view('admin/layout-end');
view('admin/foot');
