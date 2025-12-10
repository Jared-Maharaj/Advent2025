<?php

//
// PART 2 - Allowed tiles: only red + green
//
// Green tiles = the loop edges between each consecutive red point (wrapping)
//             + all tiles inside the closed polygon.
//

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$handle = fopen($filename, "r");
if (!$handle) {
    die("Error opening file.");
}

$red = [];

// --- Read red tile coordinates ---
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === "") continue;

    [$x, $y] = array_map("intval", preg_split('/\s*,\s*/', $line));
    $red[] = [$x, $y];
}
fclose($handle);

$n = count($red);
if ($n < 2) {
    echo 0;
    return;
}

// --- Determine grid bounds ---
$minX = $minY = PHP_INT_MAX;
$maxX = $maxY = PHP_INT_MIN;

foreach ($red as [$x, $y]) {
    $minX = min($minX, $x);
    $minY = min($minY, $y);
    $maxX = max($maxX, $x);
    $maxY = max($maxY, $y);
}

// Give some padding
$minX--; $minY--;
$maxX++; $maxY++;

// --- Create grid: false = not allowed, true = allowed (red/green) ---
$grid = [];
for ($y = $minY; $y <= $maxY; $y++) {
    for ($x = $minX; $x <= $maxX; $x++) {
        $grid[$y][$x] = false;
    }
}

// --- Mark red tiles ---
foreach ($red as [$x, $y]) {
    $grid[$y][$x] = true; // red
}

// --- Mark GREEN tiles along edges between each consecutive red pair ---
for ($i = 0; $i < $n; $i++) {
    [$x1, $y1] = $red[$i];
    [$x2, $y2] = $red[($i + 1) % $n]; // wrap

    if ($x1 === $x2) {
        // vertical line
        $start = min($y1, $y2);
        $end   = max($y1, $y2);
        for ($y = $start; $y <= $end; $y++) {
            $grid[$y][$x1] = true;
        }
    } else if ($y1 === $y2) {
        // horizontal line
        $start = min($x1, $x2);
        $end   = max($x1, $x2);
        for ($x = $start; $x <= $end; $x++) {
            $grid[$y1][$x] = true;
        }
    }
}

// --- Fill polygon interior using scanline filling ---
for ($y = $minY; $y <= $maxY; $y++) {
    $inside = false;

    for ($x = $minX; $x <= $maxX; $x++) {
        if ($grid[$y][$x] === true && isBoundary($red, $x, $y)) {
            // crossing edge -> toggle inside/outside
            $inside = !$inside;
        } else if ($inside) {
            $grid[$y][$x] = true; // interior is green
        }
    }
}

// --- Helper: detect if tile is one of the boundary points ---
function isBoundary($red, $x, $y) {
    foreach ($red as [$rx, $ry]) {
        if ($rx === $x && $ry === $y) return true;
    }
    return false;
}


// --- Now compute the largest rectangle where:
//       1. Opposite corners are RED
//       2. All tiles inside the rectangle are allowed (grid[y][x] == true)
//
$maxArea = 0;

for ($i = 0; $i < $n; $i++) {
    for ($j = $i + 1; $j < $n; $j++) {

        [$x1, $y1] = $red[$i];
        [$x2, $y2] = $red[$j];

        if ($x1 == $x2 && $y1 == $y2) continue;

        $xMin = min($x1, $x2);
        $xMax = max($x1, $x2);
        $yMin = min($y1, $y2);
        $yMax = max($y1, $y2);

        // Check ALL tiles in the rectangle interior
        $valid = true;
        for ($y = $yMin; $y <= $yMax && $valid; $y++) {
            for ($x = $xMin; $x <= $xMax; $x++) {
                if (!$grid[$y][$x]) {
                    $valid = false;
                    break;
                }
            }
        }

        if ($valid) {
            $area = ($xMax - $xMin + 1) * ($yMax - $yMin + 1);
            if ($area > $maxArea) $maxArea = $area;
        }
    }
}

echo $maxArea;

