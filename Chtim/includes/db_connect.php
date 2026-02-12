<?php
$servername = "localhost";
$username = "admin_chtim";
$password = "password";
$dbname = "chtim_db";

// Création de la connexion
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Vérification
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
