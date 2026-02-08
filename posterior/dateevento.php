<?php
require_once "config.php";

if (!isset($_POST['id'])) {
    die("No se recibió ID.");
}

$stmt = $db->prepare("DELETE FROM eventos WHERE id=:id");
$stmt->execute([':id' => $_POST['id']]);

echo "OK";
