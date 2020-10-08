<?php

date_default_timezone_set("Europe/Moscow");  
	
	$measT = date("H:i:s");			/* measurement time H, i, s */
	/* $mTH = strval( date("H") ); 
	$mTM = strval( date("i") );
	$mTS = strval( date("s") ); */

?>

<h4> Server Time: <?php echo $measT . "<br>"; 
    echo "Unix: " . microtime(TRUE); ?> </h4>

