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

// read coordinates
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === '') continue;

    // split on comma with optional spaces
    $parts = preg_split('/\s*,\s*/', $line);
    if (count($parts) < 2) continue;

    $x = intval($parts[0]);
    $y = intval($parts[1]);

    $points[] = [$x, $y];
}
fclose($handle);

// compute largest rectangle formed by any two red tiles
$total = 0;
$n = count($points);

for ($i = 0; $i < $n; $i++) {
    for ($j = $i + 1; $j < $n; $j++) {
        [$x1, $y1] = $points[$i];
        [$x2, $y2] = $points[$j];

        // skip identical points
        if ($x1 === $x2 && $y1 === $y2) continue;

        $width  = abs($x2 - $x1) + 1; // inclusive
        $height = abs($y2 - $y1) + 1; // inclusive

        $area = $width * $height;
        if ($area > $total) $total = $area;
    }
}

echo $total;
