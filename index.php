<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Texas Newspaper Collection</title>

<style type="text/css">
  #leftcolumn {
      width: 30%;
      float: left;
  }
  #map_canvas {
      width: 50%;
      height: 500px;
      float: right;
  }
  #rightcolumn {
      width: 20%;
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

    xSlider.after( "valueChange", updateCurrYear);
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
    showMarkers();

    addLeftColumnTable();

    addPolygon(map);

    updateCurrYear();
    updateCurrCity(currCity);
}

function addPolygon(map) {
    var c = [
    new google.maps.LatLng(36.50000,-103.00029),
    new google.maps.LatLng(33.99972,-103.04167),
    new google.maps.LatLng(32.99944,-103.06583),
    new google.maps.LatLng(31.99972,-103.06652),
    new google.maps.LatLng(31.99972,-106.61722),
    new google.maps.LatLng(31.98743,-106.66223),
    new google.maps.LatLng(31.88611,-106.62860),
    new google.maps.LatLng(31.82680,-106.59333),
    new google.maps.LatLng(31.78076,-106.52288),
    new google.maps.LatLng(31.75027,-106.46001),
    new google.maps.LatLng(31.75403,-106.41654),
    new google.maps.LatLng(31.68735,-106.33431),
    new google.maps.LatLng(31.63750,-106.30335),
    new google.maps.LatLng(31.55930,-106.27223),
    new google.maps.LatLng(31.47222,-106.20987),
    new google.maps.LatLng(31.43194,-106.14362),
    new google.maps.LatLng(31.40250,-106.06293),
    new google.maps.LatLng(31.39527,-106.01167),
    new google.maps.LatLng(31.36972,-105.97278),
    new google.maps.LatLng(31.26916,-105.84195),
    new google.maps.LatLng(31.20819,-105.78696),
    new google.maps.LatLng(31.08153,-105.60001),
    new google.maps.LatLng(30.99639,-105.54140),
    new google.maps.LatLng(30.94639,-105.49071),
    new google.maps.LatLng(30.86584,-105.39306),
    new google.maps.LatLng(30.79722,-105.25306),
    new google.maps.LatLng(30.77861,-105.16944),
    new google.maps.LatLng(30.74972,-105.12500),
    new google.maps.LatLng(30.63222,-104.99084),
    new google.maps.LatLng(30.60764,-104.93473),
    new google.maps.LatLng(30.56624,-104.89653),
    new google.maps.LatLng(30.52500,-104.87973),
    new google.maps.LatLng(30.46639,-104.86501),
    new google.maps.LatLng(30.38611,-104.82224),
    new google.maps.LatLng(30.31806,-104.77695),
    new google.maps.LatLng(30.23333,-104.70556),
    new google.maps.LatLng(30.18750,-104.68224),
    new google.maps.LatLng(30.10542,-104.67931),
    new google.maps.LatLng(30.05888,-104.70133),
    new google.maps.LatLng(30.00500,-104.69501),
    new google.maps.LatLng(29.94097,-104.67806),
    new google.maps.LatLng(29.67292,-104.54182),
    new google.maps.LatLng(29.59584,-104.45334),
    new google.maps.LatLng(29.52500,-104.33805),
    new google.maps.LatLng(29.52597,-104.28514),
    new google.maps.LatLng(29.49445,-104.22835),
    new google.maps.LatLng(29.42277,-104.16973),
    new google.maps.LatLng(29.34250,-104.06361),
    new google.maps.LatLng(29.32111,-104.02362),
    new google.maps.LatLng(29.30583,-103.97501),
    new google.maps.LatLng(29.28583,-103.88917),
    new google.maps.LatLng(29.19916,-103.73584),
    new google.maps.LatLng(29.12611,-103.53140),
    new google.maps.LatLng(29.07305,-103.46195),
    new google.maps.LatLng(29.03639,-103.40112),
    new google.maps.LatLng(28.99652,-103.29087),
    new google.maps.LatLng(28.98403,-103.16369),
    new google.maps.LatLng(29.09416,-103.05195),
    new google.maps.LatLng(29.18306,-102.95500),
    new google.maps.LatLng(29.25416,-102.89585),
    new google.maps.LatLng(29.35222,-102.85112),
    new google.maps.LatLng(29.47408,-102.80476),
    new google.maps.LatLng(29.74277,-102.67029),
    new google.maps.LatLng(29.78166,-102.49625),
    new google.maps.LatLng(29.85055,-102.35556),
    new google.maps.LatLng(29.88798,-102.30181),
    new google.maps.LatLng(29.84722,-102.22778),
    new google.maps.LatLng(29.80277,-102.09750),
    new google.maps.LatLng(29.79847,-102.04820),
    new google.maps.LatLng(29.80583,-101.99085),
    new google.maps.LatLng(29.80472,-101.82584),
    new google.maps.LatLng(29.79666,-101.77474),
    new google.maps.LatLng(29.77806,-101.70668),
    new google.maps.LatLng(29.76639,-101.62876),
    new google.maps.LatLng(29.77111,-101.53917),
    new google.maps.LatLng(29.77278,-101.40501),
    new google.maps.LatLng(29.66055,-101.35584),
    new google.maps.LatLng(29.61527,-101.31084),
    new google.maps.LatLng(29.54027,-101.21861),
    new google.maps.LatLng(29.49069,-101.13904),
    new google.maps.LatLng(29.43777,-101.02528),
    new google.maps.LatLng(29.35000,-100.93695),
    new google.maps.LatLng(29.25875,-100.79571),
    new google.maps.LatLng(29.10903,-100.66584),
    new google.maps.LatLng(28.99583,-100.62821),
    new google.maps.LatLng(28.93333,-100.62195),
    new google.maps.LatLng(28.86361,-100.56445),
    new google.maps.LatLng(28.82277,-100.52736),
    new google.maps.LatLng(28.72571,-100.49182),
    new google.maps.LatLng(28.67584,-100.47986),
    new google.maps.LatLng(28.62639,-100.44389),
    new google.maps.LatLng(28.51833,-100.35917),
    new google.maps.LatLng(28.40041,-100.33126),
    new google.maps.LatLng(28.28056,-100.28146),
    new google.maps.LatLng(28.19750,-100.18640),
    new google.maps.LatLng(28.15562,-100.07834),
    new google.maps.LatLng(27.96166,-99.93501),
    new google.maps.LatLng(27.90389,-99.88084),
    new google.maps.LatLng(27.80514,-99.86091),
    new google.maps.LatLng(27.77174,-99.80670),
    new google.maps.LatLng(27.71472,-99.74140),
    new google.maps.LatLng(27.66894,-99.71390),
    new google.maps.LatLng(27.64139,-99.65363),
    new google.maps.LatLng(27.64124,-99.60618),
    new google.maps.LatLng(27.56805,-99.50390),
    new google.maps.LatLng(27.47666,-99.47320),
    new google.maps.LatLng(27.25806,-99.44335),
    new google.maps.LatLng(27.04694,-99.45862),
    new google.maps.LatLng(26.86985,-99.31925),
    new google.maps.LatLng(26.83083,-99.25334),
    new google.maps.LatLng(26.71445,-99.20056),
    new google.maps.LatLng(26.52694,-99.13251),
    new google.maps.LatLng(26.43500,-99.10474),
    new google.maps.LatLng(26.40583,-98.97557),
    new google.maps.LatLng(26.34889,-98.78418),
    new google.maps.LatLng(26.28972,-98.69556),
    new google.maps.LatLng(26.25611,-98.57834),
    new google.maps.LatLng(26.22333,-98.43987),
    new google.maps.LatLng(26.19166,-98.38806),
    new google.maps.LatLng(26.15361,-98.36139),
    new google.maps.LatLng(26.09777,-98.28612),
    new google.maps.LatLng(26.06250,-98.20001),
    new google.maps.LatLng(26.04777,-98.03334),
    new google.maps.LatLng(26.05861,-97.97974),
    new google.maps.LatLng(26.06361,-97.84751),
    new google.maps.LatLng(26.03805,-97.67890),
    new google.maps.LatLng(26.00500,-97.61446),
    new google.maps.LatLng(25.95111,-97.55945),
    new google.maps.LatLng(25.89833,-97.51445),
    new google.maps.LatLng(25.84333,-97.41723),
    new google.maps.LatLng(25.85966,-97.34474),
    new google.maps.LatLng(25.92000,-97.31529),
    new google.maps.LatLng(25.94111,-97.26529),
    new google.maps.LatLng(25.96643,-97.14074),
    new google.maps.LatLng(26.07014,-97.16788),
    new google.maps.LatLng(26.02771,-97.18066),
    new google.maps.LatLng(25.98194,-97.24077),
    new google.maps.LatLng(26.16138,-97.31751),
    new google.maps.LatLng(26.24611,-97.31862),
    new google.maps.LatLng(26.36944,-97.40195),
    new google.maps.LatLng(26.41195,-97.41278),
    new google.maps.LatLng(26.54555,-97.42305),
    new google.maps.LatLng(26.80701,-97.50403),
    new google.maps.LatLng(26.84208,-97.56041),
    new google.maps.LatLng(27.00548,-97.55742),
    new google.maps.LatLng(27.03083,-97.47501),
    new google.maps.LatLng(27.12805,-97.44445),
    new google.maps.LatLng(27.26229,-97.42936),
    new google.maps.LatLng(27.25777,-97.47695),
    new google.maps.LatLng(27.23527,-97.53389),
    new google.maps.LatLng(27.25250,-97.63389),
    new google.maps.LatLng(27.31698,-97.67670),
    new google.maps.LatLng(27.39554,-97.72333),
    new google.maps.LatLng(27.44972,-97.76934),
    new google.maps.LatLng(27.43222,-97.72333),
    new google.maps.LatLng(27.38555,-97.67694),
    new google.maps.LatLng(27.34480,-97.62469),
    new google.maps.LatLng(27.37722,-97.52528),
    new google.maps.LatLng(27.30624,-97.49000),
    new google.maps.LatLng(27.32729,-97.41299),
    new google.maps.LatLng(27.65611,-97.27973),
    new google.maps.LatLng(27.71555,-97.31555),
    new google.maps.LatLng(27.78334,-97.39277),
    new google.maps.LatLng(27.82583,-97.48472),
    new google.maps.LatLng(27.87806,-97.49249),
    new google.maps.LatLng(27.85250,-97.34527),
    new google.maps.LatLng(27.82180,-97.19451),
    new google.maps.LatLng(28.03166,-97.02250),
    new google.maps.LatLng(28.09166,-97.04611),
    new google.maps.LatLng(28.06972,-97.09806),
    new google.maps.LatLng(28.04000,-97.14666),
    new google.maps.LatLng(28.06854,-97.21347),
    new google.maps.LatLng(28.16180,-97.16937),
    new google.maps.LatLng(28.18638,-97.02806),
    new google.maps.LatLng(28.12111,-96.98334),
    new google.maps.LatLng(28.12708,-96.93097),
    new google.maps.LatLng(28.14027,-96.88276),
    new google.maps.LatLng(28.24138,-96.78084),
    new google.maps.LatLng(28.34896,-96.78382),
    new google.maps.LatLng(28.40986,-96.84522),
    new google.maps.LatLng(28.47152,-96.80034),
    new google.maps.LatLng(28.43777,-96.74695),
    new google.maps.LatLng(28.39770,-96.70708),
    new google.maps.LatLng(28.33409,-96.69701),
    new google.maps.LatLng(28.32416,-96.62471),
    new google.maps.LatLng(28.44173,-96.39997),
    new google.maps.LatLng(28.50944,-96.48999),
    new google.maps.LatLng(28.56694,-96.58389),
    new google.maps.LatLng(28.71194,-96.64473),
    new google.maps.LatLng(28.69756,-96.56256),
    new google.maps.LatLng(28.64777,-96.55520),
    new google.maps.LatLng(28.61944,-96.49555),
    new google.maps.LatLng(28.63548,-96.40867),
    new google.maps.LatLng(28.76027,-96.44291),
    new google.maps.LatLng(28.73619,-96.39411),
    new google.maps.LatLng(28.68514,-96.26736),
    new google.maps.LatLng(28.69548,-96.19208),
    new google.maps.LatLng(28.62729,-96.14080),
    new google.maps.LatLng(28.58195,-96.21743),
    new google.maps.LatLng(28.60208,-96.13361),
    new google.maps.LatLng(28.63445,-96.05915),
    new google.maps.LatLng(28.65104,-95.99068),
    new google.maps.LatLng(28.60388,-95.99652),
    new google.maps.LatLng(28.57388,-96.07236),
    new google.maps.LatLng(28.53833,-96.14666),
    new google.maps.LatLng(28.48812,-96.21201),
    new google.maps.LatLng(28.52195,-96.13110),
    new google.maps.LatLng(28.59027,-96.00056),
    new google.maps.LatLng(28.62138,-95.89555),
    new google.maps.LatLng(28.64833,-95.82723),
    new google.maps.LatLng(28.69249,-95.76551),
    new google.maps.LatLng(28.66819,-95.84778),
    new google.maps.LatLng(28.64000,-95.89612),
    new google.maps.LatLng(28.62632,-95.94152),
    new google.maps.LatLng(28.68642,-95.94111),
    new google.maps.LatLng(28.73694,-95.79723),
    new google.maps.LatLng(28.73814,-95.68989),
    new google.maps.LatLng(28.75556,-95.61833),
    new google.maps.LatLng(28.89611,-95.35973),
    new google.maps.LatLng(28.93153,-95.30319),
    new google.maps.LatLng(29.05124,-95.14861),
    new google.maps.LatLng(29.18194,-95.08695),
    new google.maps.LatLng(29.33777,-94.89390),
    new google.maps.LatLng(29.42090,-94.89799),
    new google.maps.LatLng(29.56556,-95.01584),
    new google.maps.LatLng(29.71506,-95.06006),
    new google.maps.LatLng(29.71652,-95.00709),
    new google.maps.LatLng(29.69583,-94.95555),
    new google.maps.LatLng(29.76195,-94.82431),
    new google.maps.LatLng(29.78500,-94.75702),
    new google.maps.LatLng(29.75680,-94.71104),
    new google.maps.LatLng(29.71111,-94.70666),
    new google.maps.LatLng(29.61291,-94.73042),
    new google.maps.LatLng(29.56805,-94.76584),
    new google.maps.LatLng(29.55277,-94.67556),
    new google.maps.LatLng(29.57319,-94.57403),
    new google.maps.LatLng(29.55888,-94.47659),
    new google.maps.LatLng(29.51653,-94.51542),
    new google.maps.LatLng(29.49444,-94.61221),
    new google.maps.LatLng(29.46749,-94.69208),
    new google.maps.LatLng(29.36791,-94.75417),
    new google.maps.LatLng(29.40944,-94.70806),
    new google.maps.LatLng(29.44708,-94.65361),
    new google.maps.LatLng(29.58416,-94.31751),
    new google.maps.LatLng(29.65361,-94.13430),
    new google.maps.LatLng(29.67916,-94.03639),
    new google.maps.LatLng(29.68163,-93.85844),
    new google.maps.LatLng(29.76556,-93.91472),
    new google.maps.LatLng(29.81666,-93.95847),
    new google.maps.LatLng(29.98514,-93.85201),
    new google.maps.LatLng(29.99416,-93.79666),
    new google.maps.LatLng(30.06597,-93.72292),
    new google.maps.LatLng(30.15138,-93.69846),
    new google.maps.LatLng(30.25111,-93.71501),
    new google.maps.LatLng(30.31083,-93.73916),
    new google.maps.LatLng(30.37306,-93.75695),
    new google.maps.LatLng(30.54930,-93.73251),
    new google.maps.LatLng(30.64638,-93.67917),
    new google.maps.LatLng(30.68361,-93.63474),
    new google.maps.LatLng(30.83125,-93.56235),
    new google.maps.LatLng(31.07416,-93.53528),
    new google.maps.LatLng(31.18478,-93.53708),
    new google.maps.LatLng(31.23138,-93.60777),
    new google.maps.LatLng(31.36361,-93.66028),
    new google.maps.LatLng(31.54500,-93.78889),
    new google.maps.LatLng(31.60250,-93.83229),
    new google.maps.LatLng(31.68514,-93.81931),
    new google.maps.LatLng(31.78569,-93.83750),
    new google.maps.LatLng(31.86374,-93.88736),
    new google.maps.LatLng(31.90506,-93.93034),
    new google.maps.LatLng(31.92346,-93.97014),
    new google.maps.LatLng(31.99592,-94.04253),
    new google.maps.LatLng(32.91638,-94.04334),
    new google.maps.LatLng(33.01093,-94.04149),
    new google.maps.LatLng(33.55332,-94.04527),
    new google.maps.LatLng(33.57361,-94.09042),
    new google.maps.LatLng(33.58971,-94.19138),
    new google.maps.LatLng(33.56266,-94.31569),
    new google.maps.LatLng(33.55583,-94.38416),
    new google.maps.LatLng(33.60500,-94.45167),
    new google.maps.LatLng(33.64716,-94.48419),
    new google.maps.LatLng(33.64916,-94.54361),
    new google.maps.LatLng(33.69249,-94.71417),
    new google.maps.LatLng(33.74944,-94.85278),
    new google.maps.LatLng(33.81722,-94.95223),
    new google.maps.LatLng(33.85416,-94.99777),
    new google.maps.LatLng(33.94540,-95.15389),
    new google.maps.LatLng(33.96221,-95.22753),
    new google.maps.LatLng(33.91173,-95.25890),
    new google.maps.LatLng(33.88750,-95.30499),
    new google.maps.LatLng(33.87027,-95.37055),
    new google.maps.LatLng(33.86666,-95.43306),
    new google.maps.LatLng(33.91465,-95.54410),
    new google.maps.LatLng(33.94332,-95.59264),
    new google.maps.LatLng(33.86776,-95.77028),
    new google.maps.LatLng(33.85583,-95.87471),
    new google.maps.LatLng(33.87055,-95.96112),
    new google.maps.LatLng(33.84096,-96.09847),
    new google.maps.LatLng(33.82132,-96.14847),
    new google.maps.LatLng(33.76361,-96.18777),
    new google.maps.LatLng(33.69999,-96.32049),
    new google.maps.LatLng(33.77944,-96.47278),
    new google.maps.LatLng(33.84944,-96.59473),
    new google.maps.LatLng(33.84471,-96.72820),
    new google.maps.LatLng(33.86804,-96.84292),
    new google.maps.LatLng(33.90221,-96.87791),
    new google.maps.LatLng(33.95499,-96.90590),
    new google.maps.LatLng(33.94944,-96.97409),
    new google.maps.LatLng(33.84749,-97.04777),
    new google.maps.LatLng(33.75361,-97.08307),
    new google.maps.LatLng(33.73333,-97.16195),
    new google.maps.LatLng(33.78513,-97.19861),
    new google.maps.LatLng(33.83416,-97.19500),
    new google.maps.LatLng(33.89194,-97.24222),
    new google.maps.LatLng(33.82111,-97.39681),
    new google.maps.LatLng(33.84152,-97.45514),
    new google.maps.LatLng(33.90221,-97.51306),
    new google.maps.LatLng(33.97083,-97.59500),
    new google.maps.LatLng(33.98318,-97.67153),
    new google.maps.LatLng(33.95888,-97.71501),
    new google.maps.LatLng(33.89145,-97.78305),
    new google.maps.LatLng(33.86069,-97.86104),
    new google.maps.LatLng(33.87791,-97.94764),
    new google.maps.LatLng(33.93554,-97.94784),
    new google.maps.LatLng(33.99819,-97.98138),
    new google.maps.LatLng(34.00750,-98.05735),
    new google.maps.LatLng(34.06656,-98.09351),
    new google.maps.LatLng(34.12777,-98.16722),
    new google.maps.LatLng(34.13221,-98.28277),
    new google.maps.LatLng(34.08215,-98.40903),
    new google.maps.LatLng(34.06985,-98.47875),
    new google.maps.LatLng(34.10360,-98.53431),
    new google.maps.LatLng(34.15152,-98.58203),
    new google.maps.LatLng(34.15263,-98.65903),
    new google.maps.LatLng(34.13750,-98.74055),
    new google.maps.LatLng(34.15471,-98.83528),
    new google.maps.LatLng(34.19805,-99.02445),
    new google.maps.LatLng(34.21541,-99.17485),
    new google.maps.LatLng(34.27277,-99.20056),
    new google.maps.LatLng(34.32707,-99.19659),
    new google.maps.LatLng(34.40263,-99.25792),
    new google.maps.LatLng(34.43749,-99.34167),
    new google.maps.LatLng(34.41999,-99.39111),
    new google.maps.LatLng(34.38027,-99.44388),
    new google.maps.LatLng(34.37874,-99.62986),
    new google.maps.LatLng(34.39875,-99.68611),
    new google.maps.LatLng(34.45333,-99.75196),
    new google.maps.LatLng(34.50666,-99.81027),
    new google.maps.LatLng(34.55694,-99.87264),
    new google.maps.LatLng(34.57527,-99.93069),
    new google.maps.LatLng(34.57694,-99.99596),
    new google.maps.LatLng(34.88276,-100.00111),
    new google.maps.LatLng(36.49972,-100.00029),
    new google.maps.LatLng(36.50000,-103.00029),
    ];

    t = new google.maps.Polygon({
        paths: c,
        strokeColor: "#666666",
        strokeOpacity: 0.8,
        strokeWeight: 4,
        fillColor: "#000000",
        fillOpacity: 0,
    });

    t.setMap(map);
}

function addLeftColumnTable() {
    var content = document.getElementById("leftcolumn");
    var tbl = document.createElement("table");
    tbl.id = "infoTable";
    content.appendChild(tbl);
}

function updateCurrYear(e) {
    if (e != null) {
        currYear = e.newVal;
    }

    // step 1 keep only markers with publication in that year
    showMarkers();
}

function updateCurrCity(city) {
    // record newly updated city
    currCity = city;

    // step 1 update info table in left column
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

    // step 2 update city info in right column
    var city_info = document.getElementById("city_info");
    var stats = null;
    for (var i = 0; i < statsByCity.length; i++) {
        if (statsByCity[i]["city"] == currCity &&
            statsByCity[i]["year"] == currYear) {
            stats = statsByCity[i];
        }
    }
    if (stats != null) {
        city_info.innerHTML = "Year: " + currYear + "<br/>" +
                              "City: " + currCity + "<br/>" +
                              "Good Characters Scanned: " + stats["mGood"] + "<br/>" +
                              "Total Characters Scanned: " + stats["mTotal"] + "<br/>";
    }
    else {
        city_info.innerHTML = "There is no publications at " + currCity + " in the year " + currYear;
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
            city: markerLoc[i]["city"],
            year: markerLoc[i]["year"],
        });

        addMarkerListener(marker);

        markers.push(marker);
    }
}

function addMarkerListener(marker) {
    google.maps.event.addListener(marker, "click", function() {
        updateCurrCity(marker.city);
    });
}

function showMarkers() {
    if (markers) {
        for (i in markers) {
            if (currYear == markers[i].year) {
                markers[i].setMap(map);
            }
            else {
                markers[i].setMap(null);
            }
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

  <!-- left column -->
  <div id="leftcolumn"></div>

  <!-- right column -->
  <div id="rightcolumn">
    <div><a href="map_count.html">Map of Count By City</a></div>
    <div><a href="city_year.html">Plots of Count By City</a></div>
    <div id="debug"></div>
    <br/> <br/> <br/> <br/>
    <div id="city_info"></div>
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

