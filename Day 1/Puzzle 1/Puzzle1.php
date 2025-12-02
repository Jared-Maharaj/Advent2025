<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$handle = fopen($filename, "r");
if (!$handle) {
    die("Error opening file.");
}

$start = 50;
$counter = 0;

while (($line = fgets($handle)) !== false) {

    if (!preg_match('/\d+/', $line, $matches)) {
        continue;
    }
    $number = (int)$matches[0];

    if ($line[0] === "L") {
        $start += $number;
    } else {
        $start -= $number;
    }

    $start = $start % 100;
    if ($start < 0) {
        $start += 100;
    }
    
    if ($start === 0) {
        $counter++;
    }
}

fclose($handle);

echo $counter;

?>
