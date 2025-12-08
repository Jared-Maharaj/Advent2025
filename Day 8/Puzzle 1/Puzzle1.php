<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$handle = fopen($filename, "r");
if (!$handle) {
    die("Error opening file.");
}

$points = [];
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === '') continue; // skip blank lines
    $parts = explode(',', $line);
    if (count($parts) !== 3) continue; // skip wierd lines
    $x = (int) trim($parts[0]);
    $y = (int) trim($parts[1]);
    $z = (int) trim($parts[2]);
    $points[] = [$x, $y, $z];
}
fclose($handle);

$n = count($points);
if ($n === 0) {
    echo "0\n";
    exit;
}

// build all unique pairs with squared distance
$edges = [];
for ($i = 0; $i < $n; $i++) {
    list($xi, $yi, $zi) = $points[$i];
    for ($j = $i + 1; $j < $n; $j++) {
        list($xj, $yj, $zj) = $points[$j];
        $dx = $xi - $xj;
        $dy = $yi - $yj;
        $dz = $zi - $zj;
        // squared distance
        $d = $dx * $dx + $dy * $dy + $dz * $dz;
        $edges[] = ['i' => $i, 'j' => $j, 'd' => $d];
    }
}

// sort edges by distance
usort($edges, function($a, $b) {
    if ($a['d'] === $b['d']) return 0;
    return ($a['d'] < $b['d']) ? -1 : 1;
});

// disjoint set
$parent = range(0, $n - 1);
$size = array_fill(0, $n, 1);

function find_root(&$parent, $x) {
    if ($parent[$x] !== $x) {
        $parent[$x] = find_root($parent, $parent[$x]);
    }
    return $parent[$x];
}

function union_nodes(&$parent, &$size, $a, $b) {
    $ra = find_root($parent, $a);
    $rb = find_root($parent, $b);
    if ($ra === $rb) return false;
    // union by size: attach smaller to larger
    if ($size[$ra] < $size[$rb]) {
        $parent[$ra] = $rb;
        $size[$rb] += $size[$ra];
    } else {
        $parent[$rb] = $ra;
        $size[$ra] += $size[$rb];
    }
    return true;
}

// process first 1000 pairs
$pairsToProcess = min(1000, count($edges));
for ($k = 0; $k < $pairsToProcess; $k++) {
    $e = $edges[$k];
    union_nodes($parent, $size, $e['i'], $e['j']);
}

// component sizes after getting pairs
$compSizes = [];
for ($i = 0; $i < $n; $i++) {
    $r = find_root($parent, $i);
    if (!isset($compSizes[$r])) $compSizes[$r] = 0;
    $compSizes[$r]++;
}
$allSizes = array_values($compSizes);
rsort($allSizes, SORT_NUMERIC);

// ensure at least three sizes
while (count($allSizes) < 3) $allSizes[] = 1;

// multiply the sizes of the three largest circuits
$total = (int)$allSizes[0] * (int)$allSizes[1] * (int)$allSizes[2];

echo $total;
