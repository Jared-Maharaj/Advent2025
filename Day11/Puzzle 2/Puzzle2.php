<?php

$filename = "input.txt";
if (!file_exists($filename)) {
    die("File not found: $filename");
}
$handle = fopen($filename, "r");
if (!$handle) {
    die("Error opening file.");
}

$graph = [];

// build adjacency list graph
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if ($line === "") continue;

    [$device, $outputs] = array_map('trim', explode(":", $line));

    if ($outputs === "") {
        $graph[$device] = [];
    } else {
        $graph[$device] = preg_split('/\s+/', trim($outputs));
    }
}

fclose($handle);

// DFS: count valid paths
function countValidPaths(array &$graph, string $current, string $target, array $visited = [], bool $sawDac = false, bool $sawFft = false): int
{
    // avoid cycles
    if (in_array($current, $visited, true)) {
        return 0;
    }

    $visited[] = $current;

    // track required nodes
    if ($current === "dac") $sawDac = true;
    if ($current === "fft") $sawFft = true;

    // reached output -> only valid if both visited
    if ($current === $target) {
        return ($sawDac && $sawFft) ? 1 : 0;
    }

    // no outputs
    if (!isset($graph[$current]) || empty($graph[$current])) {
        return 0;
    }

    $total = 0;
    foreach ($graph[$current] as $next) {
        $total += countValidPaths($graph, $next, $target, $visited, $sawDac, $sawFft);
    }

    return $total;
}

// calculate total valid paths
$total = countValidPaths($graph, "svr", "out");

echo $total;
