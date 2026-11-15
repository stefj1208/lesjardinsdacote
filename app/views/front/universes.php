<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow eyebrow--start">Ce que l'on achète ensemble</p>
    <h1>Cette saison : les graines</h1>
    <p class="lede">Pour bien démarrer, l'association se concentre sur un seul achat groupé : des graines bio de variétés anciennes et reproductibles. Plants, arbres fruitiers et aromatiques suivront aux prochaines saisons.</p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap ugrid">
    <?php $k = 0; foreach ($univers as $u) partial('partials/ucard', ['u' => $u, 'i' => $k++]); ?>
  </div>
</section>
