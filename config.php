<?php
$host = "localhost";
$user = "root";
$pass = "";
$database = "simbles";   // nama database

$conn = mysqli_connect($host, $user, $pass, $database);

if(!$conn){
    die("Koneksi gagal: " . mysqli_connect_error());
}

?>
