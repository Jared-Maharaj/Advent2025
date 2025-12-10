<?php
$filename = "input.txt";
if (!file_exists($filename)) { die("File not found: $filename"); }
$handle = fopen($filename, "r");
if (!$handle) { die("Error opening file."); }

$total = 0;

function parse_line($line) {
    // trim and ignore empty lines
    $line = trim($line);
    if ($line === "" || $line[0] === '#') return null;

    // extract the indicator pattern in [ ... ]
    if (!preg_match('/\[(.*?)\]/', $line, $m)) return null;
    $pattern = $m[1];

    // extract all button groups
    preg_match_all('/\((.*?)\)/', $line, $mb);
    $buttons_raw = $mb[1]; // array of 0,3,4 strings

    // parse buttons into arrays
    $buttons = array();
    foreach ($buttons_raw as $bstr) {
        $bstr = trim($bstr);
        if ($bstr === "") { $buttons[] = array(); continue; }
        $parts = preg_split('/\s*,\s*/', $bstr, -1, PREG_SPLIT_NO_EMPTY);
        $col = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === "") continue;
            $col[] = intval($p);
        }
        $buttons[] = $col;
    }

    return array('pattern' => $pattern, 'buttons' => $buttons);
}

function bitcount_array($arr) {
    // array of 0/1
    $c = 0;
    foreach ($arr as $v) { if ($v & 1) $c++; }
    return $c;
}

function xor_vec_inplace(&$a, $b) {
    $n = count($a);
    for ($i = 0; $i < $n; $i++) $a[$i] = ($a[$i] ^ $b[$i]) & 1;
}

function solve_machine($pattern, $buttons) {
    // pattern 
    $n = strlen($pattern);
    $m = count($buttons);

    // build matrix A
    $A = array();
    for ($i = 0; $i < $n; $i++) {
        $A[$i] = array_fill(0, $m, 0);
    }
    for ($j = 0; $j < $m; $j++) {
        foreach ($buttons[$j] as $idx) {
            if ($idx >= 0 && $idx < $n) $A[$idx][$j] = 1;
        }
    }
    // target b
    $b = array();
    for ($i = 0; $i < $n; $i++) $b[$i] = ($pattern[$i] === '#') ? 1 : 0;

    // Gaussian elimination over GF(2)
    $row = 0;
    $pivot_of_col = array_fill(0, $m, -1);
    for ($col = 0; $col < $m && $row < $n; $col++) {
        // find row with 1 in this col at or below row
        $sel = -1;
        for ($r = $row; $r < $n; $r++) {
            if ($A[$r][$col] === 1) { $sel = $r; break; }
        }
        if ($sel === -1) continue; // no pivot in this column
        // swap selected row into position row
        if ($sel !== $row) {
            $tmp = $A[$sel]; $A[$sel] = $A[$row]; $A[$row] = $tmp;
            $tmpb = $b[$sel]; $b[$sel] = $b[$row]; $b[$row] = $tmpb;
        }
        $pivot_of_col[$col] = $row;
        // eliminate column in all other rows
        for ($r = 0; $r < $n; $r++) {
            if ($r === $row) continue;
            if ($A[$r][$col] === 1) {
                // row_r = row_r XOR row_row
                for ($c2 = $col; $c2 < $m; $c2++) {
                    $A[$r][$c2] ^= $A[$row][$c2];
                }
                $b[$r] ^= $b[$row];
            }
        }
        $row++;
    }

    // check consistency
    for ($r = $row; $r < $n; $r++) {
        $allzero = true;
        for ($c = 0; $c < $m; $c++) if ($A[$r][$c] === 1) { $allzero = false; break; }
        if ($allzero && $b[$r] === 1) {
            // no solution
            return INF;
        }
    }

    // build solution with all free variables = 0
    $x_part = array_fill(0, $m, 0);
    // back-substitution
    for ($col = $m - 1; $col >= 0; $col--) {
        if ($pivot_of_col[$col] === -1) continue;
        $r = $pivot_of_col[$col];
        $sum = 0;
        for ($j = $col + 1; $j < $m; $j++) if ($A[$r][$j] === 1 && $x_part[$j] === 1) $sum ^= 1;
        $x_part[$col] = ($b[$r] ^ $sum) & 1;
    }

    // build nullspace basis vectors
    $free_vars = array();
    for ($j = 0; $j < $m; $j++) if ($pivot_of_col[$j] === -1) $free_vars[] = $j;
    $k = count($free_vars);
    $basis = array(); // each basis vector is array of length m
    for ($fi = 0; $fi < $k; $fi++) {
        $fv = $free_vars[$fi];
        $vec = array_fill(0, $m, 0);
        $vec[$fv] = 1;
        // compute pivot entries to satisfy A * vec = 0
        for ($col = $m - 1; $col >= 0; $col--) {
            if ($pivot_of_col[$col] === -1) continue;
            $r = $pivot_of_col[$col];
            $sum = 0;
            for ($j = $col + 1; $j < $m; $j++) if ($A[$r][$j] === 1 && $vec[$j] === 1) $sum ^= 1;
            $vec[$col] = $sum & 1; // because right-hand zero
        }
        $basis[] = $vec;
    }

    // ff nullspace small, brute-force all 2^k
    $best = INF;
    if ($k <= 26) {
        $limit = 1 << $k;
        for ($mask = 0; $mask < $limit; $mask++) {
            $x = $x_part;
            // add basis vectors
            for ($bit = 0; $bit < $k; $bit++) {
                if (($mask >> $bit) & 1) {
                    // x = x XOR basis
                    for ($j = 0; $j < $m; $j++) if ($basis[$bit][$j]) $x[$j] ^= 1;
                }
            }
            $w = bitcount_array($x);
            if ($w < $best) $best = $w;
            if ($best === 0) break;
        }
        return $best;
    }

    // if nullspace large we dorandomized search
    $x_best = $x_part;
    $best = bitcount_array($x_best);

    // try flipping
    $tries = 20000;
    for ($t = 0; $t < $tries; $t++) {
        // random mask: pick half free vars randomly
        $x = $x_part;
        // pick between 1 and min random basis vectors to XOR
        $pick = rand(1, min(12, $k));
        for ($p = 0; $p < $pick; $p++) {
            $bidx = rand(0, $k - 1);
            for ($j = 0; $j < $m; $j++) if ($basis[$bidx][$j]) $x[$j] ^= 1;
        }
        // try flipping each basis vector if it reduces weight
        $improved = true;
        while ($improved) {
            $improved = false;
            for ($bi = 0; $bi < $k; $bi++) {
                // try toggling basis
                $newx = $x;
                for ($j = 0; $j < $m; $j++) if ($basis[$bi][$j]) $newx[$j] ^= 1;
                $w = bitcount_array($newx);
                if ($w < bitcount_array($x)) { $x = $newx; $improved = true; }
            }
        }
        $w = bitcount_array($x);
        if ($w < $best) { $best = $w; $x_best = $x; }
        if ($best === 0) break;
    }

    return $best;
}

// process each non-empty line
while (($line = fgets($handle)) !== false) {
    $parsed = parse_line($line);
    if ($parsed === null) continue;
    $pattern = $parsed['pattern'];
    $buttons = $parsed['buttons'];
    $res = solve_machine($pattern, $buttons);
    if (!is_infinite($res)) {
        $total += $res;
    } else {
        fclose($handle);
    }
}

fclose($handle);
echo $total;
