<?php

$filename = "input.txt";

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$handle = fopen($filename, "r");
if (!$handle) {
    die("Error opening file.");
}

$tree = "";

while (($line = fgets($handle)) !== false) {
    $line = trim($line);

    if ($line === "") {
        continue;
    }

    // split on whitespace
    $expressions = preg_split('/\s+/', $line);

    $row = "";

    foreach ($expressions as $expr) {
        if ($expr === "") {
            continue;
        }

        // only digits +ops
        if (!preg_match('/^[0-9+\-*\/() ]+$/', $expr)) {
            continue; 
        }

        // evaluate the expression to get an ASCII code
        $value = eval("return $expr;");

        // convert numeric ASCII code to a character
        $row .= chr($value);
    }

    // add this row to the final tree with a newline
    $tree .= $row . PHP_EOL;
}

fclose($handle);

// output the full tree
echo $tree . PHP_EOL;
echo "Merry Christmas" . PHP_EOL;
