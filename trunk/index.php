<!DOCTYPE html>
<html>
<head>

<?php
session_start();

function getSessionValue($key) {
    if (isset($_GET[$key])) {
        $_SESSION[$key] = $_GET[$key];
    }
    return $_SESSION[$key];
}
echo "Current Year = ".getSessionValue("currentYear")."<br/>";
echo "Current City = ".getSessionValue("currentCity")."<br/>";
?>

<?php

function dbConnect() {
    $dbname = "./newspaper.db";
    try {
        $db = new PDO("sqlite:" . $dbname);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $e->getMessage();
        exit();
    }
    return $db;
}

function getPubInCity($cityName) {
    try {
        $db = dbConnect();
        $db->beginTransaction();

        $query = "select * from pub_by_year where city=\"".
                 $cityName ."\"";
        $result = $db->query($query)->fetchAll();

        $db->commit();
        $db = null;

        return $result;
    }
    catch (PDOException $e) {
        $e->getMessage();
        exit();
    }
}

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

    //echo "<table>";
    foreach (array_keys($pubInCity) as $pub) {
        $lineData = json_encode(intMap2Array(percentGoodByYear($pubInCity[$pub]), 1820, 2010));
        echo "<p>".clampString($pub, 30)."</p>\n";
        /*
        echo "<tr>"; echo "<td>";
        echo clampString($pub, 30);
        echo "</td>"; echo "<td>";
        echo '
        <script type="text/javascript+protovis">
            inc("sparkline.js");
            var a = '.$lineData.';
            sparkline(a, 1);
        </script>';
        echo "</td>"; echo "</tr>";
        */
    }
    //echo "</table>";
}


function getPubByYear($year) {
    try {
        $db = dbConnect();
        $db->beginTransaction();

        $query = "select * from city_by_year, location where city_by_year.city=location.city and year=\"".
                 $year ."\"";
        $result = $db->query($query)->fetchAll();

        $db->commit();
        $db = null;

        return $result;
    }
    catch (PDOException $e) {
        $e->getMessage();
        exit();
    }
}


/**
 *  Generate marker variable array
 *  which will be used to draw on the map
 */
function getMarkerArray($year) {
    $pubByYear = getPubByYear($year);
    foreach ($pubByYear as $row) {
        echo json_encode($row).",\n";
    }
}

?>

<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>

<title>Texas Newspaper Collection</title>

<style type="text/css">
  #leftcolumn {
      width: 300px;
      float: left;
  }
  #rightcolumn {
      width: 200px;
      float: left;
  }
  #map_canvas {
      width: 500px;
      height: 500px;
      float: left;
  }
</style>

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
  // global structures
  var map;
  var markerLoc = [
      <?php getMarkerArray(getSessionValue("currentYear")); ?>
      ];
  var markers = [];

  function initialize() {
    var myLatlng = new google.maps.LatLng(30.30, -97.70);
    var myOptions = {
      zoom: 6,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

    addMarkers(markerLoc);

    showMarkers();
  }

  function addMarkers(markerLoc) {
      for (i in markerLoc) {
          var loc = new google.maps.LatLng(
              parseFloat(markerLoc[i]["latitude"]),
              parseFloat(markerLoc[i]["longitude"]));

          marker = new google.maps.Marker({
              position: loc,
              map: map,
              title: "TODO",
          });

          var infowindow = new google.maps.InfoWindow({
              content: markerLoc[i]["city"],
          });

          google.maps.event.addListener(marker, "click", function() {
              infowindow.open(map, marker);
          });

          markers.push(marker);
      }
  }

  function showMarkers() {
      if (markers) {
          for (i in markers) {
              markers[i].setMap(map);
          }
      }
  }

  function hideMarkers() {
      if (markers) {
          for (i in markers) {
              markers[i].setMap(null);
          }
      }
  }

</script>

<!--
<script type="text/javascript" src="./sparkline.js" />
-->

</head>

<body onload="initialize()">

  <h1>Texas Newspaper Collection</h1>

  <!-- search bar -->
  <form method="GET" action="index.php">
  <input type="text" name="currentYear"
         value="<?php echo getSessionValue("currentYear") ?>">
  <input type="text" name="currentCity"
         value="<?php echo getSessionValue("currentCity") ?>">
  <input type="submit" value="Set State"></input>
  </form>

  <!-- left column -->
  <div id="leftcolumn">
    <?php view_pub_in_city(); ?>
  </div>

  <!-- canvas for map -->
  <div id="map_canvas">
    CENTER
  </div>

  <!-- right column -->
  <div id="rightcolumn">
    <a href="map_count.html">Map of Count By City</a>
    <a href="city_year.html">Plots of Count By City</a>
  </div>


</body>
</html>

