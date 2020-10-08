<!DOCTYPE html>
<html>
  <head>
    <title>sim800 connection</title>
  </head>
  <body>
  <p>
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
	
	$servT = date("H:i:s");			/* System time H, i, s */
	$servH = strval( date("H") ); 
	$servM = strval( date("i") );
	$servS = strval( date("s") );
	
	/*$servD = strval( date("j") ); 
	$servMo = strval( date("m") ); 
	$servY = strval( date("Y") ); */
	
	echo "System time: " . $servH . ":" . $servM . ":" . $servS . "<br>";
	/* echo "System date: " . $servD . "/" . $servMo . "/" . $servY . "<br>"; */
	
		
	$data_file = fopen ("sim800truck_data.txt", "w") or die("Unable to open file!");  
	$trucks[] = $servT; 
	$trucks_number = count($trucks);
		
	$data = "";
	$data .= "\$Hrtc = " . $Hrtc . ";\r\n";
	$data .= "\$Mrtc = " . $Mrtc . ";\r\n";
	$data .= "\$Srtc = " . $Srtc . ";\r\n";
	$data .= "\$Htruck = " . $Htruck . ";\r\n";
	$data .= "\$Mtruck = " . $Mtruck . ";\r\n";
	$data .= "\$Struck = " . $Struck . ";\r\n";
	$data .= "\$servT = " . $servT . ";\r\n";
	
	
	/* $data = "<?php\n" . $data . "?>"; */
	fwrite($data_file, $data);
	fclose($data_file);
	
	?>
  </p>
  </body>
</html>
