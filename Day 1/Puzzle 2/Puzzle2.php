<?php
$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename\n");
}

$handle = fopen($filename, "r");
if (!$handle) {
    die("Unable to open file: $filename\n");
}

$start = 50;
$counter = 0;

while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === '') continue;

    $dirChar = strtoupper($line[0]);
    $direction = ($dirChar === 'L') ? 1 : -1;

    if (!preg_match('/\d+/', $line, $m)) {
        continue;
    }
    $number = (int)$m[0];

    $newPos = $start + ($direction * $number);

    if ($direction === 1) {
        $crosses = (int)floor($newPos / 100.0) - (int)floor($start / 100.0);
    } else {
        $crosses = (int)floor(($start - 1) / 100.0) - (int)floor(($newPos - 1) / 100.0);
    }

    if ($crosses > 0) {
        $counter += $crosses;
    }

    $start = (($newPos % 100) + 100) % 100;
}

fclose($handle);

echo $counter . PHP_EOL;
?>
