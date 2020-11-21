<!DOCTYPE html>
<html>
    <head>
        <title>sim800 data test</title>		
		<style>
		body { 
			background-color: #c4e3ed;
			font-family: arial;		
		}
		.infoBox {
			width: 350px;
			height: 100px;
			position: absolute;
			left: 50px;
			font-size:120%;
			color: Navy;
			text-align: center;
			vertical-align: middle;			
			margin-left: auto;
			margin-right: auto;
		}
		#screen {
			width: 450px;
			height: 530px;
			text-align: center;
			vertical-align: middle;
			background-color: LightGray;
			font-size:120%;
			position: relative;
			margin-left: auto;
			margin-right: auto;
			top: 50px;			 
		}
			#data {
			  background-color: Orange;
			  bottom: 380px;
			}
			
			#connect_time {
			  background-color: Tomato;
			  bottom: 270px;
			}
			#sim800_Stime {			  			  
			  background-color: MediumSeaGreen;
			  bottom: 160px;
			}
			#detect_time {
			  background-color: DodgerBlue;
			  bottom: 50px;  
			}
		.tempData {
			table-layout: fixed;
			width: 40%;
			border-collapse: collapse;  
			white-space: nowrap;
			overflow: hidden;
		}
		.row-ID {width: 5%;}
		.row-Date {width: 12%;}
		.row-Time {width: 10%;}
		.row-Data {width: 6%;}

		table, td, th {
		  border: 2px solid black;
		  padding: 5px;   
		  align: center;
		  text-align: center;
		}
		tr:nth-child(even){background-color: #e4f3fe} 
		
		</style>
    </head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <body>      
		<div id = "screen">
			<div class = "infoBox" id = "data">
				<h4>Host. System Time</h4>
				<!-- <button type="button" onclick="loadDoc()">Change Content</button> -->
			</div>			
			<div class = "infoBox" id = "connect_time"> <h5>Hostinger<br>Data transfer time</h5> </div>
			<div class = "infoBox" id = "sim800_Stime"> <h4>sim800<br>Data transfer time</h4> </div>
			<div class = "infoBox" id = "detect_time">  <h5>sim800<br>Truck detection time</h5> </div>
		</div>
		
	<div align="center"> <br><br><br><br><br>sim800 DB<br>
	
	<?php
		require 'sim800_db/sim800credentials.php';
		/*
		if ($cred_db) {
			echo '<pre>'; 
			echo "cred_db: <br>";
			echo htmlspecialchars(print_r($cred_db, true));
			echo '</pre>';
		}*/
		
		
		$conn = db_connect($cred_db);
		read_db($conn);
		

		function db_connect(&$cred) { 				//Passing arguments by reference
			// Create connection
			$conn = new mysqli($cred['server'], $cred['user'], $cred['pwd'], $cred['db']); 

			// Check connection
			if ($conn->connect_error) die("Connection failed: " . $conn->connect_error); 
			// else echo "Connected successfully<br>";	

			return $conn;
		}

		function read_db(&$conn) {
			// Reading DB
			$sql = "SELECT ID, Registrator_ID, Location_ID, Created_date, Created_time, Data_in, Time_in, Data_out, Time_out, Distance FROM Trucks";
			$result = $conn->query($sql);			
			
			echo "<table class=\"tempData\" width=\"940\" style=\"overflow-y:auto;\">

			<tr>
			<th class=\"row-1 row-ID\">No</th> 
			<th class=\"row-1 row-Data\">Reg.</th> 
			<th class=\"row-1 row-Data\">Addr.</th> 
			<th class=\"row-1 row-Date\">CreatedD</th> 
			<th class=\"row-1 row-Time\">CreatedT</th> 
			<th class=\"row-1 row-Date\">Date In</th>
			<th class=\"row-1 row-Time\">Time In</th> 
			<th class=\"row-1 row-Date\">Date Out</th> 
			<th class=\"row-1 row-Time\">Time Out</th> 
			<th class=\"row-1 row-Data\">Dist.</th> 
			</tr>";
			
			if ($result->num_rows > 0) {
			  // output data of each row
			  while($row = $result->fetch_assoc()) {
				  
				echo "<tr>";
					echo "<td>" . $row["ID"] . "</td>";	echo "<td>" . $row["Registrator_ID"] . "</td>"; 	echo "<td>" . $row["Location_ID"] . "</td>";
					echo "<td>" . $row["Created_date"] . "</td>"; 	echo "<td>" . $row["Created_time"] . "</td>";		
					echo "<td>" . $row["Data_in"] . "</td>"; 		echo "<td>" . $row["Time_in"] . "</td>";		
					echo "<td>" . $row["Data_out"] . "</td>";		echo "<td>" . $row["Time_out"] . "</td>"; 
					echo "<td>" . $row["Distance"] . "</td>";
				echo "</tr>";
			  }
			} else {
			  echo "0 results";
			}
			echo "</table>";		
		}
		
		$result->free_result();
			
		$conn->close();	
	?>
	</div>
		   
    </body>
	
		<script type="text/javascript">
			
			window.onload = function() {  
				let t = setInterval(serverExchange, 500); 
			}
			
			function getHostTime() {
				var xhttp = new XMLHttpRequest();
				xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						document.getElementById("data").innerHTML = this.responseText;
					}
				};
				xhttp.open("GET", "host_time.php", true);  
				xhttp.send();
			}
			
			function getTruckData() {
				var xhttpT = new XMLHttpRequest();
				xhttpT.onreadystatechange = function() {
					
					if (this.readyState == 4 && this.status == 200) {
						timeDataParse(this.responseText); 
					}
				};
				xhttpT.open("GET", "sim800truck_data.txt", true);  
				xhttpT.send();
			} 
			
			function timeDataParse(InitialStr = "") {
				$Hrtc = dataParse(InitialStr, "$Hrtc");				// Time (H,M,S) from sim800 RTC, generated by sim800connect.php script
				$Mrtc = dataParse(InitialStr, "$Mrtc"); 
				$Srtc = dataParse(InitialStr, "$Srtc"); 
				
				$Htruck = dataParse(InitialStr, "$Htruck", 10);		// Time (H,M,S) where sim800 detected a truck (sim800connect.php script)
				$Mtruck = dataParse(InitialStr, "$Mtruck", 10); 
				$Struck = dataParse(InitialStr, "$Struck", 10); 
				
				$servT = dataParse(InitialStr, "$servT", 9, 8); // Time (H,M,S) where the server received data from sim800 (sim800connect.php script), 9 - offset of the data, 8 - data length
				
				document.getElementById("connect_time").innerHTML = "<h4>Server<br>Data received: " + $servT + "</h4>";		
				document.getElementById("sim800_Stime").innerHTML = "<h4>sim800 Modem<br>Data send: " + $Hrtc + ":" + $Mrtc + ":" + $Srtc + "</h4>";		
				document.getElementById("detect_time").innerHTML = "<h4>sim800 Core<br>Truck detected: " + $Htruck + ":" + $Mtruck + ":" + $Struck + "</h4>";					
			}
			
			function dataParse(InitialStr = "", searchStr = "", data1stPos = 8, dataLength = 2) {
				var pos = InitialStr.indexOf(searchStr) + data1stPos;
				return InitialStr.slice(pos, pos + dataLength);
			}
			
			function serverExchange() {
				getHostTime();
				getTruckData();
			}
			
		</script>
</html>

