<?php
$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename\n");
}

$lines = file($filename, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    die("Error reading file.\n");
}

// parse only ranges
$ranges = [];
foreach ($lines as $raw) {
    $line = trim($raw);
    if ($line === '') break; // stop at first blank line

    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $line, $m)) {
        $a = intval($m[1]);
        $b = intval($m[2]);
        if ($a > $b) { $tmp = $a; $a = $b; $b = $tmp; }
        $ranges[] = [$a, $b];
    }
}

if (count($ranges) === 0) {
    echo 0 . PHP_EOL;
    exit;
}

// merge ranges
usort($ranges, function($x, $y) {
    if ($x[0] === $y[0]) return $x[1] <=> $y[1];
    return $x[0] <=> $y[0];
});

$merged = [];
$current = $ranges[0];
for ($i = 1; $i < count($ranges); $i++) {
    $r = $ranges[$i];
    if ($r[0] <= $current[1] + 1) {
        $current[1] = max($current[1], $r[1]);
    } else {
        $merged[] = $current;
        $current = $r;
    }
}
$merged[] = $current;

// count total fresh IDs
$total = 0;
foreach ($merged as [$a, $b]) {
    $total += ($b - $a + 1);
}

echo $total;
