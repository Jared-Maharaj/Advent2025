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
$K = 12; // we want the highest 12-digit number

while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === "") continue;

    $chars = str_split($line);
    $n = count($chars);

    // how many we must discard
    $toRemove = $n - $K;
    $stack = [];

    foreach ($chars as $digit) {
        while (
            $toRemove > 0 &&
            !empty($stack) &&
            end($stack) < $digit
        ) {
            array_pop($stack);
            $toRemove--;
        }

        $stack[] = $digit;
    }

    // truncate to K digits (in case we didn't remove enough)
    $best12 = array_slice($stack, 0, $K);

    $num = intval(implode("", $best12));
    $total += $num;
}

fclose($handle);

echo $total;
