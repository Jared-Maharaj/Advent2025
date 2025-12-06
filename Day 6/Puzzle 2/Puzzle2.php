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
    $non = false;
    for ($r = 0; $r < $height; $r++) {
        if ($lines[$r][$c] !== ' ') { $non = true; break; }
    }
    if ($non) $current[] = $c;
    else if ($current) { $blocks[] = $current; $current = []; }
}
if ($current) $blocks[] = $current;

// each column inside a block is omne number
foreach ($blocks as $block) {

    $start = $block[0];
    $end = end($block);

    $operator = null;
    $operatorRow = null;

    for ($r = $height - 1; $r >= 0; $r--) {
        // sign is any + or * anywhere in the block
        for ($c = $start; $c <= $end; $c++) {
            $ch = $lines[$r][$c];
            if ($ch === '+' || $ch === '*') {
                $operator = $ch; 
                $operatorRow = $r;
                break 2; // break both loops
            }
        }
    }

    $numbers = [];

    // read numbers from top to one row above sign
    for ($c = $start; $c <= $end; $c++) {
        $digits = "";
        for ($r = 0; $r < $operatorRow; $r++) {
            $ch = $lines[$r][$c];
            if (ctype_digit($ch)) $digits .= $ch;
        }
        if ($digits !== "") $numbers[] = intval($digits);
    }

    // calculate
    $total += ($operator === '*') ? array_product($numbers) : array_sum($numbers);
}

echo $total, "\n";


