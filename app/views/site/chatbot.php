<?php
/**
 * 사용자 챗봇 위젯 (site/footer 에서 include). AI 키가 있으면 /chatbot-chat.php(Claude) 로 답하고,
 * 키가 없거나 실패하면 js/chatbot-widget.js 의 규칙 기반 응답을 쓴다.
 * @var bool                                           $isHome
 * @var array{email: string, phone: string, address: string} $contact footer 와 같은 연락처
 */
$chatbot = chatbot_settings();
$chatbotHome = $isHome ? '' : '/';
?>
    <div
      class="chatbot"
      id="chatbot"
      style="--chatbot-color: <?= e($chatbot['theme_color']) ?>; --chatbot-on-color: <?= e(chatbot_contrast_color($chatbot['theme_color'])) ?>"
      data-open-url="/chatbot-open.php"
<?php if (chatbot_ai()['api_key'] !== ''): ?>
      data-ai-url="/chatbot-chat.php"
<?php endif; ?>
      data-csrf="<?= e(csrf_token()) ?>"
      data-tone="<?= e($chatbot['tone']) ?>"
      data-contact-url="<?= e($chatbotHome) ?>#contact"
      data-services-url="<?= e($chatbotHome) ?>#gyu-mvp"
      data-portfolio-url="/portfolio.php"
      data-email="<?= e($contact['email']) ?>"
      data-phone="<?= e($contact['phone']) ?>"
      data-address="<?= e($contact['address']) ?>"
    >
      <section class="chatbot__panel" id="chatbot-panel" role="dialog" aria-labelledby="chatbot-title" hidden>
        <header class="chatbot__head">
          <span class="chatbot__avatar" aria-hidden="true">
<?php if ($chatbot['icon']): ?>
            <img src="<?= e($chatbot['icon']) ?>" alt="" />
<?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.4A8 8 0 1 1 21 12Z" /><path d="M8.5 12h.01M12 12h.01M15.5 12h.01" /></svg>
<?php endif; ?>
          </span>
          <div class="chatbot__title">
            <strong id="chatbot-title" title="<?= e($chatbot['ai_name']) ?>"><?= e($chatbot['ai_name']) ?></strong>
<?php if ($chatbot['guide_text'] !== ''): ?>
            <span title="<?= e($chatbot['guide_text']) ?>"><?= e($chatbot['guide_text']) ?></span>
<?php endif; ?>
          </div>
          <button class="chatbot__close" type="button" aria-label="챗봇 닫기">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
          </button>
        </header>

        <div class="chatbot__log" id="chatbot-log" role="log" aria-live="polite" data-lenis-prevent>
          <p class="chatbot__msg chatbot__msg--bot"><?= e($chatbot['welcome_message']) ?></p>
        </div>

        <div class="chatbot__quick" aria-label="빠른 질문">
          <button type="button">견적 문의는 어떻게 하나요?</button>
          <button type="button">포트폴리오를 보고 싶어요</button>
          <button type="button">어떤 서비스를 하나요?</button>
          <button type="button">연락처가 궁금해요</button>
        </div>

        <form class="chatbot__form" autocomplete="off">
          <label class="blind" for="chatbot-input">질문 입력</label>
          <input id="chatbot-input" type="text" maxlength="200" placeholder="궁금한 점을 입력해 주세요" />
          <button type="submit" aria-label="보내기">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
          </button>
        </form>
      </section>

      <button class="chatbot__launcher" type="button" aria-controls="chatbot-panel" aria-expanded="false" aria-label="<?= e($chatbot['ai_name']) ?> 챗봇 열기">
<?php if ($chatbot['icon']): ?>
        <img src="<?= e($chatbot['icon']) ?>" alt="" />
<?php else: ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.4A8 8 0 1 1 21 12Z" /><path d="M8.5 12h.01M12 12h.01M15.5 12h.01" /></svg>
<?php endif; ?>
      </button>
    </div>
