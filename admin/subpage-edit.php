<?php
/**
 * 서브페이지 편집 (app/subpages.php).
 *   GET  ?file=portfolio.php (기존 PHP 페이지) | ?id=N (만든 페이지)
 *   POST title, html, css, js (+ 기존: slots[키], 만든: slug, is_visible) + CSRF → 같은 화면 (PRG)
 * 탭: 문구(기존 페이지의 slot()) · HTML · CSS · JS · 설정. 탭 전환은 js/admin-subpage-edit.js
 */
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$file = (string) ($_GET['file'] ?? $_POST['file'] ?? '');
$page = $file !== '' ? subpage_for_file($file) : subpage_find((int) ($_GET['id'] ?? $_POST['id'] ?? 0));
if ($page === null) {
    flash('subpages', '서브페이지를 찾을 수 없습니다.');
    redirect('/admin/subpages.php');
}
$isCustom = $page['file'] === null;
$editUrl = subpage_edit_url($page);
$canEditSource = !$isCustom && subpage_source_enabled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('subpage-edit', '요청이 만료되었습니다. 다시 시도해 주세요.');
        redirect($editUrl);
    }
    try {
        if (($_POST['action'] ?? '') === 'restore' && $canEditSource) {
            subpage_source_restore($page['file'], (string) ($_POST['backup'] ?? ''));
            flash('subpage-edit', '백업으로 되돌렸습니다. (되돌리기 전 파일도 백업됨)');
            redirect($editUrl);
        }
        // 소스를 먼저 저장 (문법 오류면 아무것도 저장하지 않음)
        $sourceSaved = $canEditSource && isset($_POST['source'])
            && subpage_source_save($page['file'], (string) $_POST['source'], (string) ($_POST['source_hash'] ?? ''));
        subpage_save($page, $_POST);
        flash('subpage-edit', $sourceSaved ? '저장했습니다. (소스 파일 변경, 이전 파일은 백업됨)' : '저장했습니다.');
    } catch (RuntimeException $exception) {
        flash('subpage-edit', $exception->getMessage());
    }
    // 만든 페이지는 주소(slug)가 바뀌어도 id 로 다시 연다
    redirect($editUrl);
}

$source = $canEditSource ? subpage_source($page['file']) : '';
$backups = $canEditSource ? subpage_source_backups($page['file']) : [];

$slots = $isCustom ? [] : slot_scan($page['file']);
$saved = $isCustom ? [] : slot_saved($page['file']);
$url = subpage_url($page);
$message = flash('subpage-edit');
$htmlHelp = $isCustom
    ? '페이지 본문 전체입니다. 사이트 헤더·푸터 사이(&lt;main&gt; 안)에 그대로 들어갑니다.'
    : '페이지 본문 아래(푸터 위)에 덧붙는 HTML 입니다. 기존 본문은 소스 탭에서 고칩니다.';

view('admin/head', [
    'pageTitle' => $page['title'] . ' 편집',
    'styles' => ['/css/admin-chatbot.css', '/css/admin-subpages.css', '/css/admin-layout.css'],
    'scripts' => ['/js/admin-subpage-edit.js'],
]);
view('admin/layout-start', ['active' => 'subpages']);
?>
    <main>
      <form class="settings-card subpage-editor" id="subpage-form" method="post" action="<?= e($editUrl) ?>">
        <?= csrf_field() ?>
<?php if ($isCustom): ?>
        <input type="hidden" name="id" value="<?= (int) $page['id'] ?>" />
<?php else: ?>
        <input type="hidden" name="file" value="<?= e($page['file']) ?>" />
<?php endif; ?>
        <div class="settings-card__heading">
          <p class="subpage-back"><a href="/admin/subpages.php">← 서브페이지 목록</a></p>
          <h1 class="subpage-title" title="<?= e($page['title']) ?>"><?= e($page['title']) ?></h1>
          <p class="settings-card__desc">
            <?= $isCustom ? '만든 페이지' : '기존 페이지 (' . e($page['file']) . ')' ?> ·
            <a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($url) ?></a>
<?php if ($isCustom && !$page['is_visible']): ?>
            · <strong class="subpage-hidden">숨김 (관리자만 볼 수 있음)</strong>
<?php endif; ?>
          </p>
        </div>

        <div class="subpage-tabs" role="tablist" aria-label="편집 항목">
<?php if (!$isCustom): ?>
          <button type="button" role="tab" data-tab="texts">문구 <span><?= count($slots) ?></span></button>
<?php endif; ?>
          <button type="button" role="tab" data-tab="html">HTML</button>
          <button type="button" role="tab" data-tab="css">CSS</button>
          <button type="button" role="tab" data-tab="js">JS</button>
          <button type="button" role="tab" data-tab="settings">설정</button>
<?php if ($canEditSource): ?>
          <button type="button" role="tab" data-tab="source">소스</button>
<?php endif; ?>
        </div>

<?php if (!$isCustom): ?>
        <section class="subpage-panel" role="tabpanel" data-panel="texts">
<?php if (!$slots): ?>
          <p class="subpage-empty">이 파일에는 문구 슬롯이 없습니다. PHP 에 <code>&lt;?= slot('키', '기본 문구') ?&gt;</code> 를 넣으면 여기에 나타납니다.</p>
<?php endif; ?>
<?php foreach ($slots as $key => ['default' => $default, 'type' => $type]): ?>
<?php
    [$typeLabel, $max] = SLOT_TYPES[$type];
    $value = $saved[$key] ?? '';
    $fieldId = 'slot-' . $key;
?>
          <div class="form-row">
            <label for="<?= e($fieldId) ?>" title="<?= e($key) ?>"><?= e($key) ?></label>
            <div class="subpage-field">
<?php if ($type === 'text'): ?>
              <input class="text-field" id="<?= e($fieldId) ?>" name="slots[<?= e($key) ?>]" type="text" maxlength="<?= $max ?>" value="<?= e($value) ?>" placeholder="<?= e($default) ?>" />
<?php else: ?>
              <textarea class="subpage-textarea<?= $type === 'html' ? ' subpage-textarea--code' : '' ?>" id="<?= e($fieldId) ?>" name="slots[<?= e($key) ?>]" maxlength="<?= $max ?>" rows="<?= $type === 'html' ? 6 : 3 ?>" placeholder="<?= e($default) ?>"><?= e($value) ?></textarea>
<?php endif; ?>
              <span class="subpage-field__help"><span class="subpage-type"><?= e($typeLabel) ?></span><?= $value === '' ? ' 기본 문구 사용 중' : ' 변경됨' ?> · 기본값: <?= $default === '' ? '(없음)' : e($default) ?></span>
            </div>
          </div>
<?php endforeach; ?>
        </section>
<?php endif; ?>

        <section class="subpage-panel" role="tabpanel" data-panel="html">
          <p class="subpage-panel__help"><?= $htmlHelp ?></p>
          <textarea class="subpage-code" name="html" spellcheck="false" aria-label="HTML" placeholder="<section>...</section>"><?= e($page['html']) ?></textarea>
        </section>
        <section class="subpage-panel" role="tabpanel" data-panel="css">
          <p class="subpage-panel__help">이 페이지에만 적용되는 CSS 입니다. &lt;head&gt; 의 &lt;style&gt; 로 들어갑니다 (공통 CSS 뒤).</p>
          <textarea class="subpage-code" name="css" spellcheck="false" aria-label="CSS" placeholder=".my-section { padding: 40px; }"><?= e($page['css']) ?></textarea>
        </section>
        <section class="subpage-panel" role="tabpanel" data-panel="js">
          <p class="subpage-panel__help">이 페이지에만 실행되는 JS 입니다. &lt;body&gt; 끝(공통 스크립트 뒤)의 &lt;script&gt; 로 들어갑니다.</p>
          <textarea class="subpage-code" name="js" spellcheck="false" aria-label="JS" placeholder="document.querySelector(...)"><?= e($page['js']) ?></textarea>
        </section>
        <section class="subpage-panel" role="tabpanel" data-panel="settings">
          <div class="form-row">
            <label for="subpage-title">페이지 이름</label>
            <div class="subpage-field">
              <input class="text-field" id="subpage-title" name="title" type="text" maxlength="<?= SUBPAGE_TITLE_MAX ?>" required value="<?= e($page['title']) ?>" />
              <span class="subpage-field__help"><?= $isCustom ? '브라우저 탭 제목과 관리 목록에 쓰입니다.' : '관리 목록·메뉴에 보이는 이름입니다. 브라우저 탭 제목은 문구 탭의 page_title.' ?></span>
            </div>
          </div>
<?php if ($isCustom): ?>
          <div class="form-row">
            <label for="subpage-slug">주소</label>
            <div class="subpage-field">
              <input class="text-field" id="subpage-slug" name="slug" type="text" maxlength="<?= SUBPAGE_SLUG_MAX ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" required value="<?= e($page['slug']) ?>" />
              <span class="subpage-field__help">/page/주소 로 열립니다. 영소문자·숫자·하이픈(-). 바꾸면 이전 주소는 열리지 않습니다.</span>
            </div>
          </div>
          <div class="form-row">
            <span class="row-label">노출</span>
            <label class="subpage-check"><input type="checkbox" name="is_visible" value="1"<?= $page['is_visible'] ? ' checked' : '' ?> /> 방문자에게 보이기</label>
          </div>
<?php endif; ?>
        </section>
<?php if ($canEditSource): ?>
        <section class="subpage-panel" role="tabpanel" data-panel="source">
          <p class="subpage-warning">
            서버의 <code><?= e($page['file']) ?></code> 파일을 직접 고칩니다. 저장 즉시 사이트에 반영되고, git 에는 없는 변경이 됩니다 (필요하면 FTP 로 받아 커밋).
            PHP 문법 오류가 있으면 저장되지 않으며, 저장할 때마다 이전 파일이 백업됩니다 (최근 <?= SUBPAGE_BACKUP_KEEP ?>개).
          </p>
          <input type="hidden" name="source_hash" value="<?= e(sha1($source)) ?>" />
          <textarea class="subpage-code subpage-code--source" name="source" spellcheck="false" aria-label="<?= e($page['file']) ?> 소스"><?= e($source) ?></textarea>
<?php if ($backups): ?>
          <div class="subpage-backups">
            <p class="subpage-panel__help">백업 (최신순) — 되돌리면 지금 파일도 백업됩니다.</p>
            <ul>
<?php foreach ($backups as $backup): ?>
              <li>
                <span><?= date('Y-m-d H:i:s', $backup['time']) ?> · <?= number_format($backup['size']) ?> B</span>
                <button class="quiet-button" type="submit" form="restore-form" name="backup" value="<?= e($backup['name']) ?>" data-confirm="<?= date('Y-m-d H:i:s', $backup['time']) ?> 백업으로 되돌릴까요? 저장하지 않은 편집 내용은 사라집니다.">되돌리기</button>
              </li>
<?php endforeach; ?>
            </ul>
          </div>
<?php endif; ?>
        </section>
<?php endif; ?>

        <div class="form-actions">
          <a class="quiet-button subpage-preview" href="<?= e($url) ?>" target="_blank" rel="noopener">페이지 보기</a>
          <button class="save-button" type="submit">저장</button>
        </div>
      </form>
<?php if ($canEditSource): ?>
      <form id="restore-form" method="post" action="<?= e($editUrl) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="file" value="<?= e($page['file']) ?>" />
        <input type="hidden" name="action" value="restore" />
      </form>
<?php endif; ?>
    </main>
<?php if ($message !== null): ?>
    <div class="toast" id="subpage-toast" role="status" aria-live="polite"><?= e($message) ?></div>
<?php endif; ?>
<?php
view('admin/layout-end');
view('admin/foot');
