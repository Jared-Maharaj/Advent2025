<?php

$filename = "input.txt";
if (!file_exists($filename)) die("File not found");
$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$red = [];
foreach ($lines as $line) {
    [$x, $y] = array_map('intval', preg_split('/\s*,\s*/', trim($line)));
    $red[] = [$x, $y];
}

$n = count($red);
if ($n < 2) { echo 0; return; }

// build rectangle
$edges = [];
for ($i = 0; $i < $n; $i++) {
    [$x1, $y1] = $red[$i];
    [$x2, $y2] = $red[($i+1)%$n];

    // only horizontal or vertical
    $edges[] = [$x1, $y1, $x2, $y2];
}

// point-in-polygon
function pointInPoly($x, $y, $edges) {
    $inside = false;

    foreach ($edges as [$x1,$y1,$x2,$y2]) {
        // horizontal edge
        if ($y1 == $y2) continue;

        // is point between vertical span?
        if (($y >= min($y1,$y2)) && ($y < max($y1,$y2))) {

            // find x coordinate of intersection of ray to the righ
            $qx = $x1 + ($x2 - $x1) * ($y - $y1) / ($y2 - $y1);

            if ($qx > $x) {
                $inside = !$inside;
            }
        }
    }
    return $inside;
}

// segment intersection
function segmentsIntersect($ax,$ay,$bx,$by, $cx,$cy,$dx,$dy) {

    // bounding boxes
    if (max($ax,$bx) < min($cx,$dx)) return false;
    if (max($cx,$dx) < min($ax,$bx)) return false;
    if (max($ay,$by) < min($cy,$dy)) return false;
    if (max($cy,$dy) < min($ay,$by)) return false;

    $d1 = ($dx-$cx)*($ay-$cy) - ($dy-$cy)*($ax-$cx);
    $d2 = ($dx-$cx)*($by-$cy) - ($dy-$cy)*($bx-$cx);
    $d3 = ($bx-$ax)*($cy-$ay) - ($by-$ay)*($cx-$ax);
    $d4 = ($bx-$ax)*($dy-$ay) - ($by-$ay)*($dx-$ax);

    if ((($d1>0 && $d2<0) || ($d1<0 && $d2>0)) &&
        (($d3>0 && $d4<0) || ($d3<0 && $d4>0))) {
        return true;
    }

    return false;
}

// check if rectangle is fully inside poly
function rectangleValid($x1,$y1,$x2,$y2,$edges) {

    $xmin = min($x1,$x2);
    $xmax = max($x1,$x2);
    $ymin = min($y1,$y2);
    $ymax = max($y1,$y2);

    // check 4 corners inside or on boundary
    $corners = [
        [$xmin,$ymin],
        [$xmin,$ymax],
        [$xmax,$ymin],
        [$xmax,$ymax]
    ];

    foreach ($corners as [$cx,$cy]) {
        $inside = pointInPoly($cx, $cy, $edges);
        $onEdge = false;

        // check if corner lies exactly on polygon edge
        foreach ($edges as [$ex1,$ey1,$ex2,$ey2]) {
            if ($ex1 == $ex2 && $cx == $ex1 && $cy >= min($ey1,$ey2) && $cy <= max($ey1,$ey2)) {
                $onEdge = true;
                break;
            }
            if ($ey1 == $ey2 && $cy == $ey1 && $cx >= min($ex1,$ex2) && $cx <= max($ex1,$ex2)) {
                $onEdge = true;
                break;
            }
        }

        if (!$inside && !$onEdge) return false;
    }

    // check rectangle edges against polygon edges
    $rectEdges = [
        [$xmin,$ymin,$xmax,$ymin], // top
        [$xmin,$ymax,$xmax,$ymax], // bottom
        [$xmin,$ymin,$xmin,$ymax], // left
        [$xmax,$ymin,$xmax,$ymax], // right
    ];

    foreach ($rectEdges as [$ax,$ay,$bx,$by]) {
        foreach ($edges as [$cx,$cy,$dx,$dy]) {
            // ignore if touching at endpoints
            if (segmentsIntersect($ax,$ay,$bx,$by, $cx,$cy,$dx,$dy)) {
                return false;
            }
        }
    }

    return true;
}

// Try all red tile pairs
$maxArea = 0;

for ($i = 0; $i < $n; $i++) {
    for ($j = $i+1; $j < $n; $j++) {

        [$x1,$y1] = $red[$i];
        [$x2,$y2] = $red[$j];

        $area = (abs($x2-$x1)+1) * (abs($y2-$y1)+1);

        // skip if even area cant beat current best
        if ($area <= $maxArea) continue;

        if (rectangleValid($x1,$y1,$x2,$y2,$edges)) {
            $maxArea = $area;
        }
    }
}

echo $maxArea;
