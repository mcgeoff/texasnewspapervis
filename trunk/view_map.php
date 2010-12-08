
<script src="./protovis-r3.2.js" type="text/javascript"></script>
<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=ABQIAAAAYZ9eMYFYusxZt-1RKXLI7RQGpXqX26B62_lhdlIUxPTUm0CSORRw1BkwdprB1xQ3Xa8KfbgKAacxlw" type="text/javascript"></script>
<!-- CONFIG: set data source file -->
<script src="map_count.js" type="text/javascript"></script>

<script type="text/javascript+protovis">

<!-- CONFIG: place color -->
var colors = {
  1: { light: "rgba(40, 220, 0, .8)", dark: "rgb(40, 220, 0)" },
  2: { light: "rgba(80, 180, 0, .8)", dark: "rgb(80, 180, 0)" },
  3: { light: "rgba(120, 140, 0, .8)", dark: "rgb(120, 140, 0)" },
  4: { light: "rgba(160, 100, 0, .8)", dark: "rgb(160, 100, 0)" },
  5: { light: "rgba(200, 60, 0, .8)", dark: "rgb(200, 60, 0)" },
  6: { light: "rgba(200, 60, 0, .8)", dark: "rgb(200, 60, 0)" },
  7: { light: "rgba(200, 60, 0, .8)", dark: "rgb(200, 60, 0)" },
  8: { light: "rgba(200, 60, 0, .8)", dark: "rgb(200, 60, 0)" },
  9: { light: "rgba(200, 60, 0, .8)", dark: "rgb(200, 60, 0)" },
};
dataContent.forEach(function(x) colors[x.code] = colors[Math.round(parseFloat(x.badRatio) * 10)]);

function Canvas(dataContent) {
  this.dataContent = dataContent;
}

Canvas.prototype = pv.extend(GOverlay);

Canvas.prototype.initialize = function(map) {
  this.map = map;
  this.canvas = document.createElement("div");
  this.canvas.setAttribute("class", "canvas");
  map.getPane(G_MAP_MAP_PANE).parentNode.appendChild(this.canvas);
};

Canvas.prototype.redraw = function(force) {
  if (!force) return;
  var c = this.canvas, m = this.map, r = 20;

  /* Get the pixel locations of the dataContent. */
  var pixels = this.dataContent.map(function(d) {
      return m.fromLatLngToDivPixel(new GLatLng(d.lat, d.lon));
    });

  /* Update the canvas bounds. Note: may be large. */
  function x(p) p.x; function y(p) p.y;
  var x = { min: pv.min(pixels, x) - r, max: pv.max(pixels, x) + r };
  var y = { min: pv.min(pixels, y) - r, max: pv.max(pixels, y) + r };
  c.style.width = (x.max - x.min) + "px";
  c.style.height = (y.max - y.min) + "px";
  c.style.left = x.min + "px";
  c.style.top = y.min + "px";

  /* Render the visualization. */
  new pv.Panel()
      .canvas(c)
      .left(-x.min)
      .top(-y.min)
    .add(pv.Panel)
      .data(this.dataContent)
    .add(pv.Dot)
      .left(function() pixels[this.parent.index].x)
      .top(function() pixels[this.parent.index].y)
      .strokeStyle(function(x, d) colors[d.code].dark)
      .fillStyle(function(x, d) colors[d.code].light)
      .size(140)
    .anchor("center").add(pv.Label)
      .textStyle("white")
      .text(function(x, d) d.badRatio)  /* CONFIG: set icon text */
    .root.render();
};

/* Restrict minimum and maximum zoom levels. */
G_NORMAL_MAP.getMinimumResolution = function() 2;
G_NORMAL_MAP.getMaximumResolution = function() 24;

var map = new GMap2(document.getElementById("map"));
map.setCenter(new GLatLng(31, -97), 6);
map.setUI(map.getDefaultUI());
map.addOverlay(new Canvas(dataContent));

</script>




<?

function view_map() {
    //echo "Map with year ".$_SESSION["currentYear"]."<br/>";
    echo '<div id="map" />';
}

?>
