<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$handle = fopen($filename, "r");
if (!$handle) {
    die("Error opening file.");
}

$total = 0;

while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === "") continue;

    $chars = str_split($line);
    $maxPair = 0;
    $length = count($chars);

    // LEFT to RIGHT
    for ($i = 0; $i < $length - 1; $i++) {
        $left = intval($chars[$i]);

        // try to find the highest possible digit to the RIGHT of $left
        for ($j = $i + 1; $j < $length; $j++) {
            $right = intval($chars[$j]);

            // build 2-dig number
            $num = $left * 10 + $right;

            if ($num > $maxPair) {
                $maxPair = $num;
            }
        }
    }

    $total += $maxPair;
}

fclose($handle);

echo $total;

