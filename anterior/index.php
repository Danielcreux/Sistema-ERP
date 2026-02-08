<?php 
session_start();
if(!isset($_SESSION['usuario'])){
    header("Location: ../anterior/iniciarsesion/index.php");
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <title>ERP</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="comun/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include "cabecera/index.php" ?>
    <?php include "listadodemodulos/index.php" ?>
    
    <script>
        // Función global para cerrar sesión
        function cerrarSesion() {
            if (confirm('¿Está seguro de que desea cerrar sesión?')) {
                window.location.href = '../posterior/cerrarsesion.php';
            }
        }
    </script>
</body>
</html>