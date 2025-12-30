<?php
require_once 'config.php';
requiereLogin();

$rutina_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$dia_semana = isset($_GET['dia']) ? $_GET['dia'] : '';
$ejercicio_actual = isset($_GET['ejercicio']) ? intval($_GET['ejercicio']) : 0;

if (!$rutina_id || !$dia_semana) {
    header('Location: mis_rutinas.php');
    exit();
}

$conn = getConnection();

// Obtener información de la rutina
$stmt = $conn->prepare("SELECT * FROM rutinas WHERE id = ? AND usuario_id = ? AND (tipo_rutina = 'basica_gym' OR tipo_rutina = 'basica')");
$stmt->bind_param("ii", $rutina_id, $_SESSION['usuario_id']);
$stmt->execute();
$rutina = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rutina) {
    $_SESSION['mensaje_error'] = 'Rutina no encontrada';
    header('Location: mis_rutinas.php');
    exit();
}

// Obtener día específico
$stmt = $conn->prepare("SELECT * FROM dias_rutina WHERE rutina_id = ? AND dia_semana = ?");
$stmt->bind_param("is", $rutina_id, $dia_semana);
$stmt->execute();
$dia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dia) {
    $_SESSION['mensaje_error'] = 'Día no encontrado';
    header('Location: mis_rutinas.php');
    exit();
}

// Obtener ejercicios del día
$stmt = $conn->prepare("SELECT * FROM ejercicios_rutina WHERE dia_id = ? ORDER BY orden");
$stmt->bind_param("i", $dia['id']);
$stmt->execute();
$ejercicios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($ejercicios)) {
    $_SESSION['mensaje_error'] = 'No hay ejercicios en este día';
    header('Location: mis_rutinas.php');
    exit();
}

// Verificar índice de ejercicio actual
if ($ejercicio_actual < 0) $ejercicio_actual = 0;
if ($ejercicio_actual >= count($ejercicios)) $ejercicio_actual = count($ejercicios) - 1;

$ejercicio = $ejercicios[$ejercicio_actual];
$total_ejercicios = count($ejercicios);
$es_ultimo = ($ejercicio_actual === $total_ejercicios - 1);
$es_primero = ($ejercicio_actual === 0);

// Obtener resumen de músculos del día actual
$musculos = array_unique(array_filter(array_column($ejercicios, 'categoria')));
$resumen_musculos = !empty($musculos) ? implode(', ', $musculos) : 'Varios grupos musculares';

// Obtener TODOS los días de la rutina con sus músculos
$stmt = $conn->prepare("
    SELECT dr.dia_semana, GROUP_CONCAT(DISTINCT er.categoria SEPARATOR ', ') as musculos
    FROM dias_rutina dr
    LEFT JOIN ejercicios_rutina er ON dr.id = er.dia_id
    WHERE dr.rutina_id = ?
    GROUP BY dr.id, dr.dia_semana
    ORDER BY FIELD(dr.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')
");
$stmt->bind_param("i", $rutina_id);
$stmt->execute();
$todos_dias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- PWA -->
<meta name="theme-color" content="#2563eb">
<link rel="manifest" href="/sistema_entrenamiento/manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($rutina['nombre_rutina']); ?> - Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="notifications.css">
    <style>
        body {
            background: #f0f0f0;
            padding: 0;
        }
        
        .entrenar-container {
            max-width: 600px;
            margin: 0 auto;
            min-height: 100vh;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .entrenar-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .usuario-info {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .dia-badge {
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            margin-top: 5px;
        }
        
        /* Botones de navegación inline */
        .btn-nav-inline {
            background: #2563eb;
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .btn-nav-inline:hover:not(:disabled) {
            background: #1e40af;
            transform: scale(1.1);
        }
        
        .btn-nav-inline:disabled {
            cursor: not-allowed;
        }
        
        .resumen-musculos {
            background: #e3f2fd;
            padding: 15px;
            text-align: center;
            border-bottom: 3px solid #2563eb;
        }
        
        .resumen-musculos strong {
            color: #2563eb;
        }
        
        .ejercicio-info {
            padding: 15px;
            text-align: center;
        }
        
        .ejercicio-nombre {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .ejercicio-detalles {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .ejercicio-imagen-container {
            width: 100%;
            max-width: 350px;
            margin: 12px auto;
            position: relative;
        }
        
        .ejercicio-imagen {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        
        .ejercicio-imagen-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #999;
        }
        
        .botones-ejercicio {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 10px 0;
        }
        
        .btn-video, .btn-alternativo {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .btn-video {
            background: #10b981;
            color: white;
        }
        
        .btn-video:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-alternativo {
            background: #2563eb;
            color: white;
        }
        
        .btn-alternativo:hover {
            background: #1e40af;
            transform: translateY(-2px);
        }
        
        .descripcion-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 15px;
            color: #666;
            line-height: 1.4;
            text-align: left;
            font-size: 12px;
        }
        
        .series-checkboxes {
            padding: 12px 15px;
            background: #fafafa;
            margin: 10px 15px;
            border-radius: 8px;
        }
        
        .series-checkboxes h3 {
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
        }
        
        .series-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .serie-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .serie-checkbox:hover {
            border-color: #2563eb;
            background: #f0f9ff;
        }
        
        .serie-checkbox input[type="checkbox"] {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #2563eb;
        }
        
        .serie-checkbox label {
            cursor: pointer;
            font-weight: 600;
            color: #666;
            margin: 0;
        }
        
        .serie-checkbox input:checked + label {
            color: #2563eb;
        }
        
        .notas-section {
            padding: 12px 15px;
        }
        
        .notas-section h3 {
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }
        
        .notas-textarea {
            width: 100%;
            min-height: 70px;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s;
        }
        
        .notas-textarea:focus {
            outline: none;
            border-color: #2563eb;
        }
        
        .navegacion {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            padding: 12px;
            background: #f8f9fa;
            border-top: 2px solid #e0e0e0;
        }
        
        .btn-nav {
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-anterior {
            background: #6b7280;
            color: white;
        }
        
        .btn-anterior:hover:not(:disabled) {
            background: #4b5563;
            transform: translateY(-2px);
        }
        
        .btn-finalizar {
            background: #ef4444;
            color: white;
        }
        
        .btn-finalizar:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .btn-siguiente {
            background: #2563eb;
            color: white;
        }
        
        .btn-siguiente:hover:not(:disabled) {
            background: #1e40af;
            transform: translateY(-2px);
        }
        
        .btn-nav:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .progreso-ejercicios {
            text-align: center;
            padding: 10px;
            background: #e3f2fd;
            color: #2563eb;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="entrenar-container">
        <!-- Header -->
        <div class="entrenar-header">
            <div class="usuario-info">
                Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? $_SESSION['nombre_usuario'] ?? $_SESSION['username'] ?? 'Usuario'); ?>
            </div>
            <div style="font-size: 14px; opacity: 0.9;">
                <?php echo htmlspecialchars($rutina['nombre_rutina']); ?>
            </div>
            <div class="dia-badge">
                <?php echo $dia_semana; ?>
            </div>
        </div>
        
        <!-- Resumen de músculos de la semana completa -->
        <div class="resumen-semana" style="background: #f0f9ff; padding: 10px; border-bottom: 2px solid #2563eb;">
            <div style="text-align: center; margin-bottom: 8px;">
                <strong style="color: #2563eb; font-size: 14px;">📅 Rutina Semanal</strong>
            </div>
            <div style="display: flex; gap: 6px; flex-wrap: wrap; justify-content: center;">
                <?php foreach ($todos_dias as $d): 
                    $tiene_ejercicios = !empty($d['musculos']);
                    $es_dia_actual = $d['dia_semana'] === $dia_semana;
                    
                    if ($tiene_ejercicios): ?>
                        <!-- Día con ejercicios (clickeable) -->
                        <a href="entrenar_rutina_basica.php?id=<?php echo $rutina_id; ?>&dia=<?php echo urlencode($d['dia_semana']); ?>&ejercicio=0" 
                           class="dia-semana-card" 
                           style="text-decoration: none; 
                                  background: <?php echo $es_dia_actual ? '#2563eb' : 'white'; ?>; 
                                  color: <?php echo $es_dia_actual ? 'white' : '#333'; ?>; 
                                  padding: 6px 10px; border-radius: 8px; 
                                  border: 2px solid <?php echo $es_dia_actual ? '#2563eb' : '#2563eb'; ?>; 
                                  box-shadow: 0 1px 4px rgba(0,0,0,0.1); min-width: 90px; text-align: center;
                                  transition: all 0.3s; cursor: pointer; flex: 0 0 auto;">
                            <div style="font-weight: bold; margin-bottom: 3px; font-size: 12px;">
                                <?php echo htmlspecialchars($d['dia_semana']); ?>
                            </div>
                            <div style="font-size: 10px; opacity: 0.8;">
                                <?php echo htmlspecialchars($d['musculos']); ?>
                            </div>
                        </a>
                    <?php else: ?>
                        <!-- Día sin ejercicios (NO clickeable) -->
                        <div class="dia-semana-card-disabled" 
                             style="text-decoration: none; 
                                    background: #f0f0f0; 
                                    color: #999; 
                                    padding: 6px 10px; border-radius: 8px; 
                                    border: 2px solid #e0e0e0; 
                                    box-shadow: 0 1px 4px rgba(0,0,0,0.05); min-width: 90px; text-align: center;
                                    cursor: not-allowed; opacity: 0.6; flex: 0 0 auto;">
                            <div style="font-weight: bold; margin-bottom: 3px; font-size: 12px;">
                                <?php echo htmlspecialchars($d['dia_semana']); ?>
                            </div>
                            <div style="font-size: 10px;">
                                🏖️ Descanso
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Progreso -->
        <div class="progreso-ejercicios">
            Ejercicio <?php echo $ejercicio_actual + 1; ?> de <?php echo $total_ejercicios; ?>
        </div>
        
        <!-- Información del ejercicio -->
        <div class="ejercicio-info">
            <div class="ejercicio-nombre" style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                <button onclick="navegarEjercicio(<?php echo $ejercicio_actual - 1; ?>)" 
                        class="btn-nav-inline"
                        <?php echo $es_primero ? 'disabled style="opacity: 0.3;"' : ''; ?>>
                    ⬅️
                </button>
                <div style="flex: 1; text-align: center;">
                    <?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?>
                </div>
                <button onclick="navegarEjercicio(<?php echo $ejercicio_actual + 1; ?>)" 
                        class="btn-nav-inline"
                        <?php echo $es_ultimo ? 'disabled style="opacity: 0.3;"' : ''; ?>>
                    ➡️
                </button>
            </div>
            
            <div class="ejercicio-detalles" style="font-size: 13px; margin-bottom: 10px;">
                <?php if (!empty($ejercicio['categoria'])): ?>
                    <strong>Músculo:</strong> <?php echo htmlspecialchars($ejercicio['categoria']); ?><br>
                <?php endif; ?>
                <strong>Series:</strong> <?php echo $ejercicio['series']; ?> | 
                <strong>Repeticiones:</strong> <?php echo htmlspecialchars($ejercicio['repeticiones']); ?>
                <?php if ($ejercicio['descanso'] > 0): ?>
                    | <strong>Descanso:</strong> <?php echo $ejercicio['descanso']; ?>s
                <?php endif; ?>
            </div>
            
            <!-- Imagen/Video del ejercicio -->
            <div class="ejercicio-imagen-container">
                <!-- Imagen -->
                <div id="imagenContainer" style="display: block;">
                    <?php if (!empty($ejercicio['imagen_url'])): ?>
                        <img src="<?php echo htmlspecialchars($ejercicio['imagen_url']); ?>" 
                             alt="<?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?>"
                             class="ejercicio-imagen"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="ejercicio-imagen-placeholder" style="display: none;">
                            💪
                        </div>
                    <?php else: ?>
                        <div class="ejercicio-imagen-placeholder">
                            💪
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Video (inicialmente oculto) -->
                <div id="videoContainer" style="display: none;">
                    <?php if (!empty($ejercicio['video_url'])): 
                        $video_url = $ejercicio['video_url'];
                        // Convertir URL de YouTube a embed
                        if (strpos($video_url, 'youtube.com/watch') !== false) {
                            preg_match('/[?&]v=([^&]+)/', $video_url, $matches);
                            $video_id = $matches[1] ?? '';
                            $video_url = 'https://www.youtube.com/embed/' . $video_id;
                        } elseif (strpos($video_url, 'youtu.be/') !== false) {
                            $video_id = basename(parse_url($video_url, PHP_URL_PATH));
                            $video_url = 'https://www.youtube.com/embed/' . $video_id;
                        }
                    ?>
                        <iframe id="videoFrame"
                                width="100%" 
                                height="220" 
                                src="<?php echo htmlspecialchars($video_url); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen
                                style="border-radius: 12px;">
                        </iframe>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Botones de video y alternativo -->
            <div class="botones-ejercicio">
                <?php if (!empty($ejercicio['video_url'])): ?>
                    <button class="btn-video" id="btnToggleVideo" onclick="toggleVideo()">
                        🎥 Ver video
                    </button>
                <?php endif; ?>
                <button class="btn-alternativo" onclick="mostrarAlternativo()">
                    🔄 E. Alternativo
                </button>
            </div>
            
            <!-- Descripción/Recomendaciones -->
            <div class="descripcion-box">
                Asegúrate de ejecutar el ejercicio de manera controlada. 
                Mantén una postura correcta durante todo el movimiento y 
                respeta los tiempos de descanso entre series.
            </div>
        </div>
        
        <!-- Checkboxes de series -->
        <div class="series-checkboxes">
            <h3>✓ Marcar series completadas:</h3>
            <div class="series-grid" id="seriesGrid">
                <?php for ($i = 1; $i <= $ejercicio['series']; $i++): ?>
                    <div class="serie-checkbox">
                        <input type="checkbox" 
                               id="serie_<?php echo $i; ?>" 
                               name="series[]" 
                               value="<?php echo $i; ?>"
                               onchange="guardarProgreso()">
                        <label for="serie_<?php echo $i; ?>">Serie <?php echo $i; ?></label>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- Notas -->
        <div class="notas-section">
            <h3>📝 Notas:</h3>
            <textarea class="notas-textarea" 
                      id="notasEjercicio"
                      placeholder="Escribe tus observaciones, sensaciones o cualquier detalle importante sobre este ejercicio..."
                      onchange="guardarProgreso()"></textarea>
        </div>
        
        <!-- Navegación -->
        <div class="navegacion">
            <button class="btn-nav btn-anterior" 
                    onclick="navegarEjercicio(<?php echo $ejercicio_actual - 1; ?>)"
                    <?php echo $es_primero ? 'disabled' : ''; ?>>
                ⬅️ Anterior
            </button>
            
            <button class="btn-nav btn-finalizar" onclick="finalizarEntrenamiento()">
                <?php echo $es_ultimo ? '🎉 Finalizar' : '🏠 Salir'; ?>
            </button>
            
            <button class="btn-nav btn-siguiente" 
                    onclick="navegarEjercicio(<?php echo $ejercicio_actual + 1; ?>)"
                    <?php echo $es_ultimo ? 'disabled' : ''; ?>>
                Siguiente ➡️
            </button>
        </div>
    </div>
    
    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <script src="notifications.js"></script>
    
    <script>
        const RUTINA_ID = <?php echo $rutina_id; ?>;
        const DIA_SEMANA = '<?php echo $dia_semana; ?>';
        const EJERCICIO_ACTUAL = <?php echo $ejercicio_actual; ?>;
        const EJERCICIO_ID = <?php echo $ejercicio['id']; ?>;
        const TOTAL_EJERCICIOS = <?php echo $total_ejercicios; ?>;
        
        // Cargar progreso guardado
        window.addEventListener('DOMContentLoaded', function() {
            cargarProgreso();
        });
        
        function navegarEjercicio(nuevoIndice) {
            if (nuevoIndice < 0 || nuevoIndice >= TOTAL_EJERCICIOS) return;
            
            window.location.href = `entrenar_rutina_basica.php?id=${RUTINA_ID}&dia=${DIA_SEMANA}&ejercicio=${nuevoIndice}`;
        }
        
        let mostrandoVideo = false;
        
        function toggleVideo() {
            const imagenContainer = document.getElementById('imagenContainer');
            const videoContainer = document.getElementById('videoContainer');
            const btn = document.getElementById('btnToggleVideo');
            
            if (mostrandoVideo) {
                // Mostrar imagen
                imagenContainer.style.display = 'block';
                videoContainer.style.display = 'none';
                btn.innerHTML = '🎥 Ver video';
                btn.style.background = '#10b981';
                mostrandoVideo = false;
            } else {
                // Mostrar video
                imagenContainer.style.display = 'none';
                videoContainer.style.display = 'block';
                btn.innerHTML = '🖼️ Ver imagen';
                btn.style.background = '#ef4444';
                mostrandoVideo = true;
            }
        }
        
        function mostrarAlternativo() {
            showModal(
                '🔄 Ejercicio Alternativo',
                'Si no puedes realizar este ejercicio, consulta con un entrenador para encontrar un ejercicio alternativo que trabaje el mismo grupo muscular.',
                () => {},
                false
            );
        }
        
        function guardarProgreso() {
            // Obtener series marcadas
            const checkboxes = document.querySelectorAll('input[name="series[]"]:checked');
            const seriesCompletadas = checkboxes.length;
            
            // Obtener notas
            const notas = document.getElementById('notasEjercicio').value;
            
            // Guardar en localStorage
            const key = `progreso_${RUTINA_ID}_${DIA_SEMANA}_${EJERCICIO_ID}`;
            const progreso = {
                series: Array.from(checkboxes).map(cb => cb.value),
                notas: notas,
                fecha: new Date().toISOString()
            };
            
            localStorage.setItem(key, JSON.stringify(progreso));
            
            // Mostrar feedback visual
            if (seriesCompletadas > 0) {
                showToast(`${seriesCompletadas} ${seriesCompletadas === 1 ? 'serie completada' : 'series completadas'}`, 'success');
            }
        }
        
        function cargarProgreso() {
            const key = `progreso_${RUTINA_ID}_${DIA_SEMANA}_${EJERCICIO_ID}`;
            const progreso = localStorage.getItem(key);
            
            if (progreso) {
                try {
                    const data = JSON.parse(progreso);
                    
                    // Marcar checkboxes
                    if (data.series) {
                        data.series.forEach(serieNum => {
                            const checkbox = document.getElementById(`serie_${serieNum}`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                    
                    // Cargar notas
                    if (data.notas) {
                        document.getElementById('notasEjercicio').value = data.notas;
                    }
                } catch (e) {
                    console.error('Error al cargar progreso:', e);
                }
            }
        }
        
        function finalizarEntrenamiento() {
            const esUltimoEjercicio = EJERCICIO_ACTUAL === TOTAL_EJERCICIOS - 1;
            
            if (esUltimoEjercicio) {
                // Último ejercicio - felicitación
                showModal(
                    '🎉 ¡Entrenamiento Completado!',
                    `¡Excelente trabajo! Has completado todos los ejercicios del ${DIA_SEMANA}. Sigue así y alcanzarás tus objetivos.`,
                    () => {
                        window.location.href = 'mis_rutinas.php';
                    },
                    false
                );
            } else {
                // No es el último - confirmar salida
                showModal(
                    '¿Salir del entrenamiento?',
                    'Aún te quedan ejercicios por completar. ¿Estás seguro de que quieres salir?',
                    () => {
                        window.location.href = 'mis_rutinas.php';
                    },
                    true
                );
            }
        }
        
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && EJERCICIO_ACTUAL > 0) {
                navegarEjercicio(EJERCICIO_ACTUAL - 1);
            } else if (e.key === 'ArrowRight' && EJERCICIO_ACTUAL < TOTAL_EJERCICIOS - 1) {
                navegarEjercicio(EJERCICIO_ACTUAL + 1);
            }
        });
    </script>
    <script src="/sistema_entrenamiento/pwa-register.js"></script>
</body>
</html>