<?php
session_start();

function getSessionValue($key) {
    if (isset($_POST[$key])) {
        $_SESSION[$key] = $_POST[$key];
    }
    return $_SESSION[$key];
}
//echo "Current Year = ".getSessionValue("currentYear")."<br/>";
//echo "Current City = ".getSessionValue("currentCity")."<br/>";

?>


<?php
// include view files
include("view_pub_in_city.php");
include("view_map.php");

// include model files

// include control files

?>

<html>
<head>

<style type="text/css">
#centercolumn { 
 float: left;
 color: #333;
 background: #FFFFFF;
 width: 60%;
 display: inline;
}

#leftcolumn { 
 color: #333;
 background: #EBE3CD;
 width: 20%;
 float: left;
}

#rightcolumn { 
 color: #333;
 background: #EBE3CD;
 width: 20%;
 float: left;
}
</style>
</head>
<body>

<table border="1" width="100%">

<!-- Title -->
<tr><td>
<table><tr><td><h1>Texas Newspaper Collection</h1></td></tr></table>
</td></tr>


<!-- Time bar -->
<tr>
<td><form method="POST" action="index.php">
<input type="submit" value="Set Current Year"></input>
<input type="text" name="currentYear"
       value="<?php echo getSessionValue("currentYear") ?>">
</form>

<form method="POST" action="index.php">
<input type="submit" value="Set Current City"></input>
<input type="text" name="currentCity"
       value="<?php echo getSessionValue("currentCity") ?>">
</form></td>
</tr>

<tr><td>


<!-- Pub in City -->
<div id="leftcolumn">
<?php view_pub_in_city(); ?>
Left
</div>

<!-- Map -->
<div id="centercolumn">
<?php //view_map(); ?>
CENTER
</div>

<!-- Legend -->
<div id="rightcolumn">
Legend
</div>

</td></tr>

</table>

<div><a href="map_count.html">Map of Count By City</a></div>
<div><a href="city_year.html">Plots of Count By City</a></div>


</body>
</html>

