<?php
$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename\n");
}

$lines = file($filename, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    die("Error reading file.\n");
}

// first block = ranges, blank line, second block = available IDs
$ranges = [];
$ids = [];
$mode = 'ranges';

foreach ($lines as $raw) {
    $line = trim($raw);
    if ($mode === 'ranges') {
        if ($line === '') {
            // blank line then switch to IDs
            $mode = 'ids';
            continue;
        }
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $line, $m)) {
            $a = intval($m[1]);
            $b = intval($m[2]);
            if ($a > $b) {
                // normalize if reversed
                $tmp = $a; $a = $b; $b = $tmp;
            }
            $ranges[] = [$a, $b];
        } else {
            // safety for messed up lines
        }
    } else { // ids mode
        if ($line === '') continue; // safety for extra blank lines
        if (preg_match('/^\d+$/', $line)) {
            $ids[] = intval($line);
        } else {
            // safety for messed up id lines
        }
    }
}

// if no ranges or no ids, result is 0
if (count($ranges) === 0 || count($ids) === 0) {
    echo 0 . PHP_EOL;
    exit;
}

// merge overlapping/adjacent ranges
usort($ranges, function($x, $y) {
    if ($x[0] === $y[0]) return $x[1] <=> $y[1];
    return $x[0] <=> $y[0];
});

$merged = [];
$current = $ranges[0];
for ($i = 1; $i < count($ranges); $i++) {
    $r = $ranges[$i];
    if ($r[0] <= $current[1] + 1) {
        // overlapping or adjacent: merge
        $current[1] = max($current[1], $r[1]);
    } else {
        $merged[] = $current;
        $current = $r;
    }
}
$merged[] = $current;

// binary-search
function isFresh(array $merged, int $id): bool {
    $lo = 0;
    $hi = count($merged) - 1;
    while ($lo <= $hi) {
        $mid = intdiv($lo + $hi, 2);
        $start = $merged[$mid][0];
        $end = $merged[$mid][1];
        if ($id < $start) {
            $hi = $mid - 1;
        } elseif ($id > $end) {
            $lo = $mid + 1;
        } else {
            return true;
        }
    }
    return false;
}

// count fresh IDs
$total = 0;
foreach ($ids as $id) {
    if (isFresh($merged, $id)) $total++;
}

echo $total;
