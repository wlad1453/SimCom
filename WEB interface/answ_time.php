<!DOCTYPE html>
<html>
  <head>
    <title>sim800</title>
  </head>
  <body>
  <p>
  <?php   
    
		/*echo "filter_has_var: <br>";
		echo !filter_has_var(INPUT_GET, "Ut") . "<br>"; */
		
	/* Default data 
	$Hsim = 20; $Msim = 30; $Ssim = 45; 
	$Dsim = 9; $Mosim = 9; $Ysim = 2019; */

	/* Check weather there are data in the client message	*/  
	$data_consist = 0;
		
	if (!filter_has_var(INPUT_GET, "H")) 	$Hsim = 14; 		else  $Hsim = $_GET['H'];
	if (!filter_has_var(INPUT_GET, "M")) 	$Msim = 30; 		else  $Msim = $_GET['M'];
	if (!filter_has_var(INPUT_GET, "S")) 	$Ssim = 50; 		else  $Ssim = $_GET['S'];

	if (!filter_has_var(INPUT_GET, "D")) 	$Dsim = 14; 		else  $Dsim = $_GET['D'];
	if (!filter_has_var(INPUT_GET, "Mo")) 	$Mosim = 9; 		else  $Mosim = $_GET['Mo'];
	if (!filter_has_var(INPUT_GET, "Y")) 	$Ysim = 2020; 		else  $Ysim = $_GET['Y'];
	
	echo "Modem time: " . $Hsim . ":" . $Msim . ":" . $Ssim . "<br>";
	echo "Modem date: " . $Dsim . "/" . $Mosim . "/" . $Ysim . "<br>" . "<br>"; 
	
	
	date_default_timezone_set("Europe/Moscow");  
	
	$servT = date("H:i:s");			/* measurement time H, i, s */
	$servH = strval( date("H") ); 
	$servM = strval( date("i") );
	$servS = strval( date("s") );
	
	$servD = strval( date("j") ); 
	$servMo = strval( date("m") ); 
	$servY = strval( date("Y") ); 
	
	echo "System time: " . $servH . ":" . $servM . ":" . $servS . "<br>";
	echo "System date: " . $servD . "/" . $servMo . "/" . $servY . "<br>";
	
	$trucks = array();
	echo $trucks . "<br>";
	include 'trucks_data.txt';
	echo "Trucks array: <br>";
	print_r($trucks);
	
	echo "<br>";
	echo "There are " . count($trucks) . " trucks <br>"; 	
		
	$data_file = fopen ("trucks_data.txt", "w") or die("Unable to open file!");  
	$trucks[] = $servT; 
	$trucks_number = count($trucks);
	echo "Trucks array: <br>";
	print_r($trucks); echo "<br>";
	
	$data = "";
	if ( $trucks_number < 11 ) {
		for ( $i = 0; $i < count($trucks); $i++) {
			$data .= "\$trucks[] = \"" . $trucks[$i] . "\";\n";
			echo "Truck " . $i . " Time: " . $trucks[$i] . "<br>";
		}
	} else {
		$data = "\$trucks[] = \"" . $trucks[10] . "\";\n";
		echo "Truck 0 Time: " . $trucks[10] . "<br>";
	}
	$data = "<?php\n" . $data . "?>";
	fwrite($data_file, $data);
	fclose($data_file);
	
	?>
  </p>
  </body>
</html>