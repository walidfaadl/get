<?php /** @var string $heading @var string $text */ ?>
<div class="card center-card">
  <h2><?= e($heading ?? 'تنبيه') ?></h2>
  <p class="muted"><?= e($text ?? '') ?></p>
  <a class="btn-primary" href="<?= e(url('tasks')) ?>">العودة للمهام</a>
</div>
