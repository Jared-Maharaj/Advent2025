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

}

fclose($handle);

echo $total;
