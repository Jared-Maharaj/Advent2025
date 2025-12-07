<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$lines = file($filename, FILE_IGNORE_NEW_LINES);
if ($lines === false) die("Error reading file.");

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
if ($startRow === -1) die("Start 'S' not found.");

// beams entering the next row
$incoming = [];
if ($startRow + 1 < $rows) {
    $incoming[$startCol] = 1;
} else {
    echo "1\n";
    exit;
}

for ($r = $startRow + 1; $r < $rows; $r++) {
    if (empty($incoming)) {
        $incoming = [];
        break;
    }

    $present = $incoming;
    $next_incoming = [];
    $queue = array_keys($present);

    // iterate row by row
    while (!empty($queue)) {
        $col = array_pop($queue);
        if (!isset($present[$col]) || $present[$col] === 0) continue;
        if ($col < 0 || $col >= $width) {
            unset($present[$col]);
            continue;
        }

        $amt = $present[$col];
        $present[$col] = 0;

        $cell = $lines[$r][$col];

        if ($cell === '^') {
            // spawn beams left and right on the same row
            $left = $col - 1;
            $right = $col + 1;

            if ($left >= 0) {
                // first time adding something to this column this row -> queue it
                if (!isset($present[$left]) || $present[$left] === 0) $queue[] = $left;
                if (!isset($present[$left])) $present[$left] = 0;
                $present[$left] += $amt;
            }

            if ($right < $width) {
                if (!isset($present[$right]) || $present[$right] === 0) $queue[] = $right;
                if (!isset($present[$right])) $present[$right] = 0;
                $present[$right] += $amt;
            }
            // original beam stops at the splitter
        } else {
            if (!isset($next_incoming[$col])) $next_incoming[$col] = 0;
            $next_incoming[$col] += $amt;
        }
    }

    // move to next row
    $incoming = $next_incoming;
}

$total = 0;
foreach ($incoming as $v) $total += $v;

echo $total;
