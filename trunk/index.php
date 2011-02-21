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
<script type="text/javascript" src="http://vis.stanford.edu/protovis/protovis-r3.2.js"></script>

<link rel="stylesheet" type="text/css" href="http://www.simile-widgets.org/timeline/examples/styles.css"/> 
<link type="text/css" rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/themes/base/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="timeline_style.css"/> 
<link rel="stylesheet" type="text/css" href="style.css"/>
 

<script type="text/javascript">

/*****************************************************************************/
// global data section
/*****************************************************************************/
var statsByPub  = <?php getStatsByPub(); ?>;
var statsByCity = <?php getStatsByCity(); ?>;
var statsByYear = <?php getStatsByYear(); ?>;


var minYear = config.yearRange.min;
var maxYear = config.yearRange.max;

var colorRamp = config.colorRamp;
var colorRampThreshold = config.colorRampThreshold;

// global state variables wrapped together
var currentState = {
    // update city through update function below
    city: config.defaultCity,
    // never updated
    state: config.defaultState,
    // update year range through google timeline
    yearRange: {
        min: minYear,   // From, inclusive
        max: maxYear,   // To, inclusive
    },
    // update color range through color legend
    colorRange: {
        min: 0,                             // From, inclusive
        max: colorRampThreshold.length - 1, // To, inclusive
    },
    // update marker size scale through <select>
    markerSizeScale: 'log',
};

// global widgets and structures
var map = null;
var markers = [];
var timeline = null;
var simile_timeline;    // for simile timeline
var simile_resizeTimerID = null;

var pubTrendByYear = getTrendByYear(statsByPub, minYear, maxYear);

/*****************************************************************************/
// js method section
/*****************************************************************************/
// include google visualization widgets
google.load('visualization', '1', {'packages':['annotatedtimeline', 'corechart']});

$(document).ready(function () {
    drawTitleBlock();
    drawLegend();
    drawMap();
    drawTimeline();
    drawSimileTimeline();
});

/*****************************************************************************/
// functions used to draw parts on the screen
/*****************************************************************************/
function drawTitleBlock() {
    // read contents from config.js
    // add generate title block accordingly
    var title_div = $("#title_block");

    title_div.append($('<div class="content"><h1>' + config.title + '</h1></div>'));
    title_div.append($('<div class="content"><h3>' + config.subTitle + '</h3></div>'));
    title_div.append($('<div class="content"><p>' + config.introText + '</p></div>'))
}

function drawTimeline() {
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
        onYearRangechange);
}

function drawMap() {
    var myLatlng = new google.maps.LatLng(
        config.map.center.lat,
        config.map.center.lng);

    var myOptions = {
      zoom: config.map.initialZoom,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.TERRAIN,
      mapTypeControl: false,
      streetViewControl: false,
      panControlOptions: {
          position: google.maps.ControlPosition.TOP_RIGHT,
      },
      zoomControlOptions: {
          position: google.maps.ControlPosition.TOP_RIGHT,
      },
    };

    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

    drawContour(map);

    updateCity(currentState.city);
    drawMarkers(statsByCity);
}

function drawLegend() {
    drawColorLegend();
    drawSizeLegend();

    $('input[name=scale_select]').change(function () {
        currentState.markerSizeScale = $('input[name=scale_select]:checked').val();
        $('#legend_size').fadeOut('slow', function() {
            onMarkerSizeScaleChange();
        });
        $('#legend_size').fadeIn('slow');
    });
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

function drawSizeLegend() {
    if ($('#legend_size').children().length > 0) {
        $('#legend_size').children().remove();
    }

    var element = $('<div></div>');
    $('#legend_size').append(element);
    var canvasId = 'legend_size_canvas';
    element.append($('<canvas id="' + canvasId + '"></canvas>'));
    var canvas = document.getElementById(canvasId);
    if (canvas.getContext) {
        var ns = 1000000;
        var nl = 100000000;
        var rs = getMarkerSize(ns, currentState.markerSizeScale);
        var rl = getMarkerSize(nl, currentState.markerSizeScale);
        var m = 1;  // margin
        canvas.width  = (rl + m) * 4;
        canvas.height = (rl + m) * 2;

        var ctx = canvas.getContext('2d');
        ctx.beginPath();
        ctx.arc(rl, rl, rl, 0, 2 * Math.PI, true);
        ctx.fillStyle = colorRamp[colorRamp.length - 1];
        ctx.fill();
        ctx.stroke();

        ctx.beginPath();
        ctx.arc(rl, 2 * rl - rs, rs, 0, 2 * Math.PI, true);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(rl, m);
        ctx.lineTo(2.5 * rl, m);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(rl, 2 * rl - 2 * rs);
        ctx.lineTo(2.5 * rl, 2 * rl - 2 * rs);
        ctx.stroke();

        //ctx.fillStyle = '#000000';
        ctx.strokeText(shorterNumber(nl), 2.5 * rl, 8);
        ctx.strokeText(shorterNumber(ns), 2.5 * rl, 2 * rl - 2 * rs + 8);
    }
}

function drawSizeLegendSingle(total) {
    var element = $('<div></div>');
    $('#legend_size').append(element);

    var canvasId = 'legend_size_' + total;
    element.append($('<canvas id="' + canvasId + '"></canvas>'));
    var canvas = document.getElementById(canvasId);
    if (canvas.getContext) {
        var r = getMarkerSize(total, currentState.markerSizeScale);
        canvas.width = (r+2) * 2;
        canvas.height = (r+2) * 2;

        var ctx = canvas.getContext('2d');
        ctx.beginPath();
        ctx.arc(r, r, r, 0, 2 * Math.PI, true);
        ctx.stroke();
    }

    element.append($('<p></p>').html(total));
}

function drawContour(map) {
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

function drawSimileTimeline() {
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

    simile_timeline = Timeline.create(
        document.getElementById("simile_timeline"),
        bandInfos,
        Timeline.HORIZONTAL);
    simile_timeline.loadXML("timeline_data.xml", function(xml, url) {
        eventSource.loadXML(xml, url);
    });
}
   
function drawCityChart() {
    // remove previous charts
    var pub_chart = document.getElementById('pub_chart');
    while (pub_chart.childNodes.length > 0) {
        pub_chart.removeChild(pub_chart.firstChild);
    }

    // preparing data
	var jsonObj = {};
    var numYears = maxYear - minYear + 1; 
    for (var k in pubTrendByYear) {
        if (pubTrendByYear[k]['city'] != currentState.city) {
            continue;
        }
		jsonObj[k] =  new Array();

        var goodPercent = pubTrendByYear[k]["goodPercent"];
        for (var i = 0; i < numYears; i++) {
            if (isNaN(goodPercent[i])) {
                goodPercent[i] = 0;
            }
            var strYear = "" + (i + minYear);
            jsonObj[k].push({year: strYear, percentGood: goodPercent[i]});
        }
    }

    // add new DIV element for chart
    var chart_div = document.createElement('div');
    chart_div.id = 'area';
    $("#pub_char").html("");
    pub_chart.appendChild(chart_div);

        // begin draw of chart with Protovis
		minyear = 1829;
		var dateFormat = pv.Format.date("%y");
		for (newspaper in jsonObj) {
			jsonObj[newspaper].forEach(function(d) {
				var mySplitResult = d.year.toString().split(" ");
				var year = d.year;
				
				if (mySplitResult.length > 1) {
					year = mySplitResult[3]
				} 
				return d.year = dateFormat.parse(year);
			});

		}
		var counter = 0;
		
		var w = 400,
		    h = 170,
		   x = pv.Scale.linear(dateFormat.parse("1700"),dateFormat.parse("2010")).range(0, w),
		    y = pv.Scale.linear(0, 1).range(0, h);
		
		/* The root panel. */
		var vis = new pv.Panel().width(w).height(h).bottom(20).left(20).right(10).top(5).canvas('area');
		
		/* Y-axis and ticks. */
		vis.add(pv.Rule).data(y.ticks(5)).bottom(y).strokeStyle(function (d) { if (d) { return "#eee"; } else { return "#000"; } }).anchor("left").add(pv.Label).text(y.tickFormat);
		
		/* X-axis and ticks. */
		vis.add(pv.Rule).data(x.ticks()).visible(function (d) { return d; }).left(x).bottom(-5).height(5).anchor("bottom").add(pv.Label).text(x.tickFormat);
		
		vis.add(pv.Panel)
    .events("all")
    .event("mousedown", pv.Behavior.pan())
    .event("mousewheel", pv.Behavior.zoom())
    .event("pan", transform)
    .event("zoom", transform);

/** Update the x- and y-scale domains per the new transform. */
function transform() {
  var t = this.transform().invert();
  var mx = x.invert(vis.mouse().x);
  var y = mx.toString().split(" ")[3];
  var timerange  = (parseInt((t.k-1)*5*1000));
  x.domain(dateFormat.parse((1890 + (t.x/10) - timerange).toString()), dateFormat.parse((2000 + (t.x/10) + timerange).toString()));
  vis.render();
}

		for (newspapert in jsonObj) {
			
			//console.log(jsonObj[newspapert]);
			eval("var panel"+ counter + " = vis.add(pv.Panel).def('i', -1);");
		
			eval("panel"+counter+".add(pv.Area).data(jsonObj[newspapert]).bottom(1).left(function (d) { return x(d.year); }).height(function (d) { return y(d.percentGood); }).event('mouseover', function () { panel"+counter+".i(10); this.render(); return panel100.x("+counter+"); }).event('mouseout', function () { 		    panel"+counter+".i(-1); this.render(); return panel100.x(-1);	}).fillStyle(function (d, p) { if (panel"+counter+".i() < 0) { return 'rgba(238, 238, 238, 0.00001)'; } else { return '"+config.pvcolorRamp[counter]+"'; } }).anchor('top').add(pv.Line).strokeStyle(function() { return '" + config.pvcolorRamp[counter]+ "'; }).lineWidth(function (d, p) { if (panel"+counter+".i() < 0) { return 0.5; } else { return 1; }});");
		counter++;
			
			
		}
		
		
		

////////////////////////////////////////////////////////////////////
var selected = 0;
var panel100 = vis.add(pv.Panel).def("x", -1); /* Antibiotic legend. */
panel100.add(pv.Bar)

.data(pv.keys(jsonObj)).right(370).event("mouseover", function (d) {
        panel100.x(this.index);
        this.render();
        //alert(this.index);
   		return eval("panel" + this.index + ".i(10)");
}).event("mouseout", function (d) {
        panel100.x(-1);
        this.render();
        // alert(this.index);
		return eval("panel" + this.index + ".i(-1)");

}).bottom(function () { return (10 + this.index * 18) }).fillStyle(function (d, p) {

    if (panel100.x() == this.index) {
        return config.pvcolorRamp[this.index];
    } else {
        return "rgba(238, 238, 238, 1)";
    }
}).width(20).height(12).anchor("right").add(pv.Label).textMargin(6).textAlign("left");



		vis.render();
}

function drawCityInfo() {
    // update city info in right column
    var stats = null;
    for (var i = 0; i < statsByCity.length; i++) {
        if (statsByCity[i]["city"] == currentState.city &&
            yearInRange(statsByCity[i]["year"])) {
            if (stats == null) {
                stats = {mGood: 0, mTotal: 0};
            }
            stats["mGood"] += parseInt(statsByCity[i]["mGood"]);
            stats["mTotal"] += parseInt(statsByCity[i]["mTotal"]);
        }
    }
    if (stats != null) {
        $('#city_info').hide('slow', function() {
            $('#city_info').html(
                "<span id='cityname'>" + currentState.city + ", " + currentState.state + "</span>, " +
                currentState.yearRange.min + " - " +
                currentState.yearRange.max + "<br/>" +
                "Good Characters Scanned: " + stats["mGood"] + "<br/>" +
                "Total Characters Scanned: " + stats["mTotal"] + "<br/>");
        });
        $('#city_info').show('slow');
    }
}

function drawMarkers(statsByCity) {
    // clean up previous markers
    while (markers.length > 0) {
        markers.pop().setMap(null);
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

        // determine color
        var bin = 0;
        for (; bin < colorRamp.length; bin++) {
            if (goodPercent <= colorRampThreshold[bin]) {
                break;
            }
        }
        var color = colorRamp[bin];

        // determine radius
        var r = getMarkerSize(total, currentState.markerSizeScale);

        // create canvas element as marker
        var canvas = document.createElement('canvas');
        if (canvas.getContext) {
            rs = r - 3;
            canvas.width = r * 2;
            canvas.height = r * 2;

            // draw circle according to size and color from data
            var ctx = canvas.getContext('2d');
            ctx.globalAlpha = 0.5;
            ctx.fillStyle = color;
            ctx.arc(r, r, rs, 0, Math.PI * 2);
            ctx.fill();

            // highlight current city
            if (currentState.city == data[i]["city"]) {
                ctx.strokeStyle="#ffff00";
                ctx.lineWidth = 3;
                ctx.arc(r, r, rs, 0, Math.PI * 2);
                ctx.stroke();
            }

            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 1;
            ctx.strokeText(shorterNumber(total), r - 8, r + 4);
        }

        // create the marker image
        var image = new google.maps.MarkerImage(
            canvas.toDataURL(),  // url to the canvas image
            new google.maps.Size(2*r, 2*r), // size
            new google.maps.Point(0, 0),    // origin
            new google.maps.Point(r, r));   // anchor

        // generate the marker
        marker = new google.maps.Marker({
            position: loc,
            map: map,
            icon: image,
            city: data[i]["city"],
        });

        addMarkerListener(marker);
        markers.push(marker);
    }
}

function drawColorRangeDisplay() {
    var percentageMin = colorRampThreshold[currentState.colorRange.min];
    var percentageMax = colorRampThreshold[currentState.colorRange.max] + 0.1;

    percentageMin = (percentageMin * 100).toPrecision(3) + '%';
    percentageMax = (percentageMax * 100).toPrecision(3) + '%';

    $("#color_range_left").html(percentageMin);
    $("#color_range_right").html(percentageMax);
}

/*****************************************************************************/
// state transition and control functions
/*****************************************************************************/

// the update of year range and color range are done through sliders
// so only here we need explicit update function

function updateCity(city) {
    // record newly updated city
    currentState.city = city;
    onCityChange();
}

function onCityChange() {
    drawCityInfo();
    drawCityChart();
}

function onYearRangechange() {
    currentState.yearRange.min = timeline.getVisibleChartRange().start.getFullYear();
    currentState.yearRange.max = timeline.getVisibleChartRange().end.getFullYear();

    drawCityInfo();
    drawMarkers(statsByCity);
    setSimileCenterYear('' +
        Math.round((currentState.yearRange.min +
                    currentState.yearRange.max) / 2));
}

function onColorRangeChange() {
    // record updated color range
    var range = $( "#legend_slider" ).slider("values");
    currentState.colorRange.min = range[0];
    currentState.colorRange.max = range[1];

    drawColorRangeDisplay();

    // update markers, keeping only those with color in range
    drawMarkers(statsByCity);
}

function onMarkerSizeScaleChange() {
    drawMarkers(statsByCity);
    drawSizeLegend();
}


/****************************************************************************/
// utility functions
/****************************************************************************/
function yearInRange(year) {
    // currently only work on SIMILE timeline
    var inRange = false;
    if (parseInt(year) >= parseInt(currentState.yearRange.min) &&
        parseInt(year) <= parseInt(currentState.yearRange.max)) {
        inRange = true;
    }
    return inRange;
}

function addMarkerListener(marker) {
    google.maps.event.addListener(marker, "click", function() {
        updateCity(marker.city);
        drawMarkers(statsByCity);
    });
}

function onResize() {
    if (simile_resizeTimerID == null) {
        simile_resizeTimerID = window.setTimeout(function() {
            simile_resizeTimerID = null;
            simile_timeline.layout();
        }, 500);
    }
}

function setSimileCenterYear(date) {
    simile_timeline.getBand(0).setCenterVisibleDate(new Date(date, 0, 1));
}

function getSimileCenterYear() {
    alert(simile_timeline.getBand(0).getCenterVisibleDate());
}

function switchSimileTheme(){
    var timeline = document.getElementById('simile_timeline');
    timeline.className = (timeline.className.indexOf('dark-theme') != -1) ?
                         timeline.className.replace('dark-theme', '') :
                         timeline.className += ' dark-theme';
}

function getTrendByYear(statsByPub, minYear, maxYear) {
    //  convert data from   pub, city, year, ...
    //  to                  pub, city, trend[year], ...
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
            // force length to the entire year range
            result[pubName]["goodPercent"][maxYear-minYear] = null;
        }

        // record mGood / mTotal to the year
        var yearOffset = statsByPub[i]["year"] - minYear;
        result[pubName]["goodPercent"][yearOffset] =
            parseFloat(statsByPub[i]["mGood"]) /
            parseFloat(statsByPub[i]["mTotal"]);
    }
    return result;
}

function getMarkerSize(total, scaleMethod) {
    var radius = Math.log(total);
    if (scaleMethod == 'linear') {
        radius = Math.ceil(total / 2000000);
    }
    return Math.max(5, radius);  // assign minimum
}

function shorterNumber(n) {
    var s = '';
    if (n >= 1000000000) {
        s = Math.floor(n / 1000000000) + 'B';
    }
    else if (n >= 1000000) {
        s = Math.floor(n / 1000000) + 'M';
    }
    else if (n >= 1000) {
        s = Math.floor(n / 1000) + 'K';
    }
    else {
        s = n;
    }
    return s;
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
      <div id="city_info" style="display: none"></div>
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
          Publications with correct percentage 
          <span id="color_range_left"></span>
          -
          <span id="color_range_right"></span>
          .
        </div>
      </div>
      <br/>
      <p>Number of words scanned with scaling:</p>
      <!-- scale selector -->
      <div>
        <form>
          <input type="radio" name="scale_select" value="log" checked="checked">Log
          <input type="radio" name="scale_select" value="linear">Linear
        </form>
      </div>
      <!-- size legend -->
      <div id="legend_size"></div>
    </div>
  </div>

  <!-- SIMILE timeline -->
  <div>
    <div id="simile_timeline" class="timeline-default" style="height: 400px;"></div>
    <button onClick="setSimileCenterYear('1890');">set date to 1890</button>
    <button onClick="getSimileCenterYear()">get currently viewed date</button>
    <button onclick="switchSimileTheme();">Switch theme</button> 
    <script type="text/javascript">
        var timeline = document.getElementById('simile_timeline');
        timeline.className += ' dark-theme';
    </script>
  <div>

</body>
</html>

