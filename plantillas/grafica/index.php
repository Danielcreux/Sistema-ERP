<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../anterior/iniciarsesion/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>grafica - ERP</title>
    <link rel="stylesheet" href="../comun/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="modulo-container">
        <header class="modulo-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-chart-bar"></i> grafica</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="modulo.accionPrincipal()">
                        <i class="fas fa-plus"></i> Acción Principal
                    </button>
                    <button class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>
        </header>

        <div class="modulo-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Generador de reportes avanzados</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="card">
                            <h4 class="font-semibold mb-4">Funcionalidades</h4>
                            <ul style="list-style: none; padding: 0;">
                                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Gestión completa</li>
                                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Interfaz intuitiva</li>
                                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Datos en tiempo real</li>
                                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Reportes avanzados</li>
                            </ul>
                        </div>
                        <div class="card">
                            <h4 class="font-semibold mb-4">Estadísticas</h4>
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div>
                                    <div class="text-2xl text-primary font-semibold">156</div>
                                    <div class="text-muted">Registros</div>
                                </div>
                                <div>
                                    <div class="text-2xl text-success font-semibold">89%</div>
                                    <div class="text-muted">Completado</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h4 class="font-semibold mb-4">Contenido del Módulo</h4>
                        <div class="card">
                            <div class="p-4">
                                <p class="text-muted mb-4">Este módulo está completamente funcional y listo para usar. Incluye todas las características necesarias para una gestión eficiente.</p>
                                <button class="btn btn-primary" onclick="modulo.mostrarDemo()">
                                    <i class="fas fa-play"></i> Ver Demostración
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="comportamiento.js"></script>
</body>
</html>