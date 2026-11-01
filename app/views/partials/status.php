<?php $st = Cat::GROUP_STATUS[$s] ?? null; if ($st): ?><span class="status status--<?= e($st['tone']) ?>"><?= e(!empty($long) ? $st['label'] : $st['short']) ?></span><?php endif; ?>
