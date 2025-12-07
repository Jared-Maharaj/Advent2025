<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$lines = file($filename, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    die("Error reading file.");
}

$rows = count($lines);
if ($rows === 0) {
    echo "0";
    exit;
}

// assume all lines same width
$width = strlen($lines[0]);

// find S
$startRow = -1;
$startCol = -1;
for ($r = 0; $r < $rows; $r++) {
    $c = strpos($lines[$r], 'S');
    if ($c !== false) {
        $startRow = $r;
        $startCol = $c;
        break;
    }
}
if ($startRow === -1) {
    die("Start 'S' not found.");
}

$total = 0;

// beams entering the next row
$incoming = [];
if ($startRow + 1 < $rows) {
    $incoming[$startCol] = true;
}

// iterate row by row
for ($r = $startRow + 1; $r < $rows; $r++) {
    if (empty($incoming)) break;

    $queue = array_keys($incoming);
    $visited = [];
    $present = [];
    // process beams for this row
    while (!empty($queue)) {
        $col = array_pop($queue);
        if (isset($visited[$col])) continue;
        $visited[$col] = true;

        // ignore columns
        if ($col < 0 || $col >= $width) continue;

        $ch = $lines[$r][$col];

        if ($ch === '^') {
            // beam is split here
            $total++;
            // spawn beams left and right on the same row
            $left = $col - 1;
            $right = $col + 1;
            if ($left >= 0 && !isset($visited[$left])) $queue[] = $left;
            if ($right < $width && !isset($visited[$right])) $queue[] = $right;
            // the original beam stops at this splitter
        } else {
            // beam in this column in this row and will go down next row
            $present[$col] = true;
        }
    }

    // beams in next row are the present columns from this row
    $incoming = $present;
}

echo $total;
