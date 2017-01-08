<!--
Ricky Kearney
Smart Supermarket - Hub

This website pulls in the data from the mysql database and displays it on a
chart.js chart. The current temp is shown and range of data is adjustable.
The relay buttons aren't used yet but will be to open shutters or activate lights etc...

 -->

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.6.0/pure-min.css">
<link rel="stylesheet" href="/style.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="Chart.Core.min.js"></script>
<script src="Chart.Scatter.min.js"></script>
<div class="content">
<div class="relays">
<?php
// Connect to the MySQL database
$servername = "localhost";
$username = "root";
$password = "raspbian";
$dbname = "TempSensors";
$lastSensor = ""; // holds the name of the previous sensor
$i = 0; // Variable used counting
$k = 0; // Another counting Variable
$numberOfSensors = 0;
$timeArray = array();
$tempArray = array();
$sensorName = array(); // holds sensor names
$colourArray = array("af038e", "1bb33f", "1b51b3", "b3ae1b"); // holds colour for each line
$displayRange = 288; // one day
$sensorNumber = -1;

// // Get range if set
if (isset($_GET["range"])) {
		$displayRange = $_GET["range"];
		if ($displayRange < 0) {
			echo "Display All";
		}
		else{
		echo "Display Range = Last " . $displayRange . " Inputs";
	}
}

// // Select only data from this sensor if set
if (isset($_GET["sensor"])) {
		$sensorNumber = $_GET["sensor"];
	}

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
						// array_push($colourArray, random_color()); // make a new random colour and store in array
						$lastSensor = $row["Sensor"]; // Set current = last
					}
			    }
							$numberOfSensors = $i;
} else { // Table has no rows
    echo "<p><b>0 results found</b></p>";
}
$conn->close(); // Close database connection

/* Print Temp Sensor Data used for debugging
for ($i = 1; $i <= $numberOfSensors; $i++){
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
	$endRange = count($GLOBALS["tempArray" . $id]); // Count elements in current temp array
	$x = $GLOBALS["displayRange"];
	if ($x < 0 || $x >= $endRange){
		 $element = 0; // Get start Element
	}
	else{
		$element = ($endRange -  $x); // Get start Element
	}
	while ($element < $endRange){
		echo("{ x: new Date('" );
		echo($GLOBALS["timeArray" . $id][$element]);
		echo("'), y: " );
		echo($GLOBALS["tempArray" . $id][$element]);
		echo("}" );
		if(($element + 1) != $endRange) {
			echo(", "); // if not last add ", "
		}
		$element++;
	}
}
?>
</head>
<body>

<?php // Buttons with GET requests for activating the relays ?>
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
</div>

<?php // Last Temps and display range ?>
<div class="currentData">
<table>
	<tr>
	<td>
		<form action="index.php" target="_self">
			<input type="hidden" name="sensor" value="<?php  if ($sensorNumber >= 0) { echo $sensorNumber; } ?>">
			<select name="range">
				<option value="24">2 Hours</option>
		  <option value="288" selected="selected">1 Day</option>
		  <option value="2016">7 Days</option>
		  <option value="8640">1 Month</option>
		  <option value="-1">ALL</option>
			</select>
		<input class="button-success pure-button" type="submit" value="Range">
	</form>
	</td>
</tr>
	<?php
	 // Shows Current Temp
		// $numberOfSensors = number of temp sensors
		for ($x=0; $x <= $numberOfSensors; $x++) {
			echo "<tr> <td>";
			echo "<a href=index.php?sensor=" . $x;
			if (isset($_GET["range"])) {
				echo "&range=" . $displayRange;
			}
			echo ">";
			echo"<div class=\"colour_box " . "cb" . $x . "\"></div>";
			$lastElement = (count(${"tempArray" . $x}) -1);
			echo $sensorName[$x] . " Temp: " . "<b>" . ${"tempArray" . $x}[$lastElement] . "</b>";
			echo "</a> </td> </tr>";
		}
		?>
</table>
</div>
</div>


<?php // Container to hold graph and ledgend ?>
<div class="graph">
	<h3>Fridge Temp Graph</h3>
	<canvas id="tempGraph" width="430px" height="220px"></canvas>
	<table><?php
		for ($i = 0; $i <= $numberOfSensors; $i++){
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
			<?php
			if ($sensorNumber >= 0) {
				$i = $sensorNumber;
			?>
				label: '<?php echo($sensorName[$i]); ?>',
				strokeColor: <?php echo("'#" . $colourArray[$i] . "'"); ?>,
				data: [<?php  tempData($i);  ?>
				] }
		<?php	}
			else {
				for ($i = 0; $i <= $numberOfSensors; $i++){
				?>
					label: '<?php echo($sensorName[$i]); ?>',
					strokeColor: <?php echo("'#" . $colourArray[$i] . "'"); ?>,
					data: [<?php  tempData($i);  ?>
					] }
					<?php if ($i != $numberOfSensors ){ echo(", {"); }
				 }
				}
			?>
				];

		var ctx3 = document.getElementById("tempGraph").getContext("2d");
		var myDateLineChart = new Chart(ctx3).Scatter(data3, {
			bezierCurve: true,
			showTooltips: true,
			scaleShowHorizontalLines: true,
			scaleShowLabels: true,
			scaleType: "date",
			legend: {
				display: false,
						labels: {
        display: false
							}
     },
			scale: {position: 'right'},
			scaleLabel: "<%=value%>oC",
			scaleOverride : true,
			scaleSteps : 20,
			scaleStepWidth : 2,
			scaleStartValue : -22
		});
</script>
<br></br>
<?php
echo "Memory Usage " . memory_get_usage() . "Bytes";
?>
</body>
</html>
