
<script type="text/javascript" src="./protovis-r3.2.js" />
<script type="text/javascript+protovis">

/** A simple sparkline with optional dots. */
function sparkline(data, dots) {
    var n = data.length,
        w = n,
        h = 10,
        min = pv.min.index(data),
        max = pv.max.index(data);
    var vis = new pv.Panel()
        .width(w)
        .height(h)
        .margin(2);
    vis.add(pv.Line)
        .data(data)
        .left(pv.Scale.linear(0, n - 1).range(0, w).by(pv.index))
        .bottom(pv.Scale.linear(data).range(0, h))
        .strokeStyle("#000")
        .lineWidth(1)
        .add(pv.Dot)
        .visible(function() (dots && this.index == 0) || this.index == n - 1)
        .strokeStyle(null)
        .fillStyle("brown")
        .radius(2)
        .add(pv.Dot)
        .visible(function() dots && (this.index == min || this.index == max))
        .fillStyle("steelblue");
    vis.render();
}

/** Generates a random walk of length n. */
function walk(n) {
    var array = [], value = 0, i = 0;
    while (n-- > 0) {
        array.push(value += (Math.random() - .5));
    }
    return array;
}

</script>

<?php

include("control.php");

function clampString($s, $len) {
    $result = $s;

    if (strlen($s) > $len) {
        $result = substr($s, 0, $len-3) . "...";
    }

    return $result;
}

/**
 *  Convert a map with int key to a continuous array
 *  with $begin and $end as the range of index
 *  e.g.:
 *      array( 1933 => 100, 1935 => 200 )
 *          with $begin = 1930, $end = 1939
 *  will be converted to
 *      array( 0, 0, 0, 100, 0, 200, 0, 0, 0, 0)
 */
function intMap2Array($intMap, $begin, $end) {
    $result = array(0);
    $result = array_pad($result, $end - $begin + 1, 0);
    foreach (array_keys($intMap) as $idx) {
        $result[$idx - $begin] = $intMap[$idx];
    }
    return $result;
}

function percentGoodByYear($pubStats) {
    $result = array();
    foreach (array_keys($pubStats) as $k) {
        $year = (int)$k;
        $result[$year] = (float)$pubStats[$k]["Good"] / (float)$pubStats[$k]["Total"];
    }
    return $result;
}

function view_pub_in_city() {
    $currCity = $_SESSION["currentCity"];

    $pubInCity = array();
    $pubs = getPubInCity($currCity);
    foreach ($pubs as $row) {
        $pub   = $row["pub"];
        $year  = $row["year"];
        $good  = $row["mGood"];
        $total = $row["mTotal"];

        $pubInCity[$pub][$year] =
            array("Good" => $good, "Total" => $total);
    }

    echo "<table>";
    foreach (array_keys($pubInCity) as $pub) {
        $lineData = json_encode(intMap2Array(percentGoodByYear($pubInCity[$pub]), 1820, 2010));

        echo "<tr>";
        echo "<td>";
        echo clampString($pub, 30);
        echo "</td>";
        echo "<td>";
        echo '
        <script type="text/javascript+protovis">
            var a = '.$lineData.';
            sparkline(a, 1);
        </script>';
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

?>

