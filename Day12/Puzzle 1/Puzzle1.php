<?php
$filename = "input.txt";
if (!file_exists($filename)) {
    die("File not found: $filename");
}
$handle = fopen($filename, "r");
if (!$handle) {
    die("Error opening file.");
}

// read all lines into memory
$lines = [];
while (($line = fgets($handle)) !== false) {
    $lines[] = rtrim($line, "\r\n");
}
fclose($handle);

// parse shapes
$shapes = [];        // list of [x,y] cells
$shapeAreas = [];    // area (number of cells)

$n = count($lines);
$i = 0;

// read shapes until we hit the first region line
while ($i < $n) {
    $line = trim($lines[$i]);

    if ($line === '') {
        $i++;
        continue;
    }

    // shape header
    if (preg_match('/^(\d+):/', $line, $m)) {
        $shapeIndex = (int)$m[1];
        $i++;

        $grid = [];
        // read shape grid until blank line or region line
        while ($i < $n && trim($lines[$i]) !== '') {
            if (preg_match('/^\d+x\d+:/', trim($lines[$i]))) {
                break; // region section starts
            }
            $grid[] = $lines[$i];
            $i++;
        }

        // convert grid to list of cells
        $cells = [];
        for ($y = 0; $y < count($grid); $y++) {
            $row = $grid[$y];
            $len = strlen($row);
            for ($x = 0; $x < $len; $x++) {
                if ($row[$x] === '#') {
                    $cells[] = [$x, $y];
                }
            }
        }

        $shapes[$shapeIndex] = $cells;
        $shapeAreas[$shapeIndex] = count($cells);
        continue;
    }

    // first region line encountered -> stop parsing shapess
    if (preg_match('/^\d+x\d+:/', $line)) {
        break;
    }

    $i++;
}

// sort shapes by index
ksort($shapes);
ksort($shapeAreas);

// generate rotations + flips
function generate_orientations($cells) {
    $orientations = [];
    $seen = [];

    foreach ([false, true] as $mirrorX) {
        for ($rot = 0; $rot < 4; $rot++) {
            $transformed = [];

            foreach ($cells as $cell) {
                $x = $cell[0];
                $y = $cell[1];

                // horizontal mirror
                if ($mirrorX) {
                    $x = -$x;
                }

                // rotations around origin
                switch ($rot) {
                    case 0: $x2 = $x;   $y2 = $y;   break;
                    case 1: $x2 = -$y;  $y2 = $x;   break;
                    case 2: $x2 = -$x;  $y2 = -$y;  break;
                    case 3: $x2 = $y;   $y2 = -$x;  break;
                }

                $transformed[] = [$x2, $y2];
            }

            // normalize so min x,y = 0,0
            $minX = PHP_INT_MAX;
            $minY = PHP_INT_MAX;
            foreach ($transformed as $t) {
                if ($t[0] < $minX) $minX = $t[0];
                if ($t[1] < $minY) $minY = $t[1];
            }
            foreach ($transformed as &$t) {
                $t[0] -= $minX;
                $t[1] -= $minY;
            }
            unset($t);

            // sort cells
            usort($transformed, function($a, $b) {
                if ($a[1] === $b[1]) {
                    return $a[0] <=> $b[0];
                }
                return $a[1] <=> $b[1];
            });

            $sigParts = [];
            foreach ($transformed as $t) {
                $sigParts[] = $t[0] . "," . $t[1];
            }
            $sig = implode(";", $sigParts);

            if (!isset($seen[$sig])) {
                $seen[$sig] = true;

                // bounding box
                $maxX = 0;
                $maxY = 0;
                foreach ($transformed as $t) {
                    if ($t[0] > $maxX) $maxX = $t[0];
                    if ($t[1] > $maxY) $maxY = $t[1];
                }

                $orientations[] = [
                    'cells' => $transformed,
                    'w'     => $maxX + 1,
                    'h'     => $maxY + 1,
                ];
            }
        }
    }

    return $orientations;
}

// orientations for each shape
$shapeOrientations = [];   // shapeIndex => list of orientations
foreach ($shapes as $idx => $cells) {
    $shapeOrientations[$idx] = generate_orientations($cells);
}

// parse regions
$regions = [];

for (; $i < $n; $i++) {
    $line = trim($lines[$i]);
    if ($line === '') {
        continue;
    }

    if (preg_match('/^(\d+)x(\d+):\s*(.*)$/', $line, $m)) {
        $w = (int)$m[1];
        $h = (int)$m[2];
        $rest = trim($m[3]);

        $parts = $rest === '' ? [] : preg_split('/\s+/', $rest);

        $counts = [];
        $shapeIndices = array_keys($shapes);
        sort($shapeIndices);
        foreach ($shapeIndices as $idxPos => $shapeIdx) {
            $counts[$shapeIdx] = isset($parts[$idxPos]) ? (int)$parts[$idxPos] : 0;
        }

        $regions[] = [
            'w' => $w,
            'h' => $h,
            'counts' => $counts,
        ];
    }
}

// backtracking solver for a single region
function search_region($pieceIndex, &$board, $pieces, $shapeOrientations, $w, $h, $totalPieces) {
    if ($pieceIndex === $totalPieces) {
        // all presents placed - we dont care about empty cells
        return true;
    }

    $shapeIdx = $pieces[$pieceIndex];
    $orientList = $shapeOrientations[$shapeIdx];

    // try every orientation of this shape
    foreach ($orientList as $orient) {
        $cells = $orient['cells'];
        $ow    = $orient['w'];
        $oh    = $orient['h'];

        // slide this orientation over every position
        for ($y = 0; $y <= $h - $oh; $y++) {
            for ($x = 0; $x <= $w - $ow; $x++) {
                $fits = true;
                $occupied = [];

                foreach ($cells as $c) {
                    $nx = $x + $c[0];
                    $ny = $y + $c[1];

                    if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                        $fits = false;
                        break;
                    }

                    if ($board[$ny][$nx]) {
                        // collision with already placed present
                        $fits = false;
                        break;
                    }

                    $occupied[] = [$nx, $ny];
                }

                if (!$fits) {
                    continue;
                }

                // place present
                foreach ($occupied as $o) {
                    $board[$o[1]][$o[0]] = true;
                }

                // recurse
                if (search_region($pieceIndex + 1, $board, $pieces, $shapeOrientations, $w, $h, $totalPieces)) {
                    return true;
                }

                // undo placement
                foreach ($occupied as $o) {
                    $board[$o[1]][$o[0]] = false;
                }
            }
        }
    }

    // no placement worked for this piece
    return false;
}

// decide if a region can fit all listed presents
function can_fill_region($w, $h, $counts, $shapeAreas, $shapeOrientations) {
    $totalAreaBoard = $w * $h;
    $totalAreaNeeded = 0;
    $pieces = [];

    // convert counts into a multiset of pieces
    foreach ($counts as $shapeIdx => $count) {
        if ($count <= 0) continue;
        $area = $shapeAreas[$shapeIdx];
        $totalAreaNeeded += $area * $count;
        for ($c = 0; $c < $count; $c++) {
            $pieces[] = $shapeIdx;
        }
    }

    // if presents exceed area, impossible
    if ($totalAreaNeeded > $totalAreaBoard) {
        return false;
    }

    // uif no presents, they fit
    if (empty($pieces)) {
        return true;
    }

    // place larger-area pieces first
    usort($pieces, function($a, $b) use ($shapeAreas) {
        return $shapeAreas[$b] <=> $shapeAreas[$a];
    });

    $totalPieces = count($pieces);

    // initialize empty board
    $board = [];
    for ($y = 0; $y < $h; $y++) {
        $row = [];
        for ($x = 0; $x < $w; $x++) {
            $row[] = false; // false = empty
        }
        $board[] = $row;
    }

    return search_region(0, $board, $pieces, $shapeOrientations, $w, $h, $totalPieces);
}

// evaluate all regions
$total = 0;

foreach ($regions as $region) {
    if (can_fill_region(
        $region['w'],
        $region['h'],
        $region['counts'],
        $shapeAreas,
        $shapeOrientations
    )) {
        $total++;
    }
}

echo $total;
