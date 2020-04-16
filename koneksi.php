<?php
$db_server = "localhost"; //ganti sesuai server Anda
$db_username = ""; //ganti sesuai username Anda
$db_password = ""; //ganti sesuai password Anda
$db_name = ""; //ganti sesuatu nama database Anda
$mysqli = new mysqli($db_server,$db_username,$db_password,$db_name) or die("Koneksi gagal");
?>
