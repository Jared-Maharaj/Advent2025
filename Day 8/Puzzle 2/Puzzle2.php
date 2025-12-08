<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename\n");
}

$handle = fopen($filename, "r");
if (!$handle) {
    die("Error opening file.\n");
}

$points = [];
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === '') continue; // skip blank lines
    $parts = explode(",", $line);
    if (count($parts) !== 3) continue; // skip wierd lines
    $points[] = [(int)$parts[0], (int)$parts[1], (int)$parts[2]];
}
fclose($handle);

$n = count($points);
if ($n < 2) {
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
        $d = $dx*$dx + $dy*$dy + $dz*$dz;
        $edges[] = ['i' => $i, 'j' => $j, 'd' => $d];
    }
}

// sort edges by distance
usort($edges, function($a, $b) {
    return $a['d'] <=> $b['d'];
});

// disjoint set
$parent = range(0, $n - 1);
$size = array_fill(0, $n, 1);
$components = $n; // how many separate circuits still exist?

function find_root(&$parent, $x) {
    if ($parent[$x] !== $x) {
        $parent[$x] = find_root($parent, $parent[$x]);
    }
    return $parent[$x];
}

function union_sets(&$parent, &$size, $a, $b, &$components) {
    $ra = find_root($parent, $a);
    $rb = find_root($parent, $b);
    if ($ra === $rb) return false;

    if ($size[$ra] < $size[$rb]) {
        $parent[$ra] = $rb;
        $size[$rb] += $size[$ra];
    } else {
        $parent[$rb] = $ra;
        $size[$ra] += $size[$rb];
    }
    $components--;
    return true;
}

$last_i = -1;
$last_j = -1;

foreach ($edges as $e) {
    $i = $e['i'];
    $j = $e['j'];

    $merged = union_sets($parent, $size, $i, $j, $components);

    if ($merged && $components === 1) {
        // this is the final required connection
        $last_i = $i;
        $last_j = $j;
        break;
    }
}

if ($last_i === -1) {
    echo "0\n";
    exit;
}

$x1 = $points[$last_i][0];
$x2 = $points[$last_j][0];

$total = $x1 * $x2;

echo $total;
