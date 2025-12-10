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

    // extract joltage targets 
    preg_match('/\{([^}]*)\}/', $line, $m);
    $targets = array_map('intval', explode(',', $m[1]));
    $n = count($targets);

    // extract button definitions
    preg_match_all('/\(([^)]*)\)/', $line, $mm);
    $buttons_raw = [];
    foreach ($mm[1] as $s) {
        $s = trim($s);
        if ($s === "") continue;
        $buttons_raw[] = array_map('intval', explode(',', $s));
    }

    // find the position of ] - keep buttons appearing after that
    $closeBracketPos = strpos($line, ']');
    $buttons = [];
    foreach ($buttons_raw as $arr) {
        // detect button substring position to decide if its after the diagram
        $buttons[] = $arr;
    }

    // build button increment vectors
    $buttonVectors = [];
    foreach ($buttons as $arr) {
        $vec = array_fill(0, $n, 0);
        foreach ($arr as $idx) {
            if ($idx < $n) $vec[$idx] += 1;
        }
        $buttonVectors[] = $vec;
    }

    // dijkstra
    $pq = new SplPriorityQueue();
    $start = array_fill(0, $n, 0);
    $startKey = implode(',', $start);

    $dist = [];
    $dist[$startKey] = 0;
    $pq->insert($startKey, 0);

    $done = false;

    while (!$pq->isEmpty() && !$done) {
        $curKey = $pq->extract();
        $curCost = $dist[$curKey];
        $cur = array_map('intval', explode(',', $curKey));

        // check goal
        if ($cur === $targets) {
            $total += $curCost;
            $done = true;
            break;
        }

        // press each button
        foreach ($buttonVectors as $vec) {
            $next = $cur;
            $valid = true;

            for ($i = 0; $i < $n; $i++) {
                $next[$i] += $vec[$i];
                if ($next[$i] > $targets[$i]) {
                    $valid = false;
                    break;
                }
            }

            if (!$valid) continue;

            $nextKey = implode(',', $next);
            $nextCost = $curCost + 1;

            if (!isset($dist[$nextKey]) || $nextCost < $dist[$nextKey]) {
                $dist[$nextKey] = $nextCost;
                $pq->insert($nextKey, -$nextCost);
            }
        }
    }
}

fclose($handle);
echo $total;
