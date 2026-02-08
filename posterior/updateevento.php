<?php
require_once "config.php";

if (!isset($_POST['id'])) {
    die("ID no recibido.");
}

$sql = "UPDATE eventos SET 
            title=:title,
            descripcion=:descripcion,
            start=:start,
            end=:end,
            categoria=:categoria,
            color=:color
        WHERE id=:id";

$stmt = $db->prepare($sql);

$stmt->execute([
    ':id' => $_POST['id'],
    ':title' => $_POST['title'],
    ':descripcion' => $_POST['descripcion'],
    ':start' => $_POST['start'],
    ':end' => $_POST['end'],
    ':categoria' => $_POST['categoria'],
    ':color' => $_POST['color']
]);

echo "OK"; 
?>