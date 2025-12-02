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

        // match A - B
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $range, $m)) {

            $start = $m[1];   // keep as string (may have leading zero)
            $end   = $m[2];

            for ($n = (int)$start; $n <= (int)$end; $n++) {

                // rebuild the number with same length as original start
                $len = strlen($start);
                $S   = str_pad((string)$n, $len, "0", STR_PAD_LEFT);

                // exclude numbers with leading zero
                if ($S[0] === '0') {
                    continue;
                }

                // check for repeating pattern
                $SS = $S . $S;

                // search S inside SS from index 1 to second-last
                $foundIndex = strpos(substr($SS, 1, -1), $S);

                if ($foundIndex !== false) {

                    $realIndex = $foundIndex + 1;

                    if ($realIndex < strlen($S)) {
                        // valid repeating pattern — add to sum
                        $sum += (int)$S;
                    }
                }
            }
        }
    }
}

fclose($handle);

echo $sum;
