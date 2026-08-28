<?php
session_start();

$host = "localhost";
$user = "root";
$password = ""; // par défaut, XAMPP n'a pas de mot de passe root
$dbname = "etap_consommation";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}
?>
