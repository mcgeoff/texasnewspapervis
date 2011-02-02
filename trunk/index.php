<!DOCTYPE html>
<?php include("backend.php"); ?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Texas Newspaper Collection</title>

<link rel="stylesheet" type="text/css" href="style.css" />

<!-- Dependencies --> 
<script type="text/javascript" src="./config.js"></script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>


<script type="text/javascript">

/*
 * global data section
 */
var statsByPub  = <?php getStatsByPub(); ?>;
var statsByCity = <?php getStatsByCity(); ?>;
var statsByYear = <?php getStatsByYear(); ?>;

var minYear = config.yearRange.min;
var maxYear = config.yearRange.max;

var colorRamp = config.colorRamp;
var colorRampThreshold = config.colorRampThreshold;

// global state variables, TODO to be wrapped together
var currCity = config.defaultCity;
var currState = config.defaultState;
var rangeMinYear = minYear;
var rangeMaxYear = maxYear;

// global widgets and structures
var map = null;
var markers = [];
var timeline = null;

var pubTrendByYear = getTrendByYear(statsByPub, minYear, maxYear);


/*
 * js method section
 */

// include google visualization widgets
google.load('visualization', '1', {'packages':['annotatedtimeline', 'corechart']});
google.setOnLoadCallback(initialize);

function initialize() {
    initTitleBlock();
    drawLegend();
    initMap();
    initTimeline();
}

function initTitleBlock() {
    // read contents from config.js
    // add generate title block accordingly

    var title_div = document.getElementById('title_block');

    var title = document.createElement('h1');
    title.innerHTML = config.title;
    title_div.appendChild(title);

    var subTitle = document.createElement('h3');
    subTitle.innerHTML = config.subTitle;
    title_div.appendChild(subTitle);

    var introText = document.createElement('p');
    introText.innerHTML = config.introText;
    title_div.appendChild(introText);
}

function initTimeline() {
    var data = new google.visualization.DataTable();
    data.addColumn('date', 'Date');
    data.addColumn('number', 'Total Words Scanned');
    data.addColumn('string', 'title1');
    data.addColumn('string', 'text1');
    data.addColumn('number', 'Correct Words Scanned');
    data.addColumn('string', 'title2');
    data.addColumn('string', 'text2');

    data.addRows(statsByYear.length);
    for (var i = 0; i < statsByYear.length; i++) {
        var year = parseInt(statsByYear[i]["year"]);
        var good = parseInt(statsByYear[i]["good"]);
        var total = parseInt(statsByYear[i]["total"]);
        data.setValue(i, 0, new Date(year, 1, 1));
        data.setValue(i, 1, total);
        data.setValue(i, 4, good);
    }

    timeline = new google.visualization.AnnotatedTimeLine(
        document.getElementById('timeline_vis'));
    timeline.draw(data, {'displayAnnotations': true});

    google.visualization.events.addListener(
        timeline,
        'rangechange',
        onRangechange);
}

function initMap() {
    var myLatlng = new google.maps.LatLng(
        config.map.center.lat,
        config.map.center.lng);
    var myOptions = {
      zoom: config.map.initialZoom,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.TERRAIN,
    };

    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

    updateMarkers(statsByCity);

    addPolygon(map);

    updateCurrCity(currCity);
}

function onRangechange() {
    rangeMinYear = timeline.getVisibleChartRange().start.getFullYear();
    rangeMaxYear = timeline.getVisibleChartRange().end.getFullYear();

    // TODO update city display

    updateMarkers(statsByCity);
}

function yearInRange(year) {
    var inRange = false;
    if (parseInt(year) >= parseInt(rangeMinYear) &&
        parseInt(year) <= parseInt(rangeMaxYear)) {
        inRange = true;
    }
    return inRange;
}

function updateCurrCity(city) {
    // record newly updated city
    currCity = city;

    // remove previous sparklines
    var pub_chart = document.getElementById('pub_chart');
    while (pub_chart.childNodes.length > 0) {
        pub_chart.removeChild(pub_chart.firstChild);
    }

    // add sparklines for publications
    var bgColors = ['#ffffff', '#eeeeee'];
    var idx = 0;
    var numYears = maxYear - minYear + 1; 
    for (var k in pubTrendByYear) {
        if (pubTrendByYear[k]['city'] != currCity) {
            continue;
        }

        // prepare data
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Year');
        data.addColumn('number', '%Good');
        data.addRows(numYears);

        var goodPercent = pubTrendByYear[k]["goodPercent"];
        for (var i = 0; i < numYears; i++) {
            if (isNaN(goodPercent[i])) {
                goodPercent[i] = 0;
            }
            var strYear = "" + (i + minYear);
            data.setValue(i, 0, strYear);
            data.setValue(i, 1, goodPercent[i]);
        }

        // add DIV element for pub title
        var title_div = document.createElement('div');
        title_div.innerHTML =
            '<a href="http://west.stanford.edu">' +
            pubTrendByYear[k]['pub'] +
            '</a>';
        pub_chart.appendChild(title_div);

        // add new DIV element for chart
        var chart_div = document.createElement('div');
        chart_div.id = 'pub_chart_div' + k;
        pub_chart.appendChild(chart_div);

        // draw chart
        var chart = new google.visualization.LineChart(chart_div);
        var bgColor = bgColors[idx % 2]; // specify background color
        idx++;
        chart.draw(data, {
            backgroundColor: bgColor,
            gridlineColor: bgColor,
            chartArea: {
                left: 20,
                top: 0,
                width: '80%',
                height: 15,
            },
            height: 30,
            lineWidth: 1,
            colors: [colorRamp[Math.floor(colorRamp.length / 2)]],
            hAxis: {
                textPosition: 'out',
                showTextEvery: 59,
            },
            vAxis: {
                textPosition: 'none',
            },
        });
    }

    // update city info in right column
    // TODO the choice of year is not quite meaningful right now
    var city_info = document.getElementById("city_info");
    var stats = null;
    for (var i = 0; i < statsByCity.length; i++) {
        if (statsByCity[i]["city"] == currCity &&
            yearInRange(statsByCity[i]["year"])) {
            stats = statsByCity[i];
        }
    }
    if (stats != null) {
        city_info.innerHTML =
            currCity + ", " + currState + ", " +
            rangeMinYear + " - " + rangeMaxYear + "</br>" +
            "Good Characters Scanned: " + stats["mGood"] + "<br/>" +
            "Total Characters Scanned: " + stats["mTotal"] + "<br/>";
    }
}

function updateMarkers(statsByCity) {
    // clean up previous markers
    while (markers.length > 0) {
        var tmp = markers.pop();
        tmp.setMap(null);
    }

    // compute data by city, for all years in range
    var data = [];
    for (i in statsByCity) {
        if (!yearInRange(statsByCity[i]["year"])) {
            continue;
        }

        var city = statsByCity[i]["city"];
        var lat = statsByCity[i]["lat"];
        var lng = statsByCity[i]["lng"];

        if (!(city in data)) {
            data[city] = [];
            data[city]["city"]  = city;
            data[city]["lat"]   = lat;
            data[city]["lng"]   = lng;
            data[city]["good"]  = 0;
            data[city]["total"] = 0;
        }

        data[city]["good"] += parseInt(statsByCity[i]["mGood"]);
        data[city]["total"] += parseInt(statsByCity[i]["mTotal"]);
    }

    // add new markers
    for (i in data) {
        var loc = new google.maps.LatLng(
            parseFloat(data[i]["lat"]),
            parseFloat(data[i]["lng"]));

        var good = parseFloat(data[i]["good"]);
        var total = parseFloat(data[i]["total"]);
        var goodPercent = good / total;

        var bin = 0;
        for (; bin < colorRamp.length; bin++) {
            if (goodPercent <= colorRampThreshold[bin]) {
                break;
            }
        }
        var color = colorRamp[bin];

        var strokeColor = color;
        var strokeOpacity = 0;
        if (currCity == data[i]["city"]) {
            strokeColor = '#ffff00';
            strokeOpacity = 1;
        }

        var radius = Math.log(total) * 2000;

        marker = new google.maps.Circle({
            center: loc,
            map: map,
            city: data[i]["city"],
            radius: radius,
            fillColor: color,
            fillOpacity: 0.8,
            strokeColor: strokeColor,
            strokeOpacity: strokeOpacity,
            strokeWeight: 4,
        });

        addMarkerListener(marker);
        markers.push(marker);
    }
}

function addMarkerListener(marker) {
    google.maps.event.addListener(marker, "click", function() {
        updateCurrCity(marker.city);
        updateMarkers(statsByCity);
    });
}

/**
 *  convert data from
 *      pub, city, year, ...
 *  to
 *      pub, city, trend[year], ...
 */
function getTrendByYear(statsByPub, minYear, maxYear) {
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

function drawLegend() {
    drawColorLegend();
    drawSizeLegend();
}

function drawColorLegend() {
    var canvas = document.getElementById('legend_color_canvas');
    if (canvas.getContext) {
        var ctx = canvas.getContext('2d');

        var x = 0;
        var y = 0;
        var s = Math.round(canvas.width / colorRamp.length) - 1;
        for (var i = 0; i < colorRamp.length; i++) {
            ctx.fillStyle = colorRamp[i];
            ctx.fillRect(x + i * s, y, s, s);
        }
    }
    else {
        alert('need better browser');
    }

    document.getElementById('legend_color_left').innerHTML =
        colorRampThreshold[0];
    document.getElementById('legend_color_middle').innerHTML =
        colorRampThreshold[Math.round(colorRampThreshold.length / 2)];
    document.getElementById('legend_color_right').innerHTML =
        colorRampThreshold[colorRampThreshold.length - 1];
}

function drawSizeLegend() {
    var canvas = document.getElementById('legend_size_canvas');
    if (canvas.getContext) {
        var r = Math.min(canvas.width, canvas.height) / 2;

        var ctx = canvas.getContext('2d');
        ctx.beginPath();
        ctx.arc(r, r, r, 0, 2 * Math.PI, true);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(r, 2*r-0.3*r, 0.3*r, 0, 2 * Math.PI, true);
        ctx.stroke();
    }
}

function addPolygon(map) {
    var c = [];
    for (var i = 0; i < config.contour.length; i++) {
        c.push(new google.maps.LatLng(config.contour[i][0], config.contour[i][1]));
    }

    new google.maps.Polygon({
        paths: c,
        strokeColor: "#666666",
        strokeOpacity: 0.6,
        strokeWeight: 4,
        fillColor: "#000000",
        fillOpacity: 0,
    }).setMap(map);
}

</script>

</head>

<body>

  <!-- Title Bar -->
  <div class="wrapper">
      <div id="title_block">
      </div>
      <!-- timeline control -->
      <div id="timeline_vis"> </div>
  </div>

  <div class="wrapper">
    <!-- left column -->
    <div id="leftcolumn">
      <div id="city_info"></div>
      <br/>
      <div id="pub_chart"></div>
    </div>

    <!-- center column -->
    <div id="centercolumn">
      <!-- canvas for map -->
      <div id="map_canvas"></div>
    </div>

    <!-- right column -->
    <div id="rightcolumn">
      <!-- color legend -->
      <div width="200">
        <div>
        <canvas id="legend_color_canvas" width="100" height="20"></canvas>
        </div>
        <div>
        <div id="legend_color_left" style="float:left"></div>
        <div id="legend_color_middle" style="float:left"></div>
        <div id="legend_color_right" style="float:left"></div>
        </div>
      </div>

      <br/> <br/>

      <!-- size legend -->
      <div>
        <div style="float:left">
          <canvas id="legend_size_canvas" width="50" height="50"></canvas>
        </div>
        <div style="float:left">
          <div id="legend_size_content">
          numbers here
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>

