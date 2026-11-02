<div class="phases">
  <div class="phase" data-reveal style="--i:0">
    <span class="phase-num" aria-hidden="true">1</span>
    <?php partial('partials/status', ['s' => 'recruiting']) ?>
    <h3>Deux bénévoles se lancent</h3>
    <p>Un groupe démarre dans votre commune. Dès que deux voisins acceptent d'aider à préparer et distribuer les packs, il peut ouvrir.</p>
    <div class="phase-visual slots"><span class="slot slot--big is-person" style="--i:1"></span><span class="slot slot--big is-person" style="--i:3"></span></div>
  </div>
  <div class="phase" data-reveal style="--i:1">
    <span class="phase-num" aria-hidden="true">2</span>
    <?php partial('partials/status', ['s' => 'preopening']) ?>
    <h3>On choisit les variétés</h3>
    <p>Une quarantaine de variétés rares sont proposées. Chacun coche ses préférées : les plus demandées entrent dans le pack, jusqu'à atteindre son prix.</p>
    <div class="phase-visual" style="display:grid;gap:6px">
      <div class="meter"><span style="--p:62"></span></div><div class="meter"><span style="--p:25"></span></div><div class="meter"><span style="--p:13"></span></div>
    </div>
  </div>
  <div class="phase" data-reveal style="--i:2">
    <span class="phase-num" aria-hidden="true">3</span>
    <?php partial('partials/status', ['s' => 'open']) ?>
    <h3>Le pack composé est mis en vente</h3>
    <p>Il arrive dans le panier de chaque votant. Les achats sont regroupés par lots de 10 : un lot complet est commandé et débité, puis le suivant s'ouvre.</p>
    <div class="phase-visual slots"><?php for ($k = 0; $k < 10; $k++): ?><span class="slot<?= $k < 7 ? ' is-on' : '' ?>" style="--i:<?= $k ?>"></span><?php endfor; ?></div>
  </div>
</div>
