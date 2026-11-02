<?php
// Outil ponctuel : exporte des miniatures des photos copiées (base64 texte) pour contrôle visuel hors serveur.
declare(strict_types=1);
$pub = dirname(__DIR__, 2) . '/public_html';
$src = $pub . '/uploads/photos';
$out = dirname(__DIR__) . '/storage/export';
if (!is_dir($out)) mkdir($out, 0755, true);
$files = glob($src . '/*.{jpg,png,webp}', GLOB_BRACE) ?: [];
sort($files);
$chunk = 0; $buf = ''; $n = 0;
foreach ($files as $f) {
    $im = @imagecreatefromstring((string)file_get_contents($f));
    if (!$im) continue;
    $w = imagesx($im); $h = imagesy($im); $nw = min(960, $w); $nh = (int)round($h * $nw / $w);
    $t = imagecreatetruecolor($nw, $nh); imagecopyresampled($t, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
    ob_start(); imagejpeg($t, null, 70); $bytes = (string)ob_get_clean();
    $buf .= '### ' . basename($f) . "\n" . chunk_split(base64_encode($bytes), 76, "\n");
    if (++$n % 6 === 0) { file_put_contents($out . '/photos-' . $chunk++ . '.txt', $buf); $buf = ''; }
}
if ($buf !== '') file_put_contents($out . '/photos-' . $chunk++ . '.txt', $buf);
echo "exported $n photos in $chunk files\n";
