<?php
require_once 'config.php';
requiereLogin();

$rutina_id = isset($_GET['rutina_id']) ? intval($_GET['rutina_id']) : 0;
$dia_semana = isset($_GET['dia']) ? $_GET['dia'] : '';

if ($rutina_id == 0 || empty($dia_semana)) {
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

// Obtener el día específico
$stmt = $conn->prepare("SELECT * FROM dias_rutina WHERE rutina_id = ? AND dia_semana = ?");
$stmt->bind_param("is", $rutina_id, $dia_semana);
$stmt->execute();
$dia = $stmt->get_result()->fetch_assoc();

if (!$dia) {
    header('Location: ver_rutina.php?id=' . $rutina_id);
    exit();
}

// Obtener ejercicios del día
$stmt = $conn->prepare("SELECT * FROM ejercicios WHERE dia_rutina_id = ? ORDER BY orden");
$stmt->bind_param("i", $dia['id']);
$stmt->execute();
$ejercicios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dia_semana; ?> - <?php echo htmlspecialchars($rutina['nombre_rutina']); ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            padding: 0;
            margin: 0;
        }
        
        .header-dia {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .header-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .dia-title {
            font-size: 24px;
            font-weight: bold;
        }
        
        .btn-volver-header {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }
        
        .rutina-nombre {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .progreso-dia {
            margin-top: 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 13px;
        }
        
        .ejercicios-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .ejercicio-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .ejercicio-header {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 20px;
            border-left: 5px solid #667eea;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        
        .ejercicio-imagen-mini {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #667eea;
            flex-shrink: 0;
        }
        
        .ejercicio-header-content {
            flex: 1;
        }
        
        .toast-message {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 15px 25px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 10000;
            display: none;
            animation: slideUp 0.3s ease;
        }
        
        .toast-message.show {
            display: block;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        .ejercicio-numero {
            display: inline-block;
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .ejercicio-nombre {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .ejercicio-objetivo {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }
        
        .ejercicio-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .info-badge {
            background: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .ejercicio-media {
            padding: 15px;
            background: #f8f9fa;
            border-top: 2px solid #e0e0e0;
        }
        
        .media-toggle-btn {
            width: 100%;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .media-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.3);
        }
        
        .media-content {
            display: none;
            margin-top: 15px;
            animation: slideDown 0.3s ease;
        }
        
        .media-content.show {
            display: block;
        }
        
        .media-item {
            width: 100%;
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 8px;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .imagen-ejercicio {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tabla-registro {
            padding: 20px;
        }
        
        .tabla-scroll {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }
        
        th, td {
            border: 2px solid #dee2e6;
            padding: 10px 6px;
            text-align: center;
            min-width: 60px;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
        }
        
        .row-label {
            background: #f8f9fa;
            font-weight: bold;
            text-align: left;
            padding-left: 15px;
        }
        
        input[type="number"] {
            width: 100%;
            min-width: 70px;
            padding: 10px 5px;
            border: 2px solid #ddd;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
        }
        
        input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .eval-select {
            width: 100%;
            min-width: 50px;
            padding: 8px 4px;
            border: 2px solid #ddd;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            background: white;
        }
        
        .eval-select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .eval-select option[value="bien"] {
            color: #4caf50;
        }
        
        .eval-select option[value="mal"] {
            color: #f44336;
        }
        
        .eval-select option[value="regular"] {
            color: #ff9800;
        }
        
        .guardado {
            background: #e8f5e9 !important;
            border-color: #4caf50 !important;
        }
        
        .ejercicio-acciones {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
            border-top: 2px solid #e0e0e0;
        }
        
        .btn-accion {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-nota {
            background: #2196f3;
            color: white;
        }
        
        .btn-fecha {
            background: #9c27b0;
            color: white;
        }
        
        #save-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            font-weight: bold;
            display: none;
        }
        
        .guardando {
            background: #ffc107;
            color: #333;
        }
        
        .guardado-ok {
            background: #4caf50;
            color: white;
        }
        
        .completar-btn {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .ejercicios-container {
                padding: 10px;
            }
            
            .ejercicio-header {
                padding: 12px;
                gap: 10px;
            }
            
            .ejercicio-imagen-mini {
                width: 80px;
                height: 80px;
            }
            
            .ejercicio-header-content {
                width: auto;
            }
            
            .ejercicio-numero {
                width: 25px;
                height: 25px;
                line-height: 25px;
                font-size: 14px;
            }
            
            .ejercicio-nombre {
                font-size: 16px;
            }
            
            .ejercicio-objetivo {
                font-size: 12px;
            }
            
            .ejercicio-info {
                gap: 6px;
                flex-wrap: wrap;
            }
            
            .info-badge {
                font-size: 11px;
                padding: 4px 8px;
            }
            
            table {
                font-size: 13px;
                min-width: 600px;
            }
            
            th, td {
                padding: 6px 4px;
                min-width: 60px;
            }
            
            input[type="number"] {
                padding: 8px 2px;
                font-size: 14px;
                min-width: 55px;
            }
            
            .row-label {
                font-size: 13px;
                padding-left: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="header-dia">
        <div class="header-content">
            <div class="header-top">
                <div class="dia-title">💪 <?php echo $dia_semana; ?></div>
                <a href="ver_rutina.php?id=<?php echo $rutina_id; ?>" class="btn-volver-header">← Volver</a>
            </div>
            <div class="rutina-nombre"><?php echo htmlspecialchars($rutina['nombre_rutina']); ?></div>
            <div class="progreso-dia">
                📋 <?php echo count($ejercicios); ?> ejercicios programados
                <?php if ($dia['grupos_musculares']): ?>
                    | 💪 <?php echo htmlspecialchars($dia['grupos_musculares']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="save-indicator"></div>
    <div id="toast-message" class="toast-message"></div>
    
    <div class="ejercicios-container">
        <?php if (empty($ejercicios)): ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <h3>No hay ejercicios para este día</h3>
                <p style="color: #666; margin: 15px 0;">Agrega ejercicios desde la configuración de la rutina</p>
                <a href="ver_rutina.php?id=<?php echo $rutina_id; ?>" class="btn-primary">Volver a la Rutina</a>
            </div>
        <?php else: ?>
            <?php foreach ($ejercicios as $index => $ejercicio): ?>
                <div class="ejercicio-card" id="ejercicio-<?php echo $ejercicio['id']; ?>">
                    <!-- Header del ejercicio -->
                    <div class="ejercicio-header">
                        <?php if ($ejercicio['imagen_url']): ?>
                            <img src="<?php echo htmlspecialchars($ejercicio['imagen_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?>" 
                                 class="ejercicio-imagen-mini">
                        <?php endif; ?>
                        
                        <div class="ejercicio-header-content">
                            <div>
                                <span class="ejercicio-numero"><?php echo $ejercicio['orden']; ?></span>
                                <span class="ejercicio-nombre"><?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?></span>
                            </div>
                            <?php if ($ejercicio['objetivo_serie']): ?>
                                <div class="ejercicio-objetivo">
                                    🎯 <?php echo htmlspecialchars($ejercicio['objetivo_serie']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="ejercicio-info">
                                <div class="info-badge">
                                    <strong>Series:</strong> <?php echo $ejercicio['num_series']; ?>
                                </div>
                                <div class="info-badge">
                                    <strong>Sesiones:</strong> <?php echo $ejercicio['num_sesiones']; ?>
                                </div>
                                <div class="info-badge">
                                    <strong>Descanso:</strong> <?php echo $ejercicio['descanso_minutos']; ?>'<?php echo $ejercicio['descanso_segundos'] > 0 ? ' ' . $ejercicio['descanso_segundos'] . '"' : ''; ?>
                                </div>
                                <?php if ($ejercicio['rir_rpe']): ?>
                                <div class="info-badge">
                                    <strong>RIR/RPE:</strong> <?php echo htmlspecialchars($ejercicio['rir_rpe']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Video (solo si existe, colapsable) -->
                    <?php if ($ejercicio['video_url']): ?>
                        <div class="ejercicio-media">
                            <button class="media-toggle-btn" onclick="toggleMedia(<?php echo $ejercicio['id']; ?>)">
                                <span id="media-icon-<?php echo $ejercicio['id']; ?>">▶️</span>
                                <span id="media-text-<?php echo $ejercicio['id']; ?>">Ver Video Tutorial</span>
                            </button>
                            
                            <div class="media-content" id="media-<?php echo $ejercicio['id']; ?>">
                                <div class="media-item">
                                    <div class="video-container">
                                        <?php
                                        $video_url = $ejercicio['video_url'];
                                        // Convertir URL de YouTube a embed
                                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $video_url, $matches);
                                            $video_id = $matches[1] ?? '';
                                            if ($video_id) {
                                                echo '<iframe src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                            } else {
                                                echo '<p>URL de video no válida</p>';
                                            }
                                        } else {
                                            echo '<video controls style="width: 100%; border-radius: 8px;"><source src="' . htmlspecialchars($video_url) . '"></video>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tabla de registro -->
                    <div class="tabla-registro">
                        <h4 style="margin-bottom: 15px; color: #333;">📊 Registro de Series</h4>
                        <div class="tabla-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Serie</th>
                                        <?php for ($s = 1; $s <= $ejercicio['num_sesiones']; $s++): ?>
                                            <th colspan="3">Sesión <?php echo $s; ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <?php for ($s = 1; $s <= $ejercicio['num_sesiones']; $s++): ?>
                                            <th>Peso (kg)</th>
                                            <th>Reps</th>
                                            <th>✓</th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($serie = 1; $serie <= $ejercicio['num_series']; $serie++): ?>
                                    <tr>
                                        <td class="row-label">S.<?php echo $serie; ?></td>
                                        <?php for ($sesion = 1; $sesion <= $ejercicio['num_sesiones']; $sesion++): ?>
                                            <td>
                                                <input type="number" 
                                                       step="0.5" 
                                                       class="peso-input" 
                                                       data-ejercicio-id="<?php echo $ejercicio['id']; ?>"
                                                       data-sesion="<?php echo $sesion; ?>" 
                                                       data-serie="<?php echo $serie; ?>"
                                                       data-campo="peso"
                                                       placeholder="kg">
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="reps-input" 
                                                       data-ejercicio-id="<?php echo $ejercicio['id']; ?>"
                                                       data-sesion="<?php echo $sesion; ?>" 
                                                       data-serie="<?php echo $serie; ?>"
                                                       data-campo="repeticiones"
                                                       placeholder="reps">
                                            </td>
                                            <td>
                                                <select class="eval-select"
                                                        data-ejercicio-id="<?php echo $ejercicio['id']; ?>"
                                                        data-sesion="<?php echo $sesion; ?>"
                                                        data-serie="<?php echo $serie; ?>"
                                                        data-campo="evaluacion">
                                                    <option value="">-</option>
                                                    <option value="bien">✓</option>
                                                    <option value="mal">✗</option>
                                                    <option value="regular">~</option>
                                                </select>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Acciones del ejercicio -->
                    <div class="ejercicio-acciones">
                        <button class="btn-accion btn-nota" onclick="agregarNota(<?php echo $ejercicio['id']; ?>)">
                            📝 Nota
                        </button>
                        <button class="btn-accion btn-fecha" onclick="establecerFecha(<?php echo $ejercicio['id']; ?>)">
                            📅 Fecha
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Botón finalizar -->
            <button class="completar-btn" onclick="completarDia()">
                ✅ Finalizar Día de Entrenamiento
            </button>
        <?php endif; ?>
    </div>
    
    <script>
        let autoSaveTimer;
        
        // Función para mostrar toast
        function showToast(message) {
            const toast = document.getElementById('toast-message');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // Toggle de videos
        function toggleMedia(ejercicioId) {
            const mediaContent = document.getElementById('media-' + ejercicioId);
            const icon = document.getElementById('media-icon-' + ejercicioId);
            const text = document.getElementById('media-text-' + ejercicioId);
            
            if (mediaContent.classList.contains('show')) {
                mediaContent.classList.remove('show');
                icon.textContent = '▶️';
                text.textContent = 'Ver Video Tutorial';
            } else {
                mediaContent.classList.add('show');
                icon.textContent = '🔽';
                text.textContent = 'Ocultar Video';
            }
        }
        
        // Autoguardado
        document.querySelectorAll('input[type="number"], select.eval-select').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('guardado');
                clearTimeout(autoSaveTimer);
                
                mostrarIndicador('guardando');
                
                autoSaveTimer = setTimeout(() => {
                    autoGuardar(this);
                }, 2000);
            });
            
            input.addEventListener('change', function() {
                this.classList.remove('guardado');
                clearTimeout(autoSaveTimer);
                mostrarIndicador('guardando');
                autoSaveTimer = setTimeout(() => {
                    autoGuardar(this);
                }, 500);
            });
        });
        
        function mostrarIndicador(estado) {
            const indicator = document.getElementById('save-indicator');
            indicator.className = '';
            indicator.style.display = 'block';
            
            if (estado === 'guardando') {
                indicator.classList.add('guardando');
                indicator.textContent = '💾 Guardando...';
            } else if (estado === 'ok') {
                indicator.classList.add('guardado-ok');
                indicator.textContent = '✅ Guardado';
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 2000);
            }
        }
        
        function autoGuardar(input) {
            const formData = new FormData();
            formData.append('ejercicio_id', input.dataset.ejercicioId);
            formData.append('num_sesion', input.dataset.sesion);
            formData.append('num_serie', input.dataset.serie);
            formData.append('campo', input.dataset.campo);
            formData.append('valor', input.value);
            formData.append('accion', 'autoguardar');
            
            fetch('guardar_sesion.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    input.classList.add('guardado');
                    mostrarIndicador('ok');
                }
            });
        }
        
        function completarDia() {
            showToast('✅ ¡Día completado! Redirigiendo...');
            setTimeout(() => {
                window.location.href = 'ver_rutina.php?id=<?php echo $rutina_id; ?>';
            }, 1500);
        }
        
        function agregarNota(ejercicioId) {
            showToast('📝 Función de notas en desarrollo');
        }
        
        function establecerFecha(ejercicioId) {
            showToast('📅 Función de fecha en desarrollo');
        }
    </script>
</body>
</html>