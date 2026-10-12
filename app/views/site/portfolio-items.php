<?php
/**
 * 포트폴리오 목록 항목. 3개씩 한 줄, 줄마다 top-box / bottom-box 를 번갈아 쓰고 가운데 항목은 margin-item.
 * @var array<int, array> $items    id, title, thumbnail
 * @var int               $rowStart 이 묶음의 첫 줄 번호 (0부터, 더보기로 이어 붙일 때 줄 무늬 유지용)
 */
foreach (array_chunk($items, 3) as $rowIndex => $row):
    $boxClass = ($rowStart + $rowIndex) % 2 === 0 ? 'top-box' : 'bottom-box';
?>
            <div class="<?= $boxClass ?>">
              <ul class="body-list">
<?php foreach ($row as $position => $item): ?>
                <li class="body-list-item<?= $position === 1 ? ' margin-item' : '' ?>">
                  <a href="/portfolio-detail.php?id=<?= (int) $item['id'] ?>"><img src="<?= e($item['thumbnail']) ?>" alt="<?= e($item['title']) ?>" /> <span><?= e($item['title']) ?></span></a>
                </li>
<?php endforeach; ?>
              </ul>
            </div>
<?php endforeach; ?>
