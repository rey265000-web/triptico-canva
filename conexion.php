<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "db_estadias";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Conexion fallida: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>