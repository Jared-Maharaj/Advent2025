<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename\n");
}

$lines = file("input.txt", FILE_IGNORE_NEW_LINES);

// normalize line lengths
$maxWidth = 0;
foreach ($lines as $line) $maxWidth = max($maxWidth, strlen($line));
foreach ($lines as &$line) $line = str_pad($line, $maxWidth, " ");
unset($line);

$total = 0;
$height = count($lines);

$blocks = [];
$current = [];

// block = consecutive non-empty columns
for ($c = 0; $c < $maxWidth; $c++) {
    $colHasNonSpace = false;
    for ($r = 0; $r < $height; $r++) {
        if ($lines[$r][$c] !== ' ') { $colHasNonSpace = true; break; }
    }
    if ($colHasNonSpace) $current[] = $c;
    else if ($current) { $blocks[] = $current; $current = []; }
}
if ($current) $blocks[] = $current;

// process each block
foreach ($blocks as $block) {
    $start = $block[0];
    $end = $block[count($block)-1];
    $width = $end - $start + 1;

    $operator = null;
    $operatorRow = null;

    // read numbers from top to one row above sign
    for ($r = $height - 1; $r >= 0; $r--) {
        $segment = rtrim(substr($lines[$r], $start, $width));
        if ($segment !== "" && preg_match('/[+*]/', $segment, $m)) {
            $operator = $m[0];
            $operatorRow = $r;
            break;
        }
    }

    // 3xtract numbers above sign row
    $numbers = [];
    for ($r = 0; $r < $operatorRow; $r++) {
        $segment = trim(substr($lines[$r], $start, $width));
        if ($segment !== "" && ctype_digit($segment)) $numbers[] = intval($segment);
    }

    // calculate
    $total += ($operator === '*') ? array_product($numbers) : array_sum($numbers);
}

echo $total;