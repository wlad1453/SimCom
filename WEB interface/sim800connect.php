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
	
	$servT = date("H:i:s");			/* System time H, i, s */
	$servH = strval( date("H") ); 
	$servM = strval( date("i") );
	$servS = strval( date("s") );
	
	/*$servD = strval( date("j") ); 
	$servMo = strval( date("m") ); 
	$servY = strval( date("Y") ); */
	
	echo "Time:" . $servH . ":" . $servM . ":" . $servS /* . "<br>"*/ ;
	/* echo "System date: " . $servD . "/" . $servMo . "/" . $servY . "<br>"; */
	
		
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
	
	?>