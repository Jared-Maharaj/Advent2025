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

// recursively count of all paths
function countPaths(array &$graph, string $current, string $target, array $visited = []): int
{
    // avoid cycles
    if (in_array($current, $visited, true)) {
        return 0;
    }

    // reached output
    if ($current === $target) {
        return 1;
    }

    // no outputs
    if (!isset($graph[$current]) || empty($graph[$current])) {
        return 0;
    }

    $visited[] = $current;
    $total = 0;

    foreach ($graph[$current] as $next) {
        $total += countPaths($graph, $next, $target, $visited);
    }

    return $total;
}

// calculate total valid paths
$total = countPaths($graph, "you", "out");

echo $total;
