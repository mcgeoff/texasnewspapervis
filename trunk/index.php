<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Texas Newspaper Collection</title>

<style type="text/css">
  #leftcolumn {
      width: 40%;
      float: left;
  }
  #map_canvas {
      width: 50%;
      height: 500px;
      float: right;
  }
  #rightcolumn {
      width: 10%;
      float: right;
  }
</style>

<script type="text/javascript" src="./protovis-r3.2.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="http://yui.yahooapis.com/3.2.0/build/yui/yui-min.js"></script>

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

// Create a YUI instance and request the slider module and its dependencies
YUI().use("slider", function (Y) {
    // horizontal Slider
    var xSlider = new Y.Slider({
        min : 1829,
        max : 2008,
        value : 1950,
        length : '400px' });

    xSlider.after( "valueChange", updateCurrYearFromSlider );
    xSlider.render('#horiz_slider'); 
});

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

function updateCurrYearFromSlider(e) {
    currYear = e.newVal;
    onCurrYear();
}

function onCurrYear() {
    // step 1 update current year display
    var elem = document.getElementById("currYear");
    elem.innerHTML = "Current year is " + "<b>" + currYear + "</b>";
}

function updateCurrCityFromMap(city) {
    currCity = city;
    onCurrCity();
}

function onCurrCity() {
    // step 1 update current city display
    var elem = document.getElementById("currCity");
    elem.innerHTML = "Current city is " + "<b>" + currCity + "</b>";

    // step 2 update info table in left column
    var tbl = document.getElementById("infoTable");
    while (tbl.rows.length>0) {
        tbl.deleteRow(0);
    }

    for (var k in pubTrendByYear) {
        if (pubTrendByYear[k]["city"] != currCity) {
            continue;
        }
        var tmpRow = tbl.insertRow();

        var cell0 = tmpRow.insertCell(0)
        cell0.innerHTML = pubTrendByYear[k]["pub"];
        cell0.width = "50%";

        // TODO
        var a = pubTrendByYear[k]["goodPercent"];
        var part1 = 
            '<span style="display: inline-block; ">' +
            '<svg width="300" height="20" fill="none">' +
            '<g>';
        var part2 = '<path stroke-width="1" stroke="blue" d="M0,20 ';
        for (var i = 0; i < a.length; i++) {
            if (isNaN(a[i])) {
                a[i] = 0;
            }
            part2 = part2 + 'L' + i + ',' + (20 - 20 * a[i]) + ' ';
        }
        part2 = part2 + '"/>';
        var part3 =
            '</g>' +
            '</svg>' +
            '</span>';
        var cell1 = tmpRow.insertCell(1);
        cell1.innerHTML = part1 + part2 + part3;
        cell1.width = "50%";
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

  <div id="yahoo-com" class="yui3-skin-sam  yui-skin-sam">
      <p> 1829 <span id="horiz_slider"></span> 2008 </p>
  </div>

  <div><p id="currYear">Current year is</p></div>
  <div><p id="currCity">Current city is</p></div>

  <!-- left column -->
  <div id="leftcolumn"></div>

  <!-- right column -->
  <div id="rightcolumn">
    <div><a href="map_count.html">Map of Count By City</a></div>
    <div><a href="city_year.html">Plots of Count By City</a></div>
    <div id="debug"></div>
  </div>

  <!-- canvas for map -->
  <div id="map_canvas"></div>

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

