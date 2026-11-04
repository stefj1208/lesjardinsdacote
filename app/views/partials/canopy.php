<?php
/** Rideau de feuilles (transition entre les pages). Les feuilles sont dessinées en SVG, sans image. */
$heart = '<path fill="currentColor" d="M100 196C46 176 6 128 10 76 13 34 50 6 92 22c4 2 6 5 8 9 2-4 4-7 8-9 42-16 79 12 82 54 4 52-36 100-90 120z"/><g fill="none" stroke="rgba(255,255,255,.16)" stroke-width="3" stroke-linecap="round"><path d="M100 32v158"/><path d="M100 70 54 46M100 96 34 82M100 124 40 126M100 150 62 166M100 70l46-24M100 96l66-14M100 124l60 2M100 150l38 16"/></g><g fill="#0F172B" opacity=".18"><path d="M58 92c-8 4-14 10-18 18l20-4zM150 98c8 3 14 9 18 16l-20-3z"/></g>';
$lance = '<path fill="currentColor" d="M100 4C150 40 176 96 160 146c-10 30-34 48-60 50-26-2-50-20-60-50C24 96 50 40 100 4z"/><g fill="none" stroke="rgba(255,255,255,.18)" stroke-width="3" stroke-linecap="round"><path d="M100 18v176"/><path d="M100 60 70 44M100 90 60 72M100 120 54 104M100 150 62 140M100 60l30-16M100 90l40-18M100 120l46-16M100 150l38-10"/></g>';
$fig = '<path fill="currentColor" d="M100 196c-8-20-30-26-46-40-18-16-8-36-26-52C10 88 20 60 44 64c4-22 26-34 42-18 4-22 24-32 28-8 16-16 40-4 40 18 24-4 36 22 22 42-12 16 2 36-16 52-16 14-38 18-46 40z" transform="rotate(180 100 100)"/><g fill="none" stroke="rgba(255,255,255,.16)" stroke-width="3" stroke-linecap="round"><path d="M100 6v150M100 120 50 80M100 120l52-40M100 80 70 44M100 80l32-36"/></g>';
$shapes = [$heart, $lance, $fig, $heart, $lance, $fig];
?>
<div class="canopy" aria-hidden="true"><?php foreach ($shapes as $s): ?><div class="leaf"><svg viewBox="0 0 200 200"><?= $s ?></svg></div><?php endforeach; ?></div>
