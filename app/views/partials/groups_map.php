<?php
/** Carte des groupes et points de retrait. @var array $points  @var string $height */
if (!$points) return;
$id = 'gmap-' . substr(md5(json_encode($points)), 0, 6);
?>
<div class="gmap-wrap" data-reveal>
  <div class="gmap" id="<?= $id ?>" style="height:<?= e($height ?? '420px') ?>" role="region" aria-label="Carte des groupes et des points de retrait"
       data-points="<?= e(json_encode($points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"></div>
  <ul class="gmap-legend" aria-hidden="true">
    <li><i class="st-open"></i>Ventes ouvertes</li><li><i class="st-voting"></i>Vote en cours</li><li><i class="st-active"></i>Bénévoles recherchés</li><li><i class="st-proposed"></i>En création</li>
  </ul>
</div>
