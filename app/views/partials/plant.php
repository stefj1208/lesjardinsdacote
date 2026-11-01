<?php
/**
 * "La plante qui pousse" — SVG réactif à 5 états (§7).
 * @var int $state 1..5   @var float $growth 0..1   @var float|null $toT1 0..1   @var bool $can (arrosoir)
 */
$state = (int)($state ?? 1);
$growth = (float)($growth ?? 0);
$toT1 = (float)($toT1 ?? 0);
$can = $can ?? false;
$size = $size ?? 'md';
$uid = 'pl' . substr(md5((string)mt_rand()), 0, 6);
?>
<div class="plant plant--<?= e($size) ?>" data-plant data-state="<?= $state ?>" style="--g:<?= $growth ?>;--t1:<?= max(0.35, min(1, $toT1)) ?>">
  <svg viewBox="0 0 160 170" aria-hidden="true" focusable="false">
    <defs>
      <linearGradient id="<?= $uid ?>w" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#C9A06C"/><stop offset="1" stop-color="#A97B4C"/>
      </linearGradient>
      <radialGradient id="<?= $uid ?>f" cx=".35" cy=".35" r=".7">
        <stop offset="0" stop-color="#FF8A6B"/><stop offset=".55" stop-color="#E5482F"/><stop offset="1" stop-color="#B7321E"/>
      </radialGradient>
    </defs>
    <g class="p-sway">
      <!-- Graine semée + goutte -->
      <g class="p-seed">
        <ellipse cx="80" cy="121" rx="6" ry="4.2" fill="#8B5A2B"/>
        <path d="M76 120.5 q4 -3 8 0" stroke="#B98552" stroke-width="1.2" fill="none" stroke-linecap="round"/>
        <path class="p-drop" d="M80 78 c-4 6 -6 9 -6 12 a6 6 0 0 0 12 0 c0 -3 -2 -6 -6 -12z" fill="#7CC4E8"/>
      </g>
      <!-- Jeune pousse (2 cotylédons) -->
      <g class="p-sprout">
        <path d="M80 124 C80 112 79 104 80 96" stroke="#3F8F4E" stroke-width="3.2" fill="none" stroke-linecap="round"/>
        <g transform="translate(80 98) rotate(-28)"><path class="lf" d="M0 0 C6 -9 20 -10 27 -1 C20 7 7 7 0 0Z" fill="#5DBB63"/></g>
        <g transform="translate(80 98) scale(-1 1) rotate(-28)"><path class="lf" d="M0 0 C6 -9 20 -10 27 -1 C20 7 7 7 0 0Z" fill="#4CAF50"/></g>
      </g>
      <!-- Plante feuillue -->
      <g class="p-stem">
        <path d="M80 124 C79 104 82 86 79 66 C77 54 80 44 80 34" stroke="#2F7D4F" stroke-width="4" fill="none" stroke-linecap="round"/>
        <path class="p-branch" d="M79.5 82 C70 74 62 68 56 60" stroke="#2F7D4F" stroke-width="3" fill="none" stroke-linecap="round"/>
        <path class="p-branch" d="M80 70 C90 64 98 58 104 52" stroke="#2F7D4F" stroke-width="3" fill="none" stroke-linecap="round"/>
      </g>
      <g class="p-leaves p-leaves-1">
        <g transform="translate(80 106) rotate(-18)"><path class="lf" d="M0 0 C8 -12 28 -13 38 -1 C28 9 9 9 0 0Z" fill="#4CAF50"/></g>
        <g transform="translate(80 100) scale(-1 1) rotate(-22)"><path class="lf" d="M0 0 C8 -12 28 -13 38 -1 C28 9 9 9 0 0Z" fill="#3E9B48"/></g>
      </g>
      <g class="p-leaves p-leaves-2">
        <g transform="translate(80 86) rotate(-30)"><path class="lf" d="M0 0 C7 -10 24 -11 32 -1 C24 8 8 8 0 0Z" fill="#5DBB63"/></g>
        <g transform="translate(79 78) scale(-1 1) rotate(-12)"><path class="lf" d="M0 0 C7 -10 24 -11 32 -1 C24 8 8 8 0 0Z" fill="#4CAF50"/></g>
        <g transform="translate(80 50) rotate(-55)"><path class="lf" d="M0 0 C6 -8 19 -9 25 -1 C19 6 6 6 0 0Z" fill="#6CC970"/></g>
        <g transform="translate(80 50) scale(-1 1) rotate(-55)"><path class="lf" d="M0 0 C6 -8 19 -9 25 -1 C19 6 6 6 0 0Z" fill="#5DBB63"/></g>
      </g>
      <g class="p-leaves p-leaves-3">
        <g transform="translate(66 72) scale(-1 1) rotate(-40)"><path class="lf" d="M0 0 C6 -8 19 -9 25 -1 C19 6 6 6 0 0Z" fill="#4CAF50"/></g>
        <g transform="translate(94 62) rotate(-40)"><path class="lf" d="M0 0 C6 -8 19 -9 25 -1 C19 6 6 6 0 0Z" fill="#3E9B48"/></g>
      </g>
      <!-- Fleurs -->
      <g class="p-flowers">
        <?php foreach ([[80, 31, 1], [55, 58, .85], [105, 50, .85]] as $i => [$fx, $fy, $fs]): ?>
        <g transform="translate(<?= $fx ?> <?= $fy ?>) scale(<?= $fs ?>)"><g class="fl" style="--d:<?= $i * 120 ?>ms">
          <?php for ($k = 0; $k < 5; $k++): ?><ellipse cx="0" cy="-6.5" rx="4" ry="6" fill="#FFFDF5" stroke="#F1E7C8" stroke-width=".6" transform="rotate(<?= $k * 72 ?>)"/><?php endfor; ?>
          <circle r="3.6" fill="#FDE047"/><circle r="1.6" fill="#E9B308"/>
        </g></g>
        <?php endforeach; ?>
      </g>
      <!-- Récolte abondante -->
      <g class="p-fruits">
        <?php foreach ([[64, 80, 7.5], [97, 70, 8.5], [72, 96, 6.5], [90, 92, 7]] as $i => [$fx, $fy, $fr]): ?>
        <g transform="translate(<?= $fx ?> <?= $fy ?>)"><g class="fr" style="--d:<?= $i * 110 ?>ms">
          <circle r="<?= $fr ?>" fill="url(#<?= $uid ?>f)"/>
          <ellipse cx="<?= -$fr * .35 ?>" cy="<?= -$fr * .35 ?>" rx="<?= $fr * .28 ?>" ry="<?= $fr * .18 ?>" fill="#fff" opacity=".55"/>
          <path d="M0 <?= -$fr ?> l-3 -2 M0 <?= -$fr ?> l3 -2 M0 <?= -$fr ?> l0 -3" stroke="#2F7D4F" stroke-width="1.6" stroke-linecap="round"/>
        </g></g>
        <?php endforeach; ?>
      </g>
      <g class="p-sparkles" fill="#FDE047">
        <path d="M30 40 l2 5 5 2 -5 2 -2 5 -2 -5 -5 -2 5 -2z"/>
        <path d="M128 30 l1.6 4 4 1.6 -4 1.6 -1.6 4 -1.6 -4 -4 -1.6 4 -1.6z"/>
        <path d="M134 84 l1.4 3.5 3.5 1.4 -3.5 1.4 -1.4 3.5 -1.4 -3.5 -3.5 -1.4 3.5 -1.4z"/>
      </g>
    </g>
    <!-- Bac en bois + terreau -->
    <path d="M32 120 Q80 112 128 120 L128 128 L32 128Z" fill="#4A2F1B"/>
    <g fill="#6B4528"><circle cx="48" cy="121" r="1.4"/><circle cx="62" cy="119" r="1"/><circle cx="101" cy="119.5" r="1.3"/><circle cx="116" cy="121" r="1"/></g>
    <rect x="26" y="126" width="108" height="36" rx="3" fill="url(#<?= $uid ?>w)"/>
    <rect x="26" y="126" width="108" height="5" rx="2" fill="#D9B98C"/>
    <path d="M26 144 H134" stroke="#8B5A2B" stroke-width="1.6" opacity=".55"/>
    <path d="M40 134 q14 -2 28 0 M88 150 q16 -2 34 0 M34 154 q10 -1 18 0" stroke="#8B5A2B" stroke-width=".9" fill="none" opacity=".45"/>
    <g fill="#5B3A1E"><circle cx="32" cy="135" r="1.3"/><circle cx="128" cy="135" r="1.3"/><circle cx="32" cy="153" r="1.3"/><circle cx="128" cy="153" r="1.3"/></g>
  </svg>
  <?php if ($can): ?>
  <svg class="can" viewBox="0 0 64 48" aria-hidden="true">
    <g class="can-body">
      <path d="M14 18 h24 l3 22 h-30z" fill="#1F4E3D"/>
      <path d="M16 18 q10 -14 22 0" stroke="#1F4E3D" stroke-width="3" fill="none"/>
      <path d="M40 26 l18 -10 2 3 -17 12z" fill="#1F4E3D"/>
      <circle cx="59" cy="17" r="3.4" fill="#22C55E"/>
    </g>
    <g class="can-drops" fill="#7CC4E8"><circle cx="58" cy="26" r="1.6"/><circle cx="61" cy="30" r="1.4"/><circle cx="56" cy="32" r="1.2"/></g>
  </svg>
  <?php endif; ?>
</div>
