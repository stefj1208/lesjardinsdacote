<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow eyebrow--start">Ce que l'on achète ensemble</p>
    <h1>Cinq univers, une saison chacun</h1>
    <p class="lede">À chaque saison, votre groupe choisit ce qu'il achète en gros : plants au printemps, arbres en hiver, graines dès janvier. Chaque univers affiche les prix publics et le prix du groupe.</p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap ugrid">
    <?php $k = 0; foreach ($univers as $u) partial('partials/ucard', ['u' => $u, 'i' => $k++]); ?>
  </div>
</section>
