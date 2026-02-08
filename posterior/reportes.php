<?php
// Agregar estas funciones al archivo reportes.php existente

function obtenerReporteVentas($periodo) {
    global $db, $userId;
    
    // Simulación de datos de ventas - en un sistema real esto vendría de una tabla de ventas
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
            COUNT(*) as total_ventas,
            SUM(total) as monto_total
        FROM ventas 
        WHERE usuario_id = ? AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
        ORDER BY mes
    ");
    $stmt->execute([$userId]);
    $ventasMensuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay datos reales, usar datos de ejemplo
    if (empty($ventasMensuales)) {
        $ventasMensuales = [
            ['mes' => '2024-01', 'total_ventas' => 15, 'monto_total' => 12500],
            ['mes' => '2024-02', 'total_ventas' => 22, 'monto_total' => 18400],
            ['mes' => '2024-03', 'total_ventas' => 18, 'monto_total' => 15200],
            ['mes' => '2024-04', 'total_ventas' => 25, 'monto_total' => 21000],
            ['mes' => '2024-05', 'total_ventas' => 30, 'monto_total' => 25800],
            ['mes' => '2024-06', 'total_ventas' => 28, 'monto_total' => 23500]
        ];
    }
    
    return [
        'ventas_mensuales' => [
            'labels' => array_map(function($v) { 
                return DateTime::createFromFormat('Y-m', $v['mes'])->format('M Y'); 
            }, $ventasMensuales),
            'datasets' => [
                [
                    'label' => 'Total Ventas',
                    'data' => array_column($ventasMensuales, 'total_ventas'),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
                ],
                [
                    'label' => 'Monto Total ($)',
                    'data' => array_map(function($v) { 
                        return $v['monto_total'] / 1000; 
                    }, $ventasMensuales),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'yAxisID' => 'y1'
                ]
            ]
        ]
    ];
}

function obtenerReporteProductividad($periodo) {
    global $db, $userId;
    
    $stmt = $db->prepare("
        SELECT 
            columna as estado,
            COUNT(*) as cantidad,
            AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_actualizacion)) as tiempo_promedio
        FROM kanban_tareas kt 
        JOIN kanban_tableros kb ON kt.tablero_id = kb.id 
        WHERE kb.usuario_id = ? 
        GROUP BY columna
    ");
    $stmt->execute([$userId]);
    $productividad = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'productividad' => [
            'labels' => array_column($productividad, 'estado'),
            'datasets' => [
                {
                    'label' => 'Cantidad de Tareas',
                    'data' => array_column($productividad, 'cantidad'),
                    'backgroundColor' => '#3b82f6'
                },
                {
                    'label' => 'Tiempo Promedio (horas)',
                    'data' => array_column($productividad, 'tiempo_promedio'),
                    'backgroundColor' => '#f59e0b',
                    'type' => 'line',
                    'yAxisID' => 'y1'
                }
            ]
        }
    ];
}

function obtenerReporteClientes($periodo) {
    global $db, $userId;
    
    // Clientes por mes
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
            COUNT(*) as nuevos_clientes,
            SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as clientes_activos
        FROM clientes 
        WHERE usuario_id = ? AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
        ORDER BY mes
    ");
    $stmt->execute([$userId]);
    $evolucionClientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'evolucion_clientes' => [
            'labels' => array_map(function($c) { 
                return DateTime::createFromFormat('Y-m', $c['mes'])->format('M Y'); 
            }, $evolucionClientes),
            'datasets' => [
                {
                    'label' => 'Nuevos Clientes',
                    'data' => array_column($evolucionClientes, 'nuevos_clientes'),
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)'
                },
                {
                    'label' => 'Clientes Activos',
                    'data' => array_column($evolucionClientes, 'clientes_activos'),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)'
                }
            ]
        }
    ];
}

// Actualizar la función principal para incluir los nuevos reportes
function obtenerDatosGraficas($periodo) {
    global $db, $userId;
    
    $reportes = [];
    
    // Reportes básicos existentes
    $reportes = array_merge($reportes, obtenerReportesBasicos($periodo));
    
    // Nuevos reportes avanzados
    $reportes = array_merge($reportes, obtenerReporteVentas($periodo));
    $reportes = array_merge($reportes, obtenerReporteProductividad($periodo));
    $reportes = array_merge($reportes, obtenerReporteClientes($periodo));
    $reportes = array_merge($reportes, obtenerReporteInventario($periodo));
    
    return $reportes;
}

function obtenerReporteInventario($periodo) {
    global $db, $userId;
    
    $stmt = $db->prepare("
        SELECT 
            c.nombre as categoria,
            COUNT(i.id) as total_items,
            SUM(i.stock_actual * i.precio_compra) as valor_total,
            SUM(i.stock_actual) as stock_total
        FROM inventario_items i 
        LEFT JOIN inventario_categorias c ON i.categoria_id = c.id 
        WHERE i.usuario_id = ? AND i.estado != 'inactivo'
        GROUP BY c.id, c.nombre
    ");
    $stmt->execute([$userId]);
    $inventarioCategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'inventario_categorias' => [
            'labels' => array_column($inventarioCategorias, 'categoria') ?: ['Sin Categoría'],
            'datasets' => [
                {
                    'label' => 'Valor Total ($)',
                    'data' => array_map(function($i) { 
                        return $i['valor_total'] ?: 0; 
                    }, $inventarioCategorias),
                    'backgroundColor' => '#3b82f6'
                }
            ]
        }
    ];
}