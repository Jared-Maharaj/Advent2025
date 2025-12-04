<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$grid = array_map('str_split', $lines);
$rows = count($grid);
$cols = count($grid[0]);

// directions for the 8 adj positions
$dirs = [
    [-1, -1], [-1, 0], [-1, 1],
    [0, -1],          [0, 1],
    [1, -1], [1, 0],  [1, 1]
];

$totalRemoved = 0;

// we gotta keep the lifts moving
while (true) {
    $toRemove = [];

    for ($r = 0; $r < $rows; $r++) {
        for ($c = 0; $c < $cols; $c++) {

            // paper is only '@'
            if ($grid[$r][$c] !== '@') continue;

            $adj = 0;

            // count adj rolls
            foreach ($dirs as $d) {
                $nr = $r + $d[0];
                $nc = $c + $d[1];

                // eol
                if ($nr < 0 || $nr >= $rows || $nc < 0 || $nc >= $cols) continue;

                if ($grid[$nr][$nc] === '@') {
                    $adj++;
                }
            }

            // less than 4 adj, inc total
            if ($adj < 4) {
                $toRemove[] = [$r, $c];
            }
        }
    }

    // stop once no more are accessible
    if (empty($toRemove)) {
        break;
    }

    // remove all accessible rolls at once
    foreach ($toRemove as [$r, $c]) {
        $grid[$r][$c] = '.';  // mark removed
    }

    $totalRemoved += count($toRemove);
}

echo $totalRemoved;
