<?php
/**
 * 관리자 포트폴리오 등록/수정. ?id= 가 있으면 수정.
 * POST (multipart, CSRF) → 저장 후 목록으로. 이미지는 uploads/posts/ 에 저장하고 교체·삭제 시 이전 업로드 파일을 지운다.
 */
require __DIR__ . '/../app/bootstrap.php';
require_admin();

const PORTFOLIO_IMAGE_FIELDS = [
    'thumbnail' => '썸네일',
    'hero_image' => '대표 이미지',
    'detail_image' => '상세 이미지',
];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$existing = null;
if ($id > 0) {
    $existing = portfolio_find($id);
    if ($existing === null) {
        flash('portfolio', '포트폴리오를 찾을 수 없습니다.');
        redirect('/admin/portfolios.php');
    }
}

$form = [
    'title' => $existing['title'] ?? '',
    'category' => $existing['category'] ?? 'mvp',
    'client' => $existing['client'] ?? '',
    'type' => $existing['type'] ?? '',
    'project_date' => $existing['project_date'] ?? '',
    'summary' => $existing['summary'] ?? '',
    'site_url' => $existing['site_url'] ?? '',
    'is_visible' => (int) ($existing['is_visible'] ?? 1),
    'thumbnail' => $existing['thumbnail'] ?? null,
    'hero_image' => $existing['hero_image'] ?? null,
    'detail_image' => $existing['detail_image'] ?? null,
];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'category' => (string) ($_POST['category'] ?? ''),
        'client' => trim((string) ($_POST['client'] ?? '')),
        'type' => trim((string) ($_POST['type'] ?? '')),
        'project_date' => trim((string) ($_POST['project_date'] ?? '')),
        'summary' => trim(str_replace("\r\n", "\n", (string) ($_POST['summary'] ?? ''))),
        'site_url' => trim((string) ($_POST['site_url'] ?? '')),
        'is_visible' => ($_POST['is_visible'] ?? '1') === '0' ? 0 : 1,
    ] + $form;

    $uploads = [];
    foreach (array_keys(PORTFOLIO_IMAGE_FIELDS) as $field) {
        $file = $_FILES[$field] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploads[$field] = $file;
        }
    }
    $removeImages = array_intersect(['hero_image', 'detail_image'], (array) ($_POST['remove'] ?? []));
    $scheme = strtolower((string) parse_url($form['site_url'], PHP_URL_SCHEME));

    // post_max_size 를 넘으면 $_POST 가 비어서 CSRF 토큰도 사라진다
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $error = '전송 용량이 너무 큽니다. 이미지 크기를 줄여 주세요.';
    } elseif (!csrf_valid()) {
        $error = '요청이 만료되었습니다. 다시 시도해 주세요.';
    } elseif ($form['title'] === '' || mb_strlen($form['title']) > 120) {
        $error = '제목을 120자 이내로 입력해 주세요.';
    } elseif (!isset(PORTFOLIO_CATEGORIES[$form['category']])) {
        $error = '분류를 선택해 주세요.';
    } elseif (mb_strlen($form['client']) > 100 || mb_strlen($form['type']) > 200 || mb_strlen($form['project_date']) > 50) {
        $error = 'CLIENT 는 100자, TYPE 은 200자, 기간은 50자 이내로 입력해 주세요.';
    } elseif ($form['summary'] === '') {
        $error = '소개 내용을 입력해 주세요.';
    } elseif ($form['site_url'] !== '' && (mb_strlen($form['site_url']) > 255
        || filter_var($form['site_url'], FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true))) {
        $error = '사이트 주소는 http:// 또는 https:// 로 시작하는 주소만 입력할 수 있습니다.';
    } elseif (empty($form['thumbnail']) && !isset($uploads['thumbnail'])) {
        $error = '썸네일 이미지를 올려 주세요.';
    }

    if ($error === null) {
        // 파일을 먼저 저장하고, 실패하면 이번에 올린 파일만 지운다
        $stored = [];
        $old = [];
        try {
            foreach ($uploads as $field => $file) {
                $stored[] = $name = post_store_upload($file, true);
                $old[] = $form[$field];
                $form[$field] = post_upload_url($name);
            }
        } catch (RuntimeException $exception) {
            array_map('post_delete_upload', $stored);
            $error = PORTFOLIO_IMAGE_FIELDS[$field] . ': ' . $exception->getMessage();
        }
    }

    if ($error === null) {
        foreach ($removeImages as $field) {
            if (!isset($uploads[$field])) {
                $old[] = $form[$field];
                $form[$field] = null;
            }
        }
        try {
            portfolio_save($existing ? $id : null, $form);
        } catch (PDOException $exception) {
            array_map('post_delete_upload', $stored);
            throw $exception;
        }
        array_map('post_delete_upload', $old);
        flash('portfolio', $existing ? '수정했습니다.' : '등록했습니다.');
        redirect('/admin/portfolios.php');
    }
    // 실패 시 화면에는 기존 이미지를 보여준다 (새로 고른 파일은 다시 선택해야 함)
    foreach (array_keys(PORTFOLIO_IMAGE_FIELDS) as $field) {
        $form[$field] = $existing[$field] ?? null;
    }
}

$pageTitle = $existing ? '포트폴리오 수정' : '포트폴리오 등록';

view('admin/head', [
    'pageTitle' => $pageTitle,
    'styles' => ['/css/admin-board.css', '/css/admin-post-edit.css', '/css/admin-portfolios.css', '/css/admin-layout.css'],
    'scripts' => ['/js/custom-select.js', '/js/admin-portfolios.js'],
]);
view('admin/layout-start', ['active' => 'board']);
?>
    <main class="editor-shell">
      <form class="editor-card" id="portfolio-form" method="post" enctype="multipart/form-data"
            action="/admin/portfolio-edit.php<?= $existing ? '?id=' . $id : '' ?>">
        <?= csrf_field() ?>
        <h1><?= e($pageTitle) ?></h1>
<?php if ($error !== null): ?>
        <p class="editor-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

        <div class="editor-row">
          <label class="editor-label" for="pf-title">제목 <span class="editor-required">*</span></label>
          <input class="editor-input" id="pf-title" name="title" type="text" maxlength="120" value="<?= e($form['title']) ?>" placeholder="목록·상세에 보일 프로젝트명" required />
        </div>

        <fieldset class="editor-row">
          <legend class="editor-label">노출 여부</legend>
          <div class="editor-choices">
            <label class="editor-choice"><input type="radio" name="is_visible" value="1"<?= $form['is_visible'] ? ' checked' : '' ?> /> 노출</label>
            <label class="editor-choice"><input type="radio" name="is_visible" value="0"<?= $form['is_visible'] ? '' : ' checked' ?> /> 숨김</label>
          </div>
        </fieldset>

        <div class="editor-row">
          <span class="editor-label" id="pf-category-label">분류 <span class="editor-required">*</span></span>
          <div class="select-box" data-custom-select>
            <select id="pf-category" name="category" aria-labelledby="pf-category-label">
<?php foreach (PORTFOLIO_CATEGORIES as $key => $label): ?>
              <option value="<?= e($key) ?>"<?= $key === $form['category'] ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
            <img src="/aseets/icons/admin/board-category-arrow.svg" alt="" width="20" height="20" />
          </div>
        </div>

        <div class="editor-row">
          <label class="editor-label" for="pf-client">CLIENT</label>
          <input class="editor-input" id="pf-client" name="client" type="text" maxlength="100" value="<?= e($form['client']) ?>" placeholder="예: Kumu" />
        </div>

        <div class="editor-row">
          <label class="editor-label" for="pf-type">TYPE</label>
          <input class="editor-input" id="pf-type" name="type" type="text" maxlength="200" value="<?= e($form['type']) ?>" placeholder="예: Branding, UI/UX Design, Responsive Web" />
        </div>

        <div class="editor-row">
          <label class="editor-label" for="pf-date">기간 (DATE)</label>
          <input class="editor-input" id="pf-date" name="project_date" type="text" maxlength="50" value="<?= e($form['project_date']) ?>" placeholder="예: 2025.10 ~ 2025.12" />
        </div>

        <div class="editor-row">
          <label class="editor-label" for="pf-url">사이트 주소</label>
          <input class="editor-input" id="pf-url" name="site_url" type="url" maxlength="255" value="<?= e($form['site_url']) ?>" placeholder="https:// (비우면 상세에서 '사이트 바로가기' 버튼 숨김)" />
        </div>

        <div class="editor-row editor-row--stack">
          <label class="editor-label" for="pf-summary">소개 (Overview) <span class="editor-required">*</span></label>
          <textarea class="editor-input editor-textarea editor-textarea--short" id="pf-summary" name="summary" placeholder="상세 페이지 Overview 문구 (줄바꿈 유지)" required><?= e($form['summary']) ?></textarea>
        </div>

<?php foreach (PORTFOLIO_IMAGE_FIELDS as $field => $label): ?>
        <div class="editor-row">
          <span class="editor-label"><?= e($label) ?><?= $field === 'thumbnail' ? ' <span class="editor-required">*</span>' : '' ?></span>
          <div class="editor-files">
            <label class="file-button">
              <img src="/aseets/icons/admin/all-inbox.svg" alt="" width="24" height="24" />
              이미지 선택
              <input name="<?= $field ?>" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="#pf-preview-<?= $field ?>" />
            </label>
            <img class="image-preview" id="pf-preview-<?= $field ?>" src="<?= e((string) $form[$field]) ?>" alt="<?= e($label) ?> 미리보기"<?= $form[$field] ? '' : ' hidden' ?> />
<?php if ($field !== 'thumbnail' && $form[$field]): ?>
            <label class="editor-inline-check"><input type="checkbox" name="remove[]" value="<?= $field ?>" /> 이미지 삭제</label>
<?php endif; ?>
            <span class="editor-hint"><?= match ($field) {
                'thumbnail' => '목록 카드 이미지. 정사각형 560×560 권장',
                'hero_image' => '상세 상단 전체 화면 이미지. 1920×1080 권장, 없으면 썸네일 사용',
                'detail_image' => '상세 하단 페이지 이미지 (세로로 긴 캡처). 없으면 영역 숨김',
            } ?> · JPG·PNG·WEBP·GIF 10MB 이하</span>
          </div>
        </div>
<?php endforeach; ?>

        <div class="editor-actions">
<?php if ($existing): ?>
          <a class="pill-button pill-button--muted" href="/portfolio-detail.php?id=<?= $id ?>" target="_blank" rel="noopener">사이트에서 보기</a>
          <button class="pill-button pill-button--danger" type="submit" form="delete-form" data-confirm="이 포트폴리오를 삭제할까요? 업로드한 이미지도 함께 삭제됩니다.">삭제</button>
<?php endif; ?>
          <a class="pill-button pill-button--muted" href="/admin/portfolios.php">목록</a>
          <button class="pill-button pill-button--primary" type="submit">저장</button>
        </div>
      </form>
<?php if ($existing): ?>
      <form id="delete-form" method="post" action="/admin/portfolios.php" hidden>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="id" value="<?= $id ?>" />
      </form>
<?php endif; ?>
    </main>
<?php
view('admin/layout-end');
view('admin/foot');
