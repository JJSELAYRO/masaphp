<?php

$host = "localhost";
$username = "root";
$password = "";
$dbname = "crud_blk2";

$conn = mysqli_connect($host,$username,$password,$dbname);

if(!$conn){

	die("Connection Error: ". mysqli_connect_error());

}

?>
     