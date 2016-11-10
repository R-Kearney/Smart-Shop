<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.6.0/pure-min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="Chart.Core.min.js"></script>
<script src="Chart.Scatter.min.js"></script>
<style>
body {
	padding: 20px;
}
 table, th, td {
	 min-width: 50px;
	 padding: 5px;
 }
 .graph {
	 width: 100%;
	 max-width: 1000px;
 }
.button-success {
	color: white;
	border-radius: 4px;
	text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
}

.button-success {
	background: rgb(28, 184, 65); /* this is green */
}
</style>
<?php
// Connect to the MySQL database
$servername = "localhost";
$username = "root";
$password = "raspbian";
$dbname = "TempSensors";
$lastSensor = ""; // holds the name of the previous sensor
$i = 0; // Variable used counting
$k = 0; // holds number of sensors
$timeArray = array();
$tempArray = array();
$sensorName = array(); // holds sensor names
$colourArray = array(); // holds colour for each line

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Select the data and sort it
$sql = "SELECT id, Sensor, Temp, Time FROM Temps ORDER BY `Temps`.`Sensor` ASC, `Temps`.`id`";
$result = $conn->query($sql);

// Check if the database table is empty.
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { // fetches each row from the table
		if ($row["Sensor"] == $lastSensor){ // Check if we are pulling from the same sensor
			array_push(${"tempArray" . $i}, $row["Temp"]); // push next temp into array
			array_push(${"timeArray" . $i}, $row["Time"]); // push next time into array
		}
		else { // make new array for new temp
			if ($k == 0) {
				$k++; // don't increment on the first run
			}
			else {
				$i++; // increment
				$k = $i; // Set k to i
			}
			${"timeArray" . $i} = array(); // new time array with incrimented number
			${"tempArray" . $i} = array(); // new temp array with incrimented number
			array_push(${"tempArray" . $i}, $row["Temp"]); // add the new data
			array_push(${"timeArray" . $i}, $row["Time"]);
			array_push($sensorName, $row["Sensor"]); // Add the sensor name to an array
			array_push($colourArray, random_color()); // make a new random colour and store in array
			$lastSensor = $row["Sensor"]; // Set current = last
		}
    }
} else { // Table has no rows
    echo "0 results found";
}
$conn->close(); // Close database connection

/* Print Temp Sensor Data used for debugging
for ($i = 1; $i <= $k; $i++){
	echo("<b>" . $sensorName[$i] . "</b> ---- " );
	for($x = 0; $x < count(${"tempArray" . $i}); $x++){
		echo("Temp = " . ${"tempArray" . $i}[$x] . " Time = ");
		echo(${"timeArray" . $i}[$x] . " .... ");
	}
	echo("<br>");
}
*/

// Prints data for chart
function tempData($id){
	$num = count($GLOBALS["tempArray" . $id]); // Count elements in current temp array
	for($x = 0; $x < $num; $x++){
		echo("{ x: new Date('" );
		echo($GLOBALS["timeArray" . $id][$x]);
		echo("'), y: " );
		echo($GLOBALS["tempArray" . $id][$x]);
		echo("}" );
		if(($x + 1) != $num) {
			echo(", "); // if not last add ", "
		}
	}
}

// generate random colour
function random_color_part() {
    return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
}
function random_color() {
    return random_color_part() . random_color_part() . random_color_part();
}
?>
</head>
<body>

<?php // Buttons with GET requests for activating the relays ?>
<br>
<table>
	<tr>
	<td>
	<form action="http://192.168.1.35/relay.html" target="_blank">
		<input type="hidden" name="pin" value="ON1">
		<input class="button-success pure-button" type="submit" value="Relay 1">
	</form>
	</td>
	<td>
	<form action="http://192.168.1.35/relay.html" target="_blank">
		<input type="hidden" name="pin" value="ON2">
		<input class="button-success pure-button" type="submit" value="Relay 2">
	</form>
	</td>
	</tr>
</table>
<br>

<?php // Container to hold graph and ledgend ?>
<div class="graph">
	<h3>Fridge Temp Graph</h3>
	<canvas id="tempGraph" width="430px" height="220px"></canvas>
	<table><?php
		for ($i = 0; $i <= $k; $i++){
			echo("<td bgcolor='" . $colourArray[$i] . "'> </td>");
			echo("<td> " . $sensorName[$i] . "</td> <td> </td>");
		} ?>
	</table>
</div>

<?php // Chart.js ?>
<script>
	Chart.defaults.global.responsive = true;
	Chart.defaults.global.animation = false;

		var data3 = [{
			<?php for ($i = 0; $i <= $k; $i++){ ?>
					label: '<?php echo($sensorName[$i]); ?>',
					strokeColor: <?php echo("'#" . $colourArray[$i] . "'"); ?>,
					data: [<?php  tempData($i);  ?>
					] }<?php if ($i != $k){ echo(", {"); }
				 } ?>
				];

		var ctx3 = document.getElementById("tempGraph").getContext("2d");
		var myDateLineChart = new Chart(ctx3).Scatter(data3, {
			bezierCurve: true,
			showTooltips: true,
			scaleShowHorizontalLines: true,
			scaleShowLabels: true,
			scaleType: "date",
			legend: {position: 'bottom',},
			scaleLabel: "<%=value%>oC",
			scaleOverride : true,
			scaleSteps : 15,
			scaleStepWidth : 2,
			scaleStartValue : 0
		});
</script>
</body>
</html>
