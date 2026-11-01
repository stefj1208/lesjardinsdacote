<?php
declare(strict_types=1);

/**
 * Générateur de QR Code autonome (mode octet, correction M, versions 1 à 40).
 * Aucune dépendance : rendu SVG (pages, étiquettes) ou PNG via GD (emails).
 * Algorithme conforme ISO/IEC 18004 (Reed-Solomon GF(256), masques 0-7).
 */
final class QR
{
    private const ECC_M = [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28];
    private const BLOCKS_M = [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49];

    private int $size;
    private array $mod = [];
    private array $fn = [];

    public static function matrix(string $text): array
    {
        $q = new self();
        return $q->encode($text);
    }

    public static function svg(string $text, int $px = 160, string $dark = '#232B2D', string $light = '#FFFFFF'): string
    {
        $m = self::matrix($text);
        $n = count($m);
        $quiet = 4;
        $total = $n + 2 * $quiet;
        $d = '';
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($m[$y][$x]) $d .= 'M' . ($x + $quiet) . ',' . ($y + $quiet) . 'h1v1h-1z';
            }
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $total . ' ' . $total . '" width="' . $px . '" height="' . $px . '" shape-rendering="crispEdges" role="img" aria-label="QR code">'
            . '<rect width="100%" height="100%" fill="' . $light . '"/><path d="' . $d . '" fill="' . $dark . '"/></svg>';
    }

    public static function png(string $text, int $scale = 8): string
    {
        $m = self::matrix($text);
        $n = count($m);
        $quiet = 4;
        $w = ($n + 2 * $quiet) * $scale;
        $img = imagecreate($w, $w);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0x23, 0x2B, 0x2D);
        imagefill($img, 0, 0, $white);
        for ($y = 0; $y < $n; $y++) for ($x = 0; $x < $n; $x++) {
            if ($m[$y][$x]) imagefilledrectangle($img, ($x + $quiet) * $scale, ($y + $quiet) * $scale, ($x + $quiet + 1) * $scale - 1, ($y + $quiet + 1) * $scale - 1, $black);
        }
        ob_start();
        imagepng($img);
        imagedestroy($img);
        return (string)ob_get_clean();
    }

    private function encode(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $len = count($bytes);
        $ver = 0;
        for ($v = 1; $v <= 40; $v++) {
            $cap = $this->dataCodewords($v) * 8;
            $need = 4 + ($v <= 9 ? 8 : 16) + 8 * $len;
            if ($need <= $cap) { $ver = $v; break; }
        }
        if ($ver === 0) throw new RuntimeException('Texte trop long pour un QR code');

        // Flux de bits
        $bits = [];
        $push = function (int $val, int $n) use (&$bits) { for ($i = $n - 1; $i >= 0; $i--) $bits[] = ($val >> $i) & 1; };
        $push(0b0100, 4);
        $push($len, $ver <= 9 ? 8 : 16);
        foreach ($bytes as $b) $push($b, 8);
        $capBits = $this->dataCodewords($ver) * 8;
        $push(0, min(4, $capBits - count($bits)));
        $push(0, (8 - count($bits) % 8) % 8);
        for ($pad = 0xEC; count($bits) < $capBits; $pad ^= 0xEC ^ 0x11) $push($pad, 8);
        $data = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $v = 0;
            for ($j = 0; $j < 8; $j++) $v = ($v << 1) | $bits[$i + $j];
            $data[] = $v;
        }

        $this->size = $ver * 4 + 17;
        $this->mod = array_fill(0, $this->size, array_fill(0, $this->size, false));
        $this->fn = $this->mod;
        $this->drawFunctionPatterns($ver);
        $all = $this->addEcc($data, $ver);
        $this->drawCodewords($all);

        $bestMask = 0; $minPen = PHP_INT_MAX;
        for ($m = 0; $m < 8; $m++) {
            $this->applyMask($m);
            $this->drawFormatBits($m);
            $p = $this->penalty();
            if ($p < $minPen) { $minPen = $p; $bestMask = $m; }
            $this->applyMask($m);
        }
        $this->applyMask($bestMask);
        $this->drawFormatBits($bestMask);
        return $this->mod;
    }

    private function rawModules(int $ver): int
    {
        $r = (16 * $ver + 128) * $ver + 64;
        if ($ver >= 2) {
            $na = intdiv($ver, 7) + 2;
            $r -= (25 * $na - 10) * $na - 55;
            if ($ver >= 7) $r -= 36;
        }
        return $r;
    }

    private function dataCodewords(int $ver): int
    {
        return intdiv($this->rawModules($ver), 8) - self::ECC_M[$ver] * self::BLOCKS_M[$ver];
    }

    private function set(int $x, int $y, bool $dark): void
    {
        $this->mod[$y][$x] = $dark;
        $this->fn[$y][$x] = true;
    }

    private function drawFunctionPatterns(int $ver): void
    {
        $s = $this->size;
        for ($i = 0; $i < $s; $i++) { $this->set(6, $i, $i % 2 === 0); $this->set($i, 6, $i % 2 === 0); }
        foreach ([[3, 3], [$s - 4, 3], [3, $s - 4]] as [$cx, $cy]) {
            for ($dy = -4; $dy <= 4; $dy++) for ($dx = -4; $dx <= 4; $dx++) {
                $x = $cx + $dx; $y = $cy + $dy;
                if ($x < 0 || $y < 0 || $x >= $s || $y >= $s) continue;
                $dist = max(abs($dx), abs($dy));
                $this->set($x, $y, $dist !== 2 && $dist !== 4);
            }
        }
        $pos = $this->alignmentPositions($ver);
        $n = count($pos);
        for ($i = 0; $i < $n; $i++) for ($j = 0; $j < $n; $j++) {
            if (($i === 0 && $j === 0) || ($i === 0 && $j === $n - 1) || ($i === $n - 1 && $j === 0)) continue;
            for ($dy = -2; $dy <= 2; $dy++) for ($dx = -2; $dx <= 2; $dx++) {
                $this->set($pos[$i] + $dx, $pos[$j] + $dy, max(abs($dx), abs($dy)) !== 1);
            }
        }
        $this->drawFormatBits(0);
        if ($ver >= 7) {
            $rem = $ver;
            for ($i = 0; $i < 12; $i++) $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
            $bits = ($ver << 12) | $rem;
            for ($i = 0; $i < 18; $i++) {
                $bit = (($bits >> $i) & 1) === 1;
                $a = $s - 11 + $i % 3; $b = intdiv($i, 3);
                $this->set($a, $b, $bit);
                $this->set($b, $a, $bit);
            }
        }
    }

    private function alignmentPositions(int $ver): array
    {
        if ($ver === 1) return [];
        $na = intdiv($ver, 7) + 2;
        $step = $ver === 32 ? 26 : (int)ceil(($ver * 4 + 4) / ($na * 2 - 2)) * 2;
        $res = [];
        for ($i = 0, $p = $this->size - 7; $i < $na - 1; $i++, $p -= $step) array_unshift($res, $p);
        array_unshift($res, 6);
        return $res;
    }

    private function drawFormatBits(int $mask): void
    {
        $data = (0 << 3) | $mask; // niveau M = 00
        $rem = $data;
        for ($i = 0; $i < 10; $i++) $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        $bits = (($data << 10) | $rem) ^ 0x5412;
        $b = fn(int $i) => (($bits >> $i) & 1) === 1;
        $s = $this->size;
        for ($i = 0; $i <= 5; $i++) $this->set(8, $i, $b($i));
        $this->set(8, 7, $b(6));
        $this->set(8, 8, $b(7));
        $this->set(7, 8, $b(8));
        for ($i = 9; $i < 15; $i++) $this->set(14 - $i, 8, $b($i));
        for ($i = 0; $i < 8; $i++) $this->set($s - 1 - $i, 8, $b($i));
        for ($i = 8; $i < 15; $i++) $this->set(8, $s - 15 + $i, $b($i));
        $this->set(8, $s - 8, true);
    }

    private function addEcc(array $data, int $ver): array
    {
        $numBlocks = self::BLOCKS_M[$ver];
        $eccLen = self::ECC_M[$ver];
        $raw = intdiv($this->rawModules($ver), 8);
        $numShort = $numBlocks - $raw % $numBlocks;
        $shortLen = intdiv($raw, $numBlocks);
        $div = $this->rsDivisor($eccLen);
        $blocks = [];
        for ($i = 0, $k = 0; $i < $numBlocks; $i++) {
            $n = $shortLen - $eccLen + ($i < $numShort ? 0 : 1);
            $dat = array_slice($data, $k, $n);
            $k += $n;
            $ecc = $this->rsRemainder($dat, $div);
            if ($i < $numShort) $dat[] = 0;
            $blocks[] = array_merge($dat, $ecc);
        }
        $out = [];
        $blen = count($blocks[0]);
        for ($i = 0; $i < $blen; $i++) {
            foreach ($blocks as $j => $blk) {
                if ($i !== $shortLen - $eccLen || $j >= $numShort) $out[] = $blk[$i];
            }
        }
        return $out;
    }

    private function rsMul(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }
        return $z & 0xFF;
    }

    private function rsDivisor(int $degree): array
    {
        $res = array_fill(0, $degree, 0);
        $res[$degree - 1] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $res[$j] = $this->rsMul($res[$j], $root);
                if ($j + 1 < $degree) $res[$j] ^= $res[$j + 1];
            }
            $root = $this->rsMul($root, 0x02);
        }
        return $res;
    }

    private function rsRemainder(array $data, array $div): array
    {
        $res = array_fill(0, count($div), 0);
        foreach ($data as $b) {
            $factor = $b ^ array_shift($res);
            $res[] = 0;
            foreach ($div as $i => $coef) $res[$i] ^= $this->rsMul($coef, $factor);
        }
        return $res;
    }

    private function drawCodewords(array $data): void
    {
        $s = $this->size;
        $i = 0;
        $total = count($data) * 8;
        for ($right = $s - 1; $right >= 1; $right -= 2) {
            if ($right === 6) $right = 5;
            for ($vert = 0; $vert < $s; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? $s - 1 - $vert : $vert;
                    if (!$this->fn[$y][$x] && $i < $total) {
                        $this->mod[$y][$x] = (($data[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                        $i++;
                    }
                }
            }
        }
    }

    private function applyMask(int $m): void
    {
        $s = $this->size;
        for ($y = 0; $y < $s; $y++) for ($x = 0; $x < $s; $x++) {
            if ($this->fn[$y][$x]) continue;
            $inv = match ($m) {
                0 => ($x + $y) % 2 === 0,
                1 => $y % 2 === 0,
                2 => $x % 3 === 0,
                3 => ($x + $y) % 3 === 0,
                4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                5 => ($x * $y % 2 + $x * $y % 3) === 0,
                6 => (($x * $y % 2 + $x * $y % 3) % 2) === 0,
                7 => ((($x + $y) % 2 + $x * $y % 3) % 2) === 0,
            };
            if ($inv) $this->mod[$y][$x] = !$this->mod[$y][$x];
        }
    }

    private function penalty(): int
    {
        $s = $this->size; $m = $this->mod; $p = 0; $dark = 0;
        $pat1 = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
        $pat2 = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];
        for ($pass = 0; $pass < 2; $pass++) {
            for ($a = 0; $a < $s; $a++) {
                $run = 1; $line = [];
                for ($b = 0; $b < $s; $b++) {
                    $v = $pass === 0 ? $m[$a][$b] : $m[$b][$a];
                    $line[] = $v ? 1 : 0;
                    if ($b > 0) {
                        $prev = $pass === 0 ? $m[$a][$b - 1] : $m[$b - 1][$a];
                        if ($v === $prev) { $run++; if ($run === 5) $p += 3; elseif ($run > 5) $p++; }
                        else $run = 1;
                    }
                }
                for ($b = 0; $b + 11 <= $s; $b++) {
                    $seg = array_slice($line, $b, 11);
                    if ($seg === $pat1 || $seg === $pat2) $p += 40;
                }
            }
        }
        for ($y = 0; $y < $s; $y++) for ($x = 0; $x < $s; $x++) {
            if ($m[$y][$x]) $dark++;
            if ($y < $s - 1 && $x < $s - 1 && $m[$y][$x] === $m[$y][$x + 1] && $m[$y][$x] === $m[$y + 1][$x] && $m[$y][$x] === $m[$y + 1][$x + 1]) $p += 3;
        }
        $total = $s * $s;
        $k = (int)ceil(abs($dark * 20 - $total * 10) / $total) - 1;
        return $p + max(0, $k) * 10;
    }
}
