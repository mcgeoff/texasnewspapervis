<!DOCTYPE html>
<html>
<head>

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

function getStatsByPub() {
    try {
        $db = dbConnect();
        $db->beginTransaction();

        $query = 'select pub, year, mGood, mTotal, location.city,
                  longitude as lng, latitude as lat
                  from pub_by_year, location
                  where pub_by_year.city=location.city';
        $result = $db->query($query)->fetchAll();

        $db->commit();
        $db = null;

        echo json_encode($result);
    }
    catch (PDOException $e) {
        $e->getMessage();
        exit();
    }
}

function getStatsByCity() {
    try {
        $db = dbConnect();
        $db->beginTransaction();

        $query = 'select city_by_year.city, year, mGood, mTotal,
                  longitude as lng, latitude as lat
                  from city_by_year, location
                  where city_by_year.city=location.city';
        $result = $db->query($query)->fetchAll();

        $db->commit();
        $db = null;

        echo json_encode($result);
    }
    catch (PDOException $e) {
        $e->getMessage();
        exit();
    }
}

?>

<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>

<title>Texas Newspaper Collection</title>

<style type="text/css">
  #leftcolumn {
      width: 35%;
      float: left;
  }
  #map_canvas {
      width: 50%;
      height: 500px;
      float: left;
  }
  #rightcolumn {
      width: 15%;
      float: left;
  }
</style>

<script type="text/javascript" src="./protovis-r3.2.js"></script>
<script type="text/javascript" src="./sparkline.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">

// global data section
var statsByPub  = <?php getStatsByPub(); ?>;
var statsByCity = <?php getStatsByCity(); ?>;
var currCity = "Austin";  // default value
var currYear = "1950";    // default value
var map;
var markers = [];


// js method section

function addLeftColumnTable() {
    var content = document.getElementById("leftcolumn");
    var tbl = document.createElement("table");
    tbl.id = "infoTable";
    content.appendChild(tbl);
}

function updateCurrCity() {
    currCity = document.form_city.city.value;

    var tbl = document.getElementById("infoTable");

    while (tbl.rows.length>0) {
        tbl.deleteRow(0);
    }

    for (var i = 0; i < statsByPub.length; i++) {
        if (statsByPub[i]["city"] != currCity) {
            continue;
        }
        var tmpRow = tbl.insertRow();

        tmpRow.insertCell(0).innerHTML = statsByPub[i]["city"];
        tmpRow.insertCell(1).innerHTML = statsByPub[i]["mGood"];
        tmpRow.insertCell(2).innerHTML = statsByPub[i]["mTotal"];
        tmpRow.insertCell(3).innerHTML = statsByPub[i]["year"];
    }
}

  function initialize() {
    var myLatlng = new google.maps.LatLng(32.20, -99.00);
    var myOptions = {
      zoom: 6,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    addMarkers(statsByCity);
    showMarkers(currYear);

    addLeftColumnTable();
  }

  function addMarkers(markerLoc) {
      for (i in markerLoc) {
          var loc = new google.maps.LatLng(
              parseFloat(markerLoc[i]["lat"]),
              parseFloat(markerLoc[i]["lng"]));

          marker = new google.maps.Marker({
              position: loc,
              map: map,
          });

          var infowindowContent = infowindowMessage(markerLoc[i]);

          addInfowindowToMarker(marker, infowindowContent);

          markers.push(marker);
      }
  }

  function infowindowMessage(markerContent) {
      return "".concat(
          "In year ", markerContent["year"],
          ", ", markerContent["city"],
          " has ", markerContent["mGood"], " good characters",
          " out of ", markerContent["mTotal"], " in total."
          );
  }

  function addInfowindowToMarker(marker, message) {
      var infowindow = new google.maps.InfoWindow({
          content: message,
      });

      google.maps.event.addListener(marker, "click", function() {
          infowindow.open(map, marker);
      });
  }

  function showMarkers(year) {
      if (markers) {
          for (i in markers) {
              markers[i].setMap(map);
              /*
              markers[i].setMap(null);
              if (year == markers) {
                  markers[i].setMap(map);
              }
              */
          }
      }
  }

  function debug(msg) {
      document.getElementById('debug').appendChild(
          document.createTextNode(msg.toString()));
  }
</script>
</head>

<body onload="initialize()">

  <!-- Title Bar -->
  <h1>Texas Newspaper Collection</h1>

  <!-- search bar -->
  <form name="form_year">
    <input type="text" name="year">
    <input type="button" value="set year" onclick='alert("TODO");'>
  </form>

  <form name="form_city">
    <input type="text" name="city">
    <input type="button" value="set city" onclick="updateCurrCity();">
  </form>

  <!-- left column -->
  <div id="leftcolumn"></div>

  <!-- canvas for map -->
  <div id="map_canvas"></div>

  <!-- right column -->
  <div id="rightcolumn">
    <div><a href="map_count.html">Map of Count By City</a></div>
    <div><a href="city_year.html">Plots of Count By City</a></div>
  </div>

  <div id="debug"></div>

</body>
</html>

