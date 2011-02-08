<!DOCTYPE html>
<?php include("backend.php"); ?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Texas Newspaper Collection</title>


<!-- Dependencies --> 
<script type="text/javascript" src="./config.js"></script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.min.js"></script>
<script type="text/javascript" src="http://api.simile-widgets.org/timeline/2.3.1/timeline-api.js"></script> 
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/jquery-ui.min.js"></script>
<link rel="stylesheet" type="text/css" href="http://www.simile-widgets.org/timeline/examples/styles.css"/> 
<link type="text/css" rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/themes/base/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="timeline_style.css"/> 
<link rel="stylesheet" type="text/css" href="style.css"/>
 

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

// global state variables wrapped together
var currentState = {
    city: config.defaultCity,
    state: config.defaultState,
    yearRange: {
        min: minYear,   // From, inclusive
        max: maxYear,   // To, inclusive
    },
    colorRange: {
        min: 0,                             // From, inclusive
        max: colorRampThreshold.length - 1, // To, inclusive
    },
};

// global widgets and structures
var map = null;
var markers = [];
var timeline = null;
var simile_timeline;    // for simile timeline
var simile_resizeTimerID = null;

var pubTrendByYear = getTrendByYear(statsByPub, minYear, maxYear);

/*
 * js method section
 */

// include google visualization widgets
google.load('visualization', '1', {'packages':['annotatedtimeline', 'corechart']});

$(document).ready(function () {
    initTitleBlock();
    drawLegend();
    initMap();
    initTimeline();
    initSimileTimeline();
});

function initTitleBlock() {
    // read contents from config.js
    // add generate title block accordingly
    var title_div = $("#title_block");

    title_div.append($('<h1>' + config.title + '</h1>'));
    title_div.append($('<h3>' + config.subTitle + '</h3>'));
    title_div.append($('<p>' + config.introText + '</p>'))
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

    addContour(map);

    updateCurrCity(currentState.city);
    updateMarkers(statsByCity);
}

function onRangechange() {
    currentState.yearRange.min = timeline.getVisibleChartRange().start.getFullYear();
    currentState.yearRange.max = timeline.getVisibleChartRange().end.getFullYear();

    updateCityInfo();
    updateMarkers(statsByCity);
}

function yearInRange(year) {
    var inRange = false;
    if (parseInt(year) >= parseInt(currentState.yearRange.min) &&
        parseInt(year) <= parseInt(currentState.yearRange.max)) {
        inRange = true;
    }
    return inRange;
}

function updateCurrCity(city) {
    // record newly updated city
    currentState.city = city;

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
        if (pubTrendByYear[k]['city'] != currentState.city) {
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

    updateCityInfo();
}

// update city info in right column
function updateCityInfo() {
    var city_info = document.getElementById("city_info");
    var stats = null;
    for (var i = 0; i < statsByCity.length; i++) {
        if (statsByCity[i]["city"] == currentState.city &&
            yearInRange(statsByCity[i]["year"])) {
            stats = statsByCity[i];
        }
    }
    if (stats != null) {
        city_info.innerHTML =
            currentState.city + ", " + currentState.state + ", " +
            currentState.yearRange.min + " - " +
            currentState.yearRange.max + "</br>" +
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
        var good = parseFloat(data[i]["good"]);
        var total = parseFloat(data[i]["total"]);
        var goodPercent = good / total;

        // keep only markers with color in range
        if (goodPercent < colorRampThreshold[currentState.colorRange.min] ||
            goodPercent > colorRampThreshold[currentState.colorRange.max] + 0.1) {
            continue;
        }

        var loc = new google.maps.LatLng(
            parseFloat(data[i]["lat"]),
            parseFloat(data[i]["lng"]));

        var bin = 0;
        for (; bin < colorRamp.length; bin++) {
            if (goodPercent <= colorRampThreshold[bin]) {
                break;
            }
        }
        var color = colorRamp[bin];

        var strokeColor = color;
        var strokeOpacity = 0;
        if (currentState.city == data[i]["city"]) {
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
            
        $( "#legend_slider").slider({
            orientation: "horizontal",
            max: colorRamp.length - 1,
            range: true,
            values: [0, colorRamp.length - 1],
            step: 1,
            change: onColorRangeChange,
        });

        onColorRangeChange();
    }
    else {
        alert('need better browser');
    }
}

function onColorRangeChange() {
    // record updated color range
    var range = $( "#legend_slider" ).slider("values");
    currentState.colorRange.min = range[0];
    currentState.colorRange.max = range[1];

    // update display
    var percentageMin = colorRampThreshold[currentState.colorRange.min];
    var percentageMax = colorRampThreshold[currentState.colorRange.max] + 0.1;
    percentageMin = (percentageMin * 100).toPrecision(3) + '%';
    percentageMax = (percentageMax * 100).toPrecision(3) + '%';
    $("#color_range_left").html(percentageMin);
    $("#color_range_right").html(percentageMax);

    // update markers, keeping only those with color in range
    updateMarkers(statsByCity);
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

function addContour(map) {
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

function initSimileTimeline() {
    var eventSource = new Timeline.DefaultEventSource(0);

    var theme = Timeline.ClassicTheme.create();
    theme.event.bubble.width = 420;
    theme.event.bubble.height = 120;
    theme.event.instant.icon = "dull-brown-circle.png";
    var d = Timeline.DateTime.parseGregorianDateTime("1870")
    var bandInfos = [
        Timeline.createBandInfo({
            width:          "10%", 
            intervalUnit:   Timeline.DateTime.DECADE, 
            intervalPixels: 200,
            date:           d,
            showEventText:  false,
            theme:          theme
        }),
        Timeline.createBandInfo({
            width:          "90%", 
            intervalUnit:   Timeline.DateTime.DECADE, 
            intervalPixels: 200,
            eventSource:    eventSource,
            date:           d,
            theme:          theme
        })
    ];

    bandInfos[0].syncWith = 1;
    bandInfos[0].highlight = false;

    simile_timeline = Timeline.create(document.getElementById("simile_timeline"), bandInfos, Timeline.HORIZONTAL);
    simile_timeline.loadXML("timeline_data.xml", function(xml, url) {
        eventSource.loadXML(xml, url);
    });
}
function themeSwitch(){
    var timeline = document.getElementById('simile_timeline');
    timeline.className = (timeline.className.indexOf('dark-theme') != -1) ?
                         timeline.className.replace('dark-theme', '') :
                         timeline.className += ' dark-theme';
}
function onResize() {
    if (simile_resizeTimerID == null) {
        simile_resizeTimerID = window.setTimeout(function() {
            simile_resizeTimerID = null;
            simile_timeline.layout();
        }, 500);
    }
}
function setDate(date) {
	 simile_timeline.getBand(0).setCenterVisibleDate(new Date(date, 0, 1));
}
function getCenter() {
	 alert(simile_timeline.getBand(0).getCenterVisibleDate());
}


</script>

</head>

<body onresize="onResize();"> 
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
            <canvas id="legend_color_canvas" height="30"></canvas>
            <div id="legend_slider"></div>
        </div>
        <br/> <br/> <br/>
        <div>
          Showing publications with correct percentage bwtween
          <span id="color_range_left"></span>
          and
          <span id="color_range_right"></span>
          .
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

  <!-- SIMILE timeline -->
  <div>
    <div id="simile_timeline" class="timeline-default" style="height: 400px;"></div>
    <button onClick="setDate('1890');">set date to 1890</button>
    <button onClick="getCenter()">get currently viewed date</button>
    <button onclick="themeSwitch();">Switch theme</button> 
    <script type="text/javascript">
        var timeline = document.getElementById('simile_timeline');
        timeline.className += ' dark-theme';
    </script>
  <div>

</body>
</html>

