<?php
require "config.php";
header("Content-Type: application/json");

$numero = isset($_GET["numero"]) ? trim($_GET["numero"]) : "";

if ($numero === "") {
    echo json_encode(["trouve" => false]);
    exit;
}

$stmt = $conn->prepare("SELECT id_compteur, numero, type, id_local FROM compteur WHERE numero = ?");
$stmt->bind_param("s", $numero);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $compteur = $result->fetch_assoc();
    echo json_encode(["trouve" => true, "compteur" => $compteur]);
} else {
    echo json_encode(["trouve" => false]);
}
?>
