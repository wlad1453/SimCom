<!DOCTYPE html>
  <?php   
    
	/* Default data 
	$Hrtc = 20; $Mrtc = 30; $Srtc = 45;     - sim800 rtc data
	$Htruck = 15; $Mtruck = 30; $Struck = 45;    - truck detection time */

	/* Check weather there are data in the client message which looks like:
		kot60.online/sim800/sim800connect.php?H=09&M=10&S=11&Ht=21&Mt=50&St=55*/  
	$data_consist = 0;
		
	if (!filter_has_var(INPUT_GET, "H")) 	$Hrtc = 12; 		else  $Hrtc = $_GET['H'];  
	if (!filter_has_var(INPUT_GET, "M")) 	$Mrtc = 13; 		else  $Mrtc = $_GET['M'];
	if (!filter_has_var(INPUT_GET, "S")) 	$Srtc = 14; 		else  $Srtc = $_GET['S'];

	if (!filter_has_var(INPUT_GET, "Ht")) 	$Htruck = 15; 		else  $Htruck = $_GET['Ht'];
	if (!filter_has_var(INPUT_GET, "Mt")) 	$Mtruck = 30; 		else  $Mtruck = $_GET['Mt'];
	if (!filter_has_var(INPUT_GET, "St")) 	$Struck = 45; 		else  $Struck = $_GET['St'];
	
	/* echo "Modem time: " . $Hsim . ":" . $Msim . ":" . $Ssim . "<br>";
	echo "Modem date: " . $Dsim . "/" . $Mosim . "/" . $Ysim . "<br>" . "<br>"; */	
	
	date_default_timezone_set("Europe/Moscow");  
	
	$servD = date("Y-m-d");			/* System date Y-m-d */
	$servT = date("H:i:s");			/* System time H, i, s */
	$servH = strval( date("H") ); 
	$servM = strval( date("i") );
	$servS = strval( date("s") );
	
	/*$servD = strval( date("j") ); 
	$servMo = strval( date("m") ); 
	$servY = strval( date("Y") ); */
	
	echo "Time:" . $servH . ":" . $servM . ":" . $servS . "<br>" ;
	/* echo "System date: " . $servD . "/" . $servMo . "/" . $servY . "<br>"; */
	echo "Date:" . $servD . "<br>";
	
	//  DB_entry($servD, $servT, $Htruck, $Mtruck, $Struck, false);
		
	$data_file = fopen ("sim800truck_data.txt", "w") or die("Unable to open file!");  
	$trucks[] = $servT; 
	$trucks_number = count($trucks);
		
	$data = "";
	
	if ( $Hrtc < 10 ) $data .= "\$Hrtc = " . "0" . $Hrtc . ";\r\n"; else $data .= "\$Hrtc = " . $Hrtc . ";\r\n";
	if ( $Mrtc < 10 ) $data .= "\$Mrtc = " . "0" . $Mrtc . ";\r\n"; else $data .= "\$Mrtc = " . $Mrtc . ";\r\n";
	if ( $Srtc < 10 ) $data .= "\$Srtc = " . "0" . $Srtc . ";\r\n"; else $data .= "\$Srtc = " . $Srtc . ";\r\n";
	
	if ( $Htruck < 10 ) $data .= "\$Htruck = " . "0" . $Htruck . ";\r\n"; else $data .= "\$Htruck = " . $Htruck . ";\r\n";
	if ( $Mtruck < 10 ) $data .= "\$Mtruck = " . "0" . $Mtruck . ";\r\n"; else $data .= "\$Mtruck = " . $Mtruck . ";\r\n";
	if ( $Struck < 10 ) $data .= "\$Struck = " . "0" . $Struck . ";\r\n"; else $data .= "\$Struck = " . $Struck . ";\r\n";
	/*
	$data .= "\$Hrtc = " . $Hrtc . ";\r\n";
	$data .= "\$Mrtc = " . $Mrtc . ";\r\n";
	$data .= "\$Srtc = " . $Srtc . ";\r\n";
	$data .= "\$Htruck = " . $Htruck . ";\r\n";
	$data .= "\$Mtruck = " . $Mtruck . ";\r\n";
	$data .= "\$Struck = " . $Struck . ";\r\n";*/ 
	
	$data .= "\$servT = " . $servT . ";\r\n";
	
	
	/* $data = "<?php\n" . $data . "?>"; */
	fwrite($data_file, $data);
	fclose($data_file);
	
	
	/* function DB_entry($sDate = "2000-01-02", $sTime = "12:13:14", $Htruck = 01, $Mtruck  = 02, $Struck = 03, $occupied = false) { */
		require 'sim800_db/sim800credentials.php';
		/*
		if ($cred_db) {
			echo '<pre>'; 
			echo "cred_db: <br>";
			echo htmlspecialchars(print_r($cred_db, true));
			echo '</pre>';
		}*/
		// $conn = db_connect($cred_db);
		
		$conn = new mysqli($cred_db['server'], $cred_db['user'], $cred_db['pwd'], $cred_db['db']); 
			// Check connection
			if ($conn->connect_error) die("Connection failed: " . $conn->connect_error); 
			else echo "Connected successfully<br>";	
			
		$sql = "SELECT MAX(ID) AS max_id FROM Trucks";  // The number of the last row in the table
		$result = $conn->query($sql);
			
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$last_id = $row["max_id"];
			echo "Determined last existing entry (last_id): " . $last_id . "<br><br>";
			$result -> free_result();
		} else {
		  echo "0 results<br>";
		}					
		
		// $result -> free_result();
			
		$sql = "SELECT Time_in, Time_out FROM Trucks WHERE ID = " . $last_id . ""; // Last entry
		$result = $conn->query($sql);
			
		if ($result->num_rows > 0) {
			
			 while($row = $result->fetch_assoc()) {				
				echo "Last entry: " . $last_id . " Time in: " . $row["Time_in"] . " Time out: " . $row["Time_out"] . " end<br>";	
				echo '<pre>' . "Result:<br>";
				echo htmlspecialchars(print_r($row, true));
				echo '</pre>';
				
				echo "Time_out: " . $row["Time_out"] . " Time in: " . $row["Time_in"] . "<br><br>";				
			}
			$result -> free_result();
		} else {
			echo "0 results<br>";
		}
		
		if ($row["Time_out"] == NULL) {
			
			// $last_id = $conn->insert_id;
			echo " Last inserted ID is: " . $last_id . "<br><br>";
			
			$sql = "UPDATE `Trucks` SET `Data_out`= \"" . $servD . "\" ,`Time_out`=\"" . $servT . "\",`Distance`= 155 WHERE ID = " . $last_id . "";
			
			if ($conn->query($sql) === TRUE) {
			  $last_id = $conn->insert_id;
			  echo "Date and time are added to the existing entry. Last inserted ID is: " . $last_id;
			} else {
			  echo "Error: " . $sql . "<br>" . $conn->error;
			}
		} else {		
			$sql = "INSERT INTO `Trucks`(`Registrator_ID`, `Location_ID`, `Created_date`, `Created_time`, `Data_in`, `Time_in`, `Data_out`, `Time_out`, `Distance`) 
			VALUES (1,1,\"" . $servD . "\",\"" . $servT . "\",\"" . $servD . "\",\"" . $Htruck . ":" . $Mtruck . ":" . $Struck . "\"," .  "\"\"" . "," . "\"\"" . "," . 123 . ")";	/* `ID`,    "\"2000-00-00\"" .  "\"00:00:00\"" .   */
			echo "sql: " . $sql . "<br>";		
			
			if ($conn->query($sql) === TRUE) {
			  $last_id = $conn->insert_id;
			  echo "New record created successfully. Last inserted ID is: " . $last_id;
			} else {
			  echo "Error: " . $sql . "<br>" . $conn->error;
			}
		}
		
		if ($result) {
		echo '<pre>' . "Result:<br>";
		echo htmlspecialchars(print_r($result, true));
		echo '</pre>';
	}
		/*
		function read_db(&$conn) {
			// Reading DB
			$sql = "SELECT ID, Registrator_ID, Location_ID, Created_date, Created_time, Data_in, Time_in, Data_out, Time_out, Distance FROM Trucks";
						
			
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
		}*/
		// $result -> free_result();
			
		$conn->close();	
	// }
	
	function db_connect(&$cred) { 				//Passing arguments by reference
			// Create connection
			$conn = new mysqli($cred['server'], $cred['user'], $cred['pwd'], $cred['db']); 
			// Check connection
			if ($conn->connect_error) die("Connection failed: " . $conn->connect_error); 
			else echo "Connected successfully<br>";	
			echo "db_connect conn: ";
			echo $conn . "end conn <br>";
			return $conn;
	}

	
	?>