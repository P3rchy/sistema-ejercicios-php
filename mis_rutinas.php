<?php
require_once 'config.php';
requiereLogin();

$conn = getConnection();

// Obtener rutinas del usuario
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT r.*, 
                        COUNT(DISTINCT dr.id) as total_dias,
                        COUNT(DISTINCT e.id) as total_ejercicios
                        FROM rutinas r
                        LEFT JOIN dias_rutina dr ON r.id = dr.rutina_id
                        LEFT JOIN ejercicios e ON dr.id = e.dia_rutina_id
                        WHERE r.usuario_id = ?
                        GROUP BY r.id
                        ORDER BY r.fecha_creacion DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$rutinas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Rutinas - Sistema de Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .rutinas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .rutina-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .rutina-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .rutina-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .rutina-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 0;
            flex: 1;
        }
        
        .rutina-tipo-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .tipo-metodologica {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .tipo-basica {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .rutina-descripcion {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
            line-height: 1.5;
        }
        
        .rutina-stats {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .stat-number {
            font-weight: bold;
            color: #667eea;
        }
        
        .rutina-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
        }
        
        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 15px;
            font-size: 12px;
            color: #555;
        }
        
        .rutina-fecha {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        
        .rutina-acciones {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-accion {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        
        .btn-ver {
            background: #667eea;
            color: white;
        }
        
        .btn-ver:hover {
            background: #5568d3;
        }
        
        .btn-editar {
            background: #ffc107;
            color: #333;
        }
        
        .btn-editar:hover {
            background: #e0a800;
        }
        
        .btn-eliminar {
            background: #dc3545;
            color: white;
        }
        
        .btn-eliminar:hover {
            background: #c82333;
        }
        
        .rutina-publica {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4caf50;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .calificacion-stars {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }
        
        .stars {
            color: #ffc107;
            font-size: 16px;
        }
        
        .votos {
            font-size: 12px;
            color: #666;
        }
        
        .no-rutinas {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-rutinas-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .no-rutinas h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .no-rutinas p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .filtros-rutinas {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filtros-grupo {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filtro-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filtro-item label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .filtro-item select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📋 Mis Rutinas</h1>
        <a href="index.php" class="btn-volver">← Volver al Inicio</a>
    </div>
    
    <div class="container">
        <!-- Header con botón crear -->
        <div class="card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="margin-bottom: 10px;">Mis Rutinas de Entrenamiento</h2>
                    <p class="subtitle">Gestiona todas tus rutinas creadas</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="crear_rutina.php" class="btn-primary">
                        ➕ Nueva Rutina Metodológica
                    </a>
                    <a href="crear_rutina_basica.php" class="btn-primary" style="background: #7b1fa2;">
                        ➕ Nueva Rutina Básica
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filtros-rutinas">
            <div class="filtros-grupo">
                <span style="font-weight: bold; color: #333;">🔍 Filtrar:</span>
                
                <div class="filtro-item">
                    <label>Tipo:</label>
                    <select id="filtroTipo" onchange="filtrarRutinas()">
                        <option value="todas">Todas</option>
                        <option value="metodologica">Metodológicas</option>
                        <option value="basica">Básicas</option>
                    </select>
                </div>
                
                <div class="filtro-item">
                    <label>Nivel:</label>
                    <select id="filtroNivel" onchange="filtrarRutinas()">
                        <option value="todas">Todos</option>
                        <option value="principiante">Principiante</option>
                        <option value="intermedio">Intermedio</option>
                        <option value="avanzado">Avanzado</option>
                    </select>
                </div>
                
                <div class="filtro-item">
                    <label>Género:</label>
                    <select id="filtroGenero" onchange="filtrarRutinas()">
                        <option value="todas">Todos</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                        <option value="unisex">Unisex</option>
                    </select>
                </div>
            </div>
        </div>
        
        <?php if (empty($rutinas)): ?>
            <!-- No hay rutinas -->
            <div class="no-rutinas">
                <div class="no-rutinas-icon">📝</div>
                <h3>No tienes rutinas creadas</h3>
                <p>Crea tu primera rutina para comenzar a entrenar</p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <a href="crear_rutina.php" class="btn-primary">
                        ➕ Crear Rutina Metodológica
                    </a>
                    <a href="crear_rutina_basica.php" class="btn-primary" style="background: #7b1fa2;">
                        ➕ Crear Rutina Básica
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Grid de rutinas -->
            <div class="rutinas-grid">
                <?php foreach ($rutinas as $rutina): ?>
                    <div class="rutina-card" 
                         data-tipo="<?php echo htmlspecialchars($rutina['tipo_rutina'] ?? 'metodologica'); ?>"
                         data-nivel="<?php echo htmlspecialchars($rutina['nivel_experiencia'] ?? 'principiante'); ?>"
                         data-genero="<?php echo htmlspecialchars($rutina['genero'] ?? 'unisex'); ?>">
                        
                        <?php if ($rutina['es_publico']): ?>
                            <div class="rutina-publica">🌐 PÚBLICA</div>
                        <?php endif; ?>
                        
                        <div class="rutina-header">
                            <div style="flex: 1;">
                                <h3 class="rutina-title">
                                    <?php echo htmlspecialchars($rutina['nombre_rutina']); ?>
                                </h3>
                                <span class="rutina-tipo-badge tipo-<?php echo $rutina['tipo_rutina'] ?? 'metodologica'; ?>">
                                    <?php 
                                    $tipo = $rutina['tipo_rutina'] ?? 'metodologica';
                                    echo $tipo == 'metodologica' ? '📊 Metodológica' : '📸 Básica'; 
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($rutina['descripcion']): ?>
                            <p class="rutina-descripcion">
                                <?php echo htmlspecialchars(substr($rutina['descripcion'], 0, 100)); ?>
                                <?php echo strlen($rutina['descripcion']) > 100 ? '...' : ''; ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="rutina-stats">
                            <div class="stat-item">
                                <span>📅</span>
                                <span class="stat-number"><?php echo $rutina['total_dias']; ?></span>
                                <span>días</span>
                            </div>
                            <div class="stat-item">
                                <span>💪</span>
                                <span class="stat-number"><?php echo $rutina['total_ejercicios']; ?></span>
                                <span>ejercicios</span>
                            </div>
                        </div>
                        
                        <div class="rutina-meta">
                            <?php if (isset($rutina['nivel_experiencia'])): ?>
                                <span class="meta-badge">
                                    🎯 <?php echo ucfirst($rutina['nivel_experiencia']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (isset($rutina['genero'])): ?>
                                <span class="meta-badge">
                                    <?php 
                                    $iconos = ['masculino' => '♂️', 'femenino' => '♀️', 'unisex' => '⚧'];
                                    echo $iconos[$rutina['genero']] ?? '⚧';
                                    ?>
                                    <?php echo ucfirst($rutina['genero']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($rutina['es_publico'] && $rutina['total_votos'] > 0): ?>
                            <div class="calificacion-stars">
                                <span class="stars">
                                    <?php
                                    $rating = floatval($rutina['calificacion_promedio']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '⭐' : '☆';
                                    }
                                    ?>
                                </span>
                                <span class="votos">(<?php echo $rutina['total_votos']; ?> votos)</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="rutina-fecha">
                            Creada: <?php echo date('d/m/Y', strtotime($rutina['fecha_creacion'])); ?>
                        </div>
                        
                        <div class="rutina-acciones">
                            <?php 
                            $tipo_rutina = $rutina['tipo_rutina'] ?? 'metodologica';
                            $ver_url = $tipo_rutina == 'basica' ? 'ver_rutina_basica.php' : 'ver_rutina.php';
                            $editar_url = $tipo_rutina == 'basica' ? 'editar_rutina_basica.php' : 'editar_rutina.php';
                            ?>
                            <a href="<?php echo $ver_url; ?>?id=<?php echo $rutina['id']; ?>" class="btn-accion btn-ver">
                                👁️ Ver
                            </a>
                            <a href="<?php echo $editar_url; ?>?id=<?php echo $rutina['id']; ?>" class="btn-accion btn-editar">
                                ✏️ Editar
                            </a>
                            <button onclick="confirmarEliminar(<?php echo $rutina['id']; ?>, '<?php echo htmlspecialchars($rutina['nombre_rutina']); ?>')" 
                                    class="btn-accion btn-eliminar">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function filtrarRutinas() {
            const tipo = document.getElementById('filtroTipo').value;
            const nivel = document.getElementById('filtroNivel').value;
            const genero = document.getElementById('filtroGenero').value;
            
            const cards = document.querySelectorAll('.rutina-card');
            
            cards.forEach(card => {
                const cardTipo = card.getAttribute('data-tipo');
                const cardNivel = card.getAttribute('data-nivel');
                const cardGenero = card.getAttribute('data-genero');
                
                const matchTipo = tipo === 'todas' || cardTipo === tipo;
                const matchNivel = nivel === 'todas' || cardNivel === nivel;
                const matchGenero = genero === 'todas' || cardGenero === genero;
                
                if (matchTipo && matchNivel && matchGenero) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function confirmarEliminar(id, nombre) {
            if (confirm(`¿Estás seguro de eliminar la rutina "${nombre}"?\n\nEsta acción no se puede deshacer y eliminará todos los ejercicios asociados.`)) {
                window.location.href = 'eliminar_rutina.php?id=' + id;
            }
        }
    </script>
</body>
</html>
