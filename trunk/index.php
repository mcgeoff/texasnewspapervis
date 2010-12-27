<!DOCTYPE html>
<html>
<head>
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

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>

<script type="text/javascript">

/*
 * global data section
 */

var statsByPub  = <?php getStatsByPub(); ?>;
var statsByCity = <?php getStatsByCity(); ?>;
var currCity = "Austin";  // default value
var currYear = "1950";    // default value

var minYear = 1829;
var maxYear = 2008;

var pubTrendByYear = getTrendByYear(statsByPub);

var map;
var markers = [];


/*
 * js method section
 */

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

function addLeftColumnTable() {
    var content = document.getElementById("leftcolumn");
    var tbl = document.createElement("table");
    tbl.id = "infoTable";
    content.appendChild(tbl);
}

function updateCurrCityFromForm() {
    currCity = document.form_city.city.value;
    onCurrCity();
}

function updateCurrCityFromMap(city) {
    currCity = city;
    onCurrCity();
}

function onCurrCity() {
    var tbl = document.getElementById("infoTable");

    while (tbl.rows.length>0) {
        tbl.deleteRow(0);
    }

    for (var k in pubTrendByYear) {
        if (pubTrendByYear[k]["city"] != currCity) {
            continue;
        }
        var tmpRow = tbl.insertRow();

        tmpRow.insertCell(0).innerHTML = pubTrendByYear[k]["pub"];
        tmpRow.insertCell(1).innerHTML = pubTrendByYear[k]["city"];

        // TODO
        tmpRow.insertCell(2).innerHTML =
            "<script type=\"text\/javascript\">sparkline(walk(100), 1);<\/script>";
        //tmpRow.insertCell(2).innerHTML = pubTrendByYear[k]["goodPercent"].toString();
    }
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

        addInfowindowToMarker(marker, markerLoc[i]);

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

function addInfowindowToMarker(marker, markerContent) {
    var infowindow = new google.maps.InfoWindow({
        content: infowindowMessage(markerContent),
        maxWidth: 70,
    });

    google.maps.event.addListener(marker, "mouseover", function() {
        infowindow.open(map, marker);
        updateCurrCityFromMap(markerContent["city"]);
    });

    google.maps.event.addListener(marker, "mouseout", function() {
        infowindow.close();
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

/**
 *  convert data from
 *      pub, city, year, ...
 *  to
 *      pub, city, trend[year], ...
 */
function getTrendByYear(statsByPub) {
    var result = {};

    for (var i = 0; i < statsByPub.length; i++) {
        var pubName = statsByPub[i]["pub"];
        if (result[pubName] == null) {
            result[pubName] = {};
            result[pubName]["pub"] = statsByPub[i]["pub"];
            result[pubName]["city"] = statsByPub[i]["city"];
            result[pubName]["lat"] = statsByPub[i]["lat"];
            result[pubName]["lng"] = statsByPub[i]["lng"];
            result[pubName]["goodPercent"] = new Array();

            result[pubName]["goodPercent"][maxYear-minYear] = null;  // force to length
        }

        var yearOffset = statsByPub[i]["year"] - minYear;

        // record mGood / mTotal to the year
        result[pubName]["goodPercent"][yearOffset] =
            parseFloat(statsByPub[i]["mGood"]) /
            parseFloat(statsByPub[i]["mTotal"]);
    }

    return result;
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
    <input type="button" value="set city" onclick="updateCurrCityFromForm();">
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

  <div><script type="text/javascript+protovis">sparkline(walk(100), 1);</script></div>

</body>
</html>


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

