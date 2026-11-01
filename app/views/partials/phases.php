<div class="phases">
  <div class="phase">
    <span class="phase-num" aria-hidden="true">1</span>
    <?php partial('partials/status', ['s' => 'recruiting']) ?>
    <h3>Deux bénévoles se lancent</h3>
    <p>Un groupe démarre dans votre commune. Dès que deux voisins acceptent d'aider à préparer et distribuer les packs, il peut ouvrir.</p>
    <div class="phase-visual slots"><span class="slot slot--big is-person"></span><span class="slot slot--big"></span></div>
  </div>
  <div class="phase">
    <span class="phase-num" aria-hidden="true">2</span>
    <?php partial('partials/status', ['s' => 'preopening']) ?>
    <h3>On vote pour un pack</h3>
    <p>Trois packs de saison sont proposés, avec leurs variétés, leurs quantités et leur prix. Chacun vote pour celui qu'il veut.</p>
    <div class="phase-visual" style="display:grid;gap:6px">
      <div class="meter"><span style="--p:62"></span></div><div class="meter"><span style="--p:25"></span></div><div class="meter"><span style="--p:13"></span></div>
    </div>
  </div>
  <div class="phase">
    <span class="phase-num" aria-hidden="true">3</span>
    <?php partial('partials/status', ['s' => 'open']) ?>
    <h3>Le pack gagnant est mis en vente</h3>
    <p>Il arrive dans le panier de chaque votant. Les achats sont regroupés par lots de 10 : un lot complet est commandé et débité, puis le suivant s'ouvre.</p>
    <div class="phase-visual slots"><?php for ($i = 0; $i < 10; $i++): ?><span class="slot<?= $i < 7 ? ' is-on' : '' ?>"></span><?php endfor; ?></div>
  </div>
</div>
