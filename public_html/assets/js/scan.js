/* Scan du Pass Retrait avec la caméra du téléphone (html5-qrcode, hébergé localement). */
(function () {
  'use strict';
  var btn = document.getElementById('scan-start');
  var box = document.getElementById('qr-reader');
  var status = document.getElementById('scan-status');
  if (!btn || !box) return;
  var scanner = null, done = false;
  function go(text) {
    var m = /\/r\/([a-f0-9]{32})/.exec(text) || /^([a-f0-9]{32})$/.exec(text);
    if (m) { window.location.href = '/benevoles/distribution/' + m[1]; return true; }
    if (/^[A-Z0-9]{6}$/.test(text)) { window.location.href = '/benevoles/distribution?q=' + encodeURIComponent(text); return true; }
    status.textContent = 'QR code non reconnu : ' + text.slice(0, 60);
    return false;
  }
  btn.addEventListener('click', function () {
    if (scanner) { scanner.stop().catch(function () {}); scanner = null; box.hidden = true; btn.textContent = 'Scanner un Pass Retrait'; return; }
    if (!window.Html5Qrcode) { status.textContent = 'Le module de scan n\'a pas pu se charger. Utilisez la recherche par code.'; return; }
    box.hidden = false;
    btn.textContent = 'Arrêter la caméra';
    scanner = new Html5Qrcode('qr-reader');
    scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } }, function (text) {
      if (done) return;
      if (go(text)) { done = true; if (navigator.vibrate) navigator.vibrate(80); scanner.stop().catch(function () {}); }
    }, function () {}).catch(function (err) {
      status.textContent = 'Caméra indisponible (' + (err && err.name ? err.name : err) + '). Utilisez la recherche par code.';
      box.hidden = true; scanner = null; btn.textContent = 'Scanner un Pass Retrait';
    });
  });
})();
