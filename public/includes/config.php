<?php
// includes/config.php

$host = "localhost";
$port = "5432";        // default PostgreSQL port
$dbname = "drivemint";
$user = "postgres";    // change as needed
$password = "2003";    // change as needed

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Database connection failed.");
}
