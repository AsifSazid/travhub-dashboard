 <?php
$servername = "localhost";
$username = "root";
$password = "travhub2025";
$dbname = "travhub_dashboard";

// Create connection
$conn = new mysqli($servername, $username, $password,$dbname);
	$conn-> select_db($dbname) or die("Couldn'ttt select DB");
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

/*
if  (mysqli_connect($servername, $username, $password,$dbname))
{


	echo "2nd connection ok";
}
*/
//echo "Connected successfully";


/*
$sql = "SELECT * FROM login Where name= '$id' OR email = '$id' AND password = '$pass' " ;


 
$result = $conn->query($sql);

if ( $conn->query("SELECT * FROM login Where name= '$id' OR email = '$id' AND password = '$pass' "))
{
    echo $result;
    echo "done";
}
else {
    
    
    echo "error";
    
}


*/


?> 