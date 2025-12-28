<?php
require_once 'config.php';
requiereLogin();

$rutina_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($rutina_id == 0) {
    header('Location: mis_rutinas.php');
    exit();
}

$conn = getConnection();

// Obtener datos de la rutina
$stmt = $conn->prepare("SELECT * FROM rutinas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $rutina_id, $_SESSION['usuario_id']);
$stmt->execute();
$rutina = $stmt->get_result()->fetch_assoc();

if (!$rutina) {
    header('Location: mis_rutinas.php');
    exit();
}

// Obtener días de la rutina con ejercicios
$stmt = $conn->prepare("SELECT dr.*, COUNT(e.id) as num_ejercicios_actual
                        FROM dias_rutina dr
                        LEFT JOIN ejercicios e ON dr.id = e.dia_rutina_id
                        WHERE dr.rutina_id = ?
                        GROUP BY dr.id
                        ORDER BY FIELD(dr.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')");
$stmt->bind_param("i", $rutina_id);
$stmt->execute();
$dias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener todos los ejercicios por día
$ejercicios_por_dia = [];
foreach ($dias as $dia) {
    $stmt = $conn->prepare("SELECT * FROM ejercicios WHERE dia_rutina_id = ? ORDER BY orden");
    $stmt->bind_param("i", $dia['id']);
    $stmt->execute();
    $ejercicios_por_dia[$dia['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($rutina['nombre_rutina']); ?> - Ver Rutina</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .rutina-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .rutina-title {
            font-size: 32px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .rutina-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .meta-label {
            font-weight: bold;
            color: #667eea;
        }
        
        .dias-container {
            display: grid;
            gap: 20px;
        }
        
        .dia-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .dia-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .dia-nombre {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .dia-info {
            font-size: 14px;
            color: #666;
        }
        
        .ejercicios-list {
            display: grid;
            gap: 15px;
        }
        
        .ejercicio-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        
        .ejercicio-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .ejercicio-nombre {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .ejercicio-detalles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .ejercicio-detalle {
            font-size: 13px;
            color: #666;
        }
        
        .ejercicio-detalle strong {
            color: #667eea;
        }
        
        .ejercicio-acciones {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-registrar {
            background: #4caf50;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-registrar:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .no-ejercicios {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .btn-agregar-ejercicios {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.2s;
        }
        
        .btn-agregar-ejercicios:hover {
            transform: translateY(-2px);
        }
        
        .acciones-rutina {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-editar-rutina {
            background: #ffc107;
            color: #333;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-editar-rutina:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👁️ Ver Rutina</h1>
        <a href="mis_rutinas.php" class="btn-volver">← Volver a Mis Rutinas</a>
    </div>
    
    <div class="container">
        <!-- Header de la rutina -->
        <div class="rutina-header">
            <div class="rutina-title"><?php echo htmlspecialchars($rutina['nombre_rutina']); ?></div>
            
            <?php if ($rutina['descripcion']): ?>
                <p style="color: #666; margin-top: 10px; line-height: 1.6;">
                    <?php echo htmlspecialchars($rutina['descripcion']); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($rutina['descripcion_split']): ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <strong style="color: #667eea;">📋 Descripción del Split:</strong>
                    <p style="margin: 10px 0 0 0; color: #666;"><?php echo nl2br(htmlspecialchars($rutina['descripcion_split'])); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($rutina['video_explicativo']): ?>
                <div style="margin-top: 15px;">
                    <a href="<?php echo htmlspecialchars($rutina['video_explicativo']); ?>" target="_blank" style="color: #667eea; font-weight: bold;">
                        🎥 Ver Video Explicativo
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="rutina-meta">
                <div class="meta-item">
                    <span class="meta-label">📅 Días:</span>
                    <span><?php echo $rutina['num_dias_semana']; ?> días/semana</span>
                </div>
                
                <?php if (isset($rutina['nivel_experiencia'])): ?>
                <div class="meta-item">
                    <span class="meta-label">🎯 Nivel:</span>
                    <span><?php echo ucfirst($rutina['nivel_experiencia']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (isset($rutina['genero'])): ?>
                <div class="meta-item">
                    <span class="meta-label">⚧ Género:</span>
                    <span><?php echo ucfirst($rutina['genero']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="meta-item">
                    <span class="meta-label">📊 Tipo:</span>
                    <span><?php echo ucfirst($rutina['tipo_rutina'] ?? 'metodologica'); ?></span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">🌐 Estado:</span>
                    <span><?php echo $rutina['es_publico'] ? 'Pública' : 'Privada'; ?></span>
                </div>
            </div>
            
            <div class="acciones-rutina">
                <a href="agregar_ejercicios.php?rutina_id=<?php echo $rutina_id; ?>" class="btn-agregar-ejercicios">
                    ➕ Agregar/Editar Ejercicios
                </a>
                <a href="editar_rutina.php?id=<?php echo $rutina_id; ?>" class="btn-editar-rutina">
                    ✏️ Editar Rutina
                </a>
            </div>
        </div>
        
        <!-- Días y ejercicios -->
        <?php if (empty($dias)): ?>
            <div class="card">
                <div class="no-ejercicios">
                    <h3>No hay días configurados</h3>
                    <p>Esta rutina aún no tiene días de entrenamiento</p>
                </div>
            </div>
        <?php else: ?>
            <div class="dias-container">
                <?php foreach ($dias as $dia): ?>
                    <div class="dia-card">
                        <div class="dia-header">
                            <div>
                                <div class="dia-nombre">📅 <?php echo $dia['dia_semana']; ?></div>
                                <?php if ($dia['grupos_musculares']): ?>
                                    <div class="dia-info">
                                        💪 Grupos: <?php echo htmlspecialchars($dia['grupos_musculares']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <a href="entrenar_dia.php?rutina_id=<?php echo $rutina_id; ?>&dia=<?php echo urlencode($dia['dia_semana']); ?>" 
                                   style="background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block; margin-bottom: 10px;">
                                    ▶️ Entrenar
                                </a>
                                <div style="font-size: 14px; color: #666;">
                                    <?php echo $dia['num_ejercicios_actual']; ?> / <?php echo $dia['num_ejercicios']; ?> ejercicios
                                </div>
                                <?php if ($dia['num_ejercicios_actual'] < $dia['num_ejercicios']): ?>
                                    <a href="agregar_ejercicios.php?rutina_id=<?php echo $rutina_id; ?>" 
                                       style="color: #ff9800; font-size: 13px; font-weight: bold;">
                                        ⚠️ Faltan ejercicios
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (empty($ejercicios_por_dia[$dia['id']])): ?>
                            <div class="no-ejercicios">
                                <p>No hay ejercicios agregados para este día</p>
                                <a href="agregar_ejercicios.php?rutina_id=<?php echo $rutina_id; ?>" class="btn-agregar-ejercicios">
                                    ➕ Agregar Ejercicios
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="ejercicios-list">
                                <?php foreach ($ejercicios_por_dia[$dia['id']] as $ejercicio): ?>
                                    <div class="ejercicio-item">
                                        <div class="ejercicio-nombre">
                                            <?php echo $ejercicio['orden']; ?>. <?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?>
                                        </div>
                                        
                                        <?php if ($ejercicio['objetivo_serie']): ?>
                                            <div style="color: #666; font-size: 14px; margin-bottom: 10px;">
                                                🎯 <?php echo htmlspecialchars($ejercicio['objetivo_serie']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="ejercicio-detalles">
                                            <div class="ejercicio-detalle">
                                                <strong>Series:</strong> <?php echo $ejercicio['num_series']; ?>
                                            </div>
                                            <div class="ejercicio-detalle">
                                                <strong>Sesiones:</strong> <?php echo $ejercicio['num_sesiones']; ?>
                                            </div>
                                            <div class="ejercicio-detalle">
                                                <strong>Descanso:</strong> <?php echo $ejercicio['descanso_minutos']; ?>'<?php echo $ejercicio['descanso_segundos'] > 0 ? ' ' . $ejercicio['descanso_segundos'] . '"' : ''; ?>
                                            </div>
                                            <?php if ($ejercicio['rir_rpe']): ?>
                                            <div class="ejercicio-detalle">
                                                <strong>RIR/RPE:</strong> <?php echo htmlspecialchars($ejercicio['rir_rpe']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="ejercicio-acciones">
                                            <a href="registrar_sesion.php?ejercicio_id=<?php echo $ejercicio['id']; ?>" class="btn-registrar">
                                                📊 Registrar Sesión
                                            </a>
                                            <?php if ($ejercicio['imagen_url']): ?>
                                                <a href="<?php echo htmlspecialchars($ejercicio['imagen_url']); ?>" target="_blank" 
                                                   style="color: #667eea; font-size: 14px; font-weight: bold;">
                                                    🖼️ Ver Imagen
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($ejercicio['video_url']): ?>
                                                <a href="<?php echo htmlspecialchars($ejercicio['video_url']); ?>" target="_blank" 
                                                   style="color: #667eea; font-size: 14px; font-weight: bold;">
                                                    🎥 Ver Video
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
