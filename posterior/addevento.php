<?php
require_once "config.php";

if (!isset($_POST['title']) || !isset($_POST['start'])) {
    die("Faltan datos obligatorios.");
}

$title = $_POST['title'];
$descripcion = $_POST['descripcion'] ?? '';
$start = $_POST['start'];
$end = $_POST['end'] ?? null;
$categoria = $_POST['categoria'] ?? '';
$color = $_POST['color'] ?? '#3788D8';

$sql = "INSERT INTO eventos (title, descripcion, start, end, categoria, color) 
        VALUES (:title, :descripcion, :start, :end, :categoria, :color)";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':title' => $title,
    ':descripcion' => $descripcion,
    ':start' => $start,
    ':end' => $end,
    ':categoria' => $categoria,
    ':color' => $color
]);

echo "OK";
?>