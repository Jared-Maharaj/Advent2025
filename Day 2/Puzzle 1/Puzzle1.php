<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename\n");
}

$handle = fopen($filename, "r");
if (!$handle) {
    die("Unable to open file: $filename\n");
}

$sum = 0;

while (($line = fgets($handle)) !== false) {

    $line = trim($line);
    if ($line === "") continue;

    $ranges = explode(",", $line);

    foreach ($ranges as $range) {

        $range = trim($range);

        // Match A - B
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $range, $m)) {

            $start = $m[1];   // keep as string (may have leading zero)
            $end   = $m[2];

            for ($n = (int)$start; $n <= (int)$end; $n++) {

                $S = (string)$n;

                // we need to check for a number repeating twice
                $len = strlen($S);
                if ($len > 0 && $len % 2 === 0) {
                    $half_len = $len / 2;
                    if (substr($S, 0, $half_len) === substr($S, $half_len)) {
                        // valid repeating pattern — add to sum
                        $sum += $n;
                    }
                }
            }
        }
    }
}

fclose($handle);

echo $sum;
