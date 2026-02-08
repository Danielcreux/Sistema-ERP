<?php
require_once "config.php";

header("Content-Type: application/json; charset=utf-8");

$query = $db->prepare("SELECT id, title, start, end, color FROM eventos");
$query->execute();

$eventos = $query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($eventos);
