<?php

$host = "sql313.infinityfree.com";
$username = "if0_42246607";
$password = "R8fGPGyKllTY4Wt";
$dbname = "if0_42246607_tubes";

$koneksi = new mysqli($host, $username, $password, $dbname);
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
?>