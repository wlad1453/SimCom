<!DOCTYPE html>
  <?php       
	/* Default data 
	$Hrtc = 20; $Mrtc = 30; $Srtc = 45;     - sim800 rtc data
	$Htruck = 15; $Mtruck = 30; $Struck = 45;    - truck detection time */

	/* Check weather there are data in the client message which looks like:
		http://kot60.online/sim800/sim800setup.php?imei=1234567890&pwd=qwerty */  
	class sim800SetUpData {
		public $sysD;
		public $sysT;
		public $password;
		public $distMin;
		public $distMax;
		
		function __construct() {
			$this->sysD = "2020-01-01";
			$this->sysT = "12:13:14";
			$this->password = "wrong_pwd";
			$this->distMin = 201;
			$this->distMax = 501;
			// echo "Constructor: " . $this->password . " " . $this->distMin . " " . $this->distMax . " " . $this->sysD . " " . $this->sysT . "<br><br>";
		} 
		function __destruct() {
			// echo "SetUp data destroied";
		}
		function setTnD () {
			date_default_timezone_set("Europe/Moscow");  	
			$this->sysD = date ("Y-m-d");				/* System date Y-m-d */
			$this->sysT = date ("H:i:s");				/* System time H, i, s */		
		}
		function set_pwd($pwd) {
			$this->password = $pwd;
		}		
		function setDist($distMin, $distMax) {
			$this->distMin = $distMin;
			$this->distMax = $distMax;
		}		
		function get_dist() {			
		}		
	}
	
	if (!filter_has_var(INPUT_GET, "imei")) $imei = 123; 		else  $imei = $_GET['imei'];  	// Reading the imei code from the calling device
	if (!filter_has_var(INPUT_GET, "pwd")) 	$pwd = "wrong_pwd"; else  $pwd = $_GET['pwd'];		// pwd from the calling device
	
	$setUpData = new sim800SetUpData();
	
	require 'sim800_db/sim800credentials.php';
		/*
		if ($cred_db) {
			echo '<pre>'; 
			echo "cred_db: <br>";
			echo htmlspecialchars(print_r($cred_db, true));
			echo '</pre>';
		}*/		
		
	$conn = db_connect($cred_db);
	read_db($conn, $imei, $setUpData);		

	function db_connect(&$cred) { 				//Passing arguments by reference
		// Create connection
		$conn = new mysqli($cred['server'], $cred['user'], $cred['pwd'], $cred['db']); 

		// Check connection
		if ($conn->connect_error) die("Connection failed: " . $conn->connect_error); 
		// else echo "Connected successfully<br>";	

		return $conn;
	}	

	function read_db(&$conn, $imei, &$setUpData) {
		// Reading DB			
		$sql = "SELECT ID, password, dist_max, dist_min FROM Registrator WHERE imei = \"" . $imei . "\"";
		$result = $conn->query($sql);	
			
		if ($result->num_rows > 0) {			
			// while($row = $result->fetch_assoc()) {} 			if we do know that there are more then one row
			$row = $result->fetch_assoc();						// output data from one row				
			
			$setUpData->set_pwd( $row["password"] );
			$setUpData->setDist( $row["dist_min"], $row["dist_max"] );
					
			// echo "Registrator Nu. " . $row["ID"] . "  password: " . $row["password"] . "  DistMax: " . $row["dist_max"] . "  Dist_min: " . $row["dist_min"] . "<br><br>"; 			  
		} else {
			 echo "0 results<br>";
		}
	}
	
	if ( $pwd === $setUpData->password ) {
		
		$setUpData->setTnD();
		
		echo "Date: " . $setUpData->sysD . "<br>";
		echo "Time: " . $setUpData->sysT . "<br><br>";
		
		echo "DistMin: " . $setUpData->distMin . "<br>";
		echo "DistMax: " . $setUpData->distMax . "<br>";
	}	
?>
</html>