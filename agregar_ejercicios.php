<?php
require_once 'config.php';
requiereLogin();

$rutina_id = isset($_GET['rutina_id']) ? intval($_GET['rutina_id']) : 0;

if ($rutina_id == 0) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();

// Obtener información de la rutina
$stmt = $conn->prepare("SELECT * FROM rutinas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $rutina_id, $_SESSION['usuario_id']);
$stmt->execute();
$rutina = $stmt->get_result()->fetch_assoc();

if (!$rutina) {
    header('Location: index.php');
    exit();
}

// Obtener días de la rutina
$stmt = $conn->prepare("SELECT * FROM dias_rutina WHERE rutina_id = ? ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')");
$stmt->bind_param("i", $rutina_id);
$stmt->execute();
$dias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener ejercicios ya creados
$stmt = $conn->prepare("SELECT e.*, d.dia_semana FROM ejercicios e JOIN dias_rutina d ON e.dia_rutina_id = d.id WHERE d.rutina_id = ? ORDER BY d.id, e.orden");
$stmt->bind_param("i", $rutina_id);
$stmt->execute();
$ejercicios_existentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener todas las categorías
$categorias = $conn->query("SELECT * FROM categorias_ejercicios ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener ejercicios de la biblioteca
$biblioteca = $conn->query("SELECT b.*, c.nombre as categoria_nombre FROM biblioteca_ejercicios b JOIN categorias_ejercicios c ON b.categoria_id = c.id WHERE b.es_publico = 1 ORDER BY c.nombre, b.nombre_ejercicio")->fetch_all(MYSQLI_ASSOC);

// Organizar biblioteca por categoría
$biblioteca_por_categoria = [];
foreach ($biblioteca as $ej) {
    $biblioteca_por_categoria[$ej['categoria_nombre']][] = $ej;
}

// Organizar ejercicios por día
$ejercicios_por_dia = [];
foreach ($ejercicios_existentes as $ej) {
    $ejercicios_por_dia[$ej['dia_semana']][] = $ej;
}

// Calcular progreso
$total_ejercicios_necesarios = 0;
foreach ($dias as $dia) {
    $total_ejercicios_necesarios += $dia['num_ejercicios'];
}
$ejercicios_creados = count($ejercicios_existentes);
$progreso = ($ejercicios_creados / $total_ejercicios_necesarios) * 100;

$error = '';
$exito = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dia_rutina_id = intval($_POST['dia_rutina_id']);
    $orden = intval($_POST['orden']);
    $nombre_ejercicio = trim($_POST['nombre_ejercicio']);
    $imagen_url = trim($_POST['imagen_url']);
    $video_url = trim($_POST['video_url']);
    $objetivo_serie = trim($_POST['objetivo_serie']);
    $num_series = intval($_POST['num_series']);
    $num_sesiones = intval($_POST['num_sesiones']);
    $descanso_minutos = intval($_POST['descanso_minutos']);
    $descanso_segundos = intval($_POST['descanso_segundos']);
    $rir_rpe = trim($_POST['rir_rpe']);
    
    if (empty($nombre_ejercicio)) {
        $error = 'El nombre del ejercicio es obligatorio';
    } else {
        $stmt = $conn->prepare("INSERT INTO ejercicios (dia_rutina_id, usuario_id, orden, nombre_ejercicio, imagen_url, video_url, objetivo_serie, num_series, num_sesiones, descanso_minutos, descanso_segundos, rir_rpe) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissssiiiis", $dia_rutina_id, $_SESSION['usuario_id'], $orden, $nombre_ejercicio, $imagen_url, $video_url, $objetivo_serie, $num_series, $num_sesiones, $descanso_minutos, $descanso_segundos, $rir_rpe);
        
        if ($stmt->execute()) {
            $exito = 'Ejercicio agregado exitosamente';
            header("refresh:1;url=agregar_ejercicios.php?rutina_id=$rutina_id");
        } else {
            $error = 'Error al agregar el ejercicio';
        }
    }
    
    $stmt->close();
}

$conn->close();

// Determinar siguiente ejercicio a crear - ahora dinámico
$dia_seleccionado = isset($_GET['dia']) ? $_GET['dia'] : null;
$siguiente_dia = null;
$siguiente_orden = 0;

if ($dia_seleccionado) {
    // Usuario eligió un día específico
    foreach ($dias as $dia) {
        if ($dia['dia_semana'] == $dia_seleccionado) {
            $ejercicios_dia_count = isset($ejercicios_por_dia[$dia['dia_semana']]) ? count($ejercicios_por_dia[$dia['dia_semana']]) : 0;
            
            if ($ejercicios_dia_count < $dia['num_ejercicios']) {
                $siguiente_dia = $dia;
                $siguiente_orden = $ejercicios_dia_count + 1;
                break;
            } else {
                // El día ya está completo, buscar siguiente día incompleto
                $dia_seleccionado = null;
                break;
            }
        }
    }
}

// Si no hay día seleccionado o el seleccionado está completo
if (!$siguiente_dia) {
    // Modo automático: buscar primer día incompleto
    foreach ($dias as $dia) {
        $ejercicios_dia_count = isset($ejercicios_por_dia[$dia['dia_semana']]) ? count($ejercicios_por_dia[$dia['dia_semana']]) : 0;
        
        if ($ejercicios_dia_count < $dia['num_ejercicios']) {
            $siguiente_dia = $dia;
            $siguiente_orden = $ejercicios_dia_count + 1;
            break;
        }
    }
}

$rutina_completa = ($siguiente_dia === null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Ejercicios - <?php echo htmlspecialchars($rutina['nombre_rutina']); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <h1>💪 Agregar Ejercicios</h1>
        <a href="index.php" class="btn-volver">← Volver al Inicio</a>
    </div>
    
    <div class="container">
        <!-- Información de la rutina -->
        <div class="card" style="margin-bottom: 20px;">
            <h2><?php echo htmlspecialchars($rutina['nombre_rutina']); ?></h2>
            <?php if ($rutina['descripcion']): ?>
                <p class="subtitle"><?php echo htmlspecialchars($rutina['descripcion']); ?></p>
            <?php endif; ?>
            
            <div class="progress-section">
                <div class="progress-info">
                    <span>Progreso: <strong><?php echo $ejercicios_creados; ?> / <?php echo $total_ejercicios_necesarios; ?></strong> ejercicios</span>
                    <span><strong><?php echo round($progreso); ?>%</strong></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progreso; ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Resumen de días -->
        <div class="card" style="margin-bottom: 20px;">
            <h3>📅 Resumen de Días</h3>
            <p class="helper-text">Haz clic en cualquier día para agregar ejercicios</p>
            
            <div class="dias-resumen">
                <?php foreach ($dias as $dia): ?>
                    <?php 
                    $ejercicios_dia = isset($ejercicios_por_dia[$dia['dia_semana']]) ? $ejercicios_por_dia[$dia['dia_semana']] : [];
                    $ejercicios_count = count($ejercicios_dia);
                    $completo = $ejercicios_count >= $dia['num_ejercicios'];
                    ?>
                    <div class="dia-resumen-item <?php echo $completo ? 'completo' : ''; ?>" 
                         onclick="toggleDiaDetalle('<?php echo $dia['dia_semana']; ?>')">
                        <div class="dia-nombre"><?php echo $dia['dia_semana']; ?></div>
                        <?php if ($dia['grupos_musculares']): ?>
                            <div class="dia-grupos">🎯 <?php echo htmlspecialchars($dia['grupos_musculares']); ?></div>
                        <?php endif; ?>
                        <div class="dia-progreso"><?php echo $ejercicios_count; ?> / <?php echo $dia['num_ejercicios']; ?> ejercicios</div>
                        <?php if ($completo): ?>
                            <div class="dia-check">✓</div>
                        <?php endif; ?>
                        
                        <!-- Detalle de ejercicios (mostrar si tiene ejercicios) -->
                        <div class="dia-ejercicios-detalle" id="detalle_<?php echo $dia['dia_semana']; ?>" 
                             style="display: <?php echo !empty($ejercicios_dia) ? 'block' : 'none'; ?>;">
                            <?php if (!empty($ejercicios_dia)): ?>
                                <div class="ejercicios-lista">
                                    <?php foreach ($ejercicios_dia as $ej): ?>
                                        <div class="ejercicio-mini">
                                            <strong><?php echo $ej['orden']; ?>.</strong> 
                                            <?php echo htmlspecialchars($ej['nombre_ejercicio']); ?>
                                            <span class="ejercicio-info">(<?php echo $ej['num_series']; ?>x<?php echo $ej['num_sesiones']; ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (!$completo): ?>
                                    <a href="?rutina_id=<?php echo $rutina_id; ?>&dia=<?php echo urlencode($dia['dia_semana']); ?>" class="btn-agregar-dia">
                                        ➕ Agregar más ejercicios
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="no-ejercicios">Sin ejercicios aún</p>
                                <a href="?rutina_id=<?php echo $rutina_id; ?>&dia=<?php echo urlencode($dia['dia_semana']); ?>" class="btn-agregar-dia">
                                    ➕ Comenzar a agregar ejercicios
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($rutina_completa): ?>
            <!-- Rutina completada -->
            <div class="card">
                <div class="mensaje exito">
                    ✅ ¡Felicidades! Has completado todos los ejercicios de tu rutina.
                </div>
                <h3>🎯 ¿Qué sigue?</h3>
                <p style="margin: 15px 0;">Ahora puedes comenzar a registrar tus sesiones de entrenamiento.</p>
                <a href="mis_rutinas.php" class="btn-primary">Ver Mis Rutinas</a>
            </div>
        <?php else: ?>
            <!-- Formulario para agregar ejercicio -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Crear Ejercicio <?php echo $siguiente_orden; ?> - <?php echo $siguiente_dia['dia_semana']; ?></h3>
                    <?php if ($siguiente_dia['grupos_musculares']): ?>
                        <div class="badge-grupos">🎯 <?php echo htmlspecialchars($siguiente_dia['grupos_musculares']); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if ($error): ?>
                    <div class="mensaje error">❌ <?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($exito): ?>
                    <div class="mensaje exito">✅ <?php echo $exito; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="dia_rutina_id" value="<?php echo $siguiente_dia['id']; ?>">
                    <input type="hidden" name="orden" value="<?php echo $siguiente_orden; ?>">
                    
                    <div class="form-section">
                        <div class="section-title">🏋️ Información del Ejercicio</div>
                        
                        <?php if ($siguiente_dia['grupos_musculares']): ?>
                            <?php
                            // Separar grupos musculares
                            $grupos = array_map('trim', explode(',', $siguiente_dia['grupos_musculares']));
                            
                            // Contar cuántos ejercicios de biblioteca ya usó en este día
                            $ejercicios_biblioteca_usados = 0;
                            if (isset($ejercicios_por_dia[$siguiente_dia['dia_semana']])) {
                                $conn2 = getConnection(); // Nueva conexión para estas consultas
                                foreach ($ejercicios_por_dia[$siguiente_dia['dia_semana']] as $ej) {
                                    // Verificar si el ejercicio existe en la biblioteca
                                    $stmt2 = $conn2->prepare("SELECT id FROM biblioteca_ejercicios WHERE nombre_ejercicio = ?");
                                    $stmt2->bind_param("s", $ej['nombre_ejercicio']);
                                    $stmt2->execute();
                                    if ($stmt2->get_result()->num_rows > 0) {
                                        $ejercicios_biblioteca_usados++;
                                    }
                                    $stmt2->close();
                                }
                                $conn2->close();
                            }
                            
                            $puede_usar_biblioteca = ($_SESSION['tipo_usuario'] != 'standard') || ($ejercicios_biblioteca_usados < 2);
                            ?>
                            
                            <div class="form-group">
                                <label>💪 Grupos Musculares de Este Día</label>
                                <?php if (!$puede_usar_biblioteca): ?>
                                    <div class="alert-limite" style="display: block; margin-bottom: 15px;">
                                        ⚠️ Has alcanzado el límite de <strong>2 ejercicios de la biblioteca</strong> para usuarios Standard en este día. El siguiente ejercicio debe ser creado manualmente.
                                    </div>
                                <?php endif; ?>
                                
                                <div class="grupos-container">
                                    <?php foreach ($grupos as $grupo): ?>
                                        <?php
                                        $grupo_limpio = trim($grupo);
                                        // Verificar si este grupo existe en categorías
                                        $tiene_ejercicios = isset($biblioteca_por_categoria[$grupo_limpio]) && !empty($biblioteca_por_categoria[$grupo_limpio]);
                                        ?>
                                        <div class="grupo-item">
                                            <input type="radio" 
                                                   id="grupo_<?php echo $grupo_limpio; ?>" 
                                                   name="grupo_muscular" 
                                                   value="<?php echo $grupo_limpio; ?>"
                                                   onchange="mostrarEjercicios('<?php echo $grupo_limpio; ?>', <?php echo $puede_usar_biblioteca ? 'true' : 'false'; ?>)"
                                                   <?php echo !$puede_usar_biblioteca ? 'disabled' : ''; ?>>
                                            <label for="grupo_<?php echo $grupo_limpio; ?>">
                                                <?php echo htmlspecialchars($grupo_limpio); ?>
                                                <?php if ($tiene_ejercicios): ?>
                                                    <span class="badge-count"><?php echo count($biblioteca_por_categoria[$grupo_limpio]); ?></span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="grupo-item">
                                        <input type="radio" id="grupo_manual" name="grupo_muscular" value="manual" onchange="mostrarEjercicios('manual', true)" checked>
                                        <label for="grupo_manual">✍️ Escribir Manualmente</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contenedor de ejercicios de biblioteca -->
                            <div id="ejercicios-biblioteca-container" style="display: none;" class="form-group">
                                <label>📚 Ejercicios de la Biblioteca</label>
                                <div id="ejercicios-lista-scroll" class="ejercicios-scroll">
                                    <!-- Se llenará con JavaScript -->
                                </div>
                            </div>
                            
                            <!-- Mensaje cuando no hay ejercicios -->
                            <div id="sin-ejercicios-mensaje" class="alert-limite" style="display: none;">
                                ℹ️ No hay ejercicios en la biblioteca para este grupo muscular. Escribe el nombre manualmente.
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group" id="nombre-manual-container">
                            <label for="nombre_ejercicio">Nombre del Ejercicio *</label>
                            <input type="text" id="nombre_ejercicio" name="nombre_ejercicio" 
                                   placeholder="Ej: Press de Banca, Sentadillas, Peso Muerto..." required>
                        </div>
                        
                        <div class="form-group">
                            <label for="imagen_url">URL de Imagen (Opcional)</label>
                            <input type="text" id="imagen_url" name="imagen_url" 
                                   placeholder="https://ejemplo.com/imagen.jpg">
                            <div class="helper-text">Pega la URL de una imagen de referencia del ejercicio</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="video_url">URL de Video Tutorial (Opcional)</label>
                            <input type="text" id="video_url" name="video_url" 
                                   placeholder="https://youtube.com/watch?v=...">
                            <div class="helper-text">Pega la URL de un video explicativo del ejercicio</div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-title">⚙️ Configuración de Series</div>
                        
                        <div class="form-group">
                            <label for="objetivo_serie">Objetivo de la Serie</label>
                            <input type="text" id="objetivo_serie" name="objetivo_serie" 
                                   placeholder="Ej: Top Set, Back Off, Volume..." value="Top Set">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="num_series">Número de Series *</label>
                                <input type="number" id="num_series" name="num_series" 
                                       min="1" max="20" value="3" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="num_sesiones">Número de Sesiones *</label>
                                <input type="number" id="num_sesiones" name="num_sesiones" 
                                       min="1" max="50" value="4" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-title">⏱️ Parámetros de Entrenamiento</div>
                        
                        <div class="form-group">
                            <label>Tiempo de Descanso</label>
                            <div class="time-input">
                                <input type="number" id="descanso_minutos" name="descanso_minutos" 
                                       min="0" max="30" value="2" placeholder="Min">
                                <span class="time-label">minutos</span>
                                <input type="number" id="descanso_segundos" name="descanso_segundos" 
                                       min="0" max="59" value="0" placeholder="Seg">
                                <span class="time-label">segundos</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="rir_rpe">RIR o RPE (@)</label>
                            <input type="text" id="rir_rpe" name="rir_rpe" 
                                   placeholder="Ej: 2-3, @8, @9" value="2-3">
                            <div class="helper-text">Reps in Reserve (RIR) o Rate of Perceived Exertion (RPE)</div>
                        </div>
                    </div>
                    
                    <button type="submit">➕ Agregar Ejercicio y Continuar</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Datos de la biblioteca de ejercicios por categoría
        const bibliotecaPorCategoria = <?php echo json_encode($biblioteca_por_categoria); ?>;
        
        function toggleDiaDetalle(dia) {
            const detalle = document.getElementById('detalle_' + dia);
            if (detalle.style.display === 'none') {
                detalle.style.display = 'block';
            } else {
                detalle.style.display = 'none';
            }
        }
        
        function mostrarEjercicios(grupo, puedeUsar) {
            const container = document.getElementById('ejercicios-biblioteca-container');
            const lista = document.getElementById('ejercicios-lista-scroll');
            const inputNombre = document.getElementById('nombre_ejercicio');
            const containerManual = document.getElementById('nombre-manual-container');
            const mensajeSin = document.getElementById('sin-ejercicios-mensaje');
            
            // Ocultar mensaje
            mensajeSin.style.display = 'none';
            
            if (grupo === 'manual' || !puedeUsar) {
                container.style.display = 'none';
                containerManual.style.display = 'block';
                inputNombre.value = '';
                inputNombre.required = true;
                return;
            }
            
            if (bibliotecaPorCategoria[grupo] && bibliotecaPorCategoria[grupo].length > 0) {
                lista.innerHTML = '';
                bibliotecaPorCategoria[grupo].forEach(ejercicio => {
                    const div = document.createElement('div');
                    div.className = 'ejercicio-biblioteca-item';
                    div.onclick = () => seleccionarEjercicio(ejercicio);
                    
                    div.innerHTML = `
                        <div class="ejercicio-info-biblioteca">
                            <strong>${ejercicio.nombre_ejercicio}</strong>
                            ${ejercicio.descripcion ? `<p>${ejercicio.descripcion}</p>` : ''}
                        </div>
                        <button type="button" class="btn-seleccionar">Seleccionar</button>
                    `;
                    
                    lista.appendChild(div);
                });
                
                container.style.display = 'block';
                containerManual.style.display = 'none';
                inputNombre.required = false;
            } else {
                container.style.display = 'none';
                containerManual.style.display = 'block';
                mensajeSin.style.display = 'block';
                inputNombre.required = true;
            }
        }
        
        function seleccionarEjercicio(ejercicio) {
            const inputNombre = document.getElementById('nombre_ejercicio');
            const inputImagen = document.getElementById('imagen_url');
            const inputVideo = document.getElementById('video_url');
            
            inputNombre.value = ejercicio.nombre_ejercicio;
            if (ejercicio.imagen_url) inputImagen.value = ejercicio.imagen_url;
            if (ejercicio.video_url) inputVideo.value = ejercicio.video_url;
            
            // Mostrar el campo manual ahora que se seleccionó
            document.getElementById('nombre-manual-container').style.display = 'block';
            document.getElementById('ejercicios-biblioteca-container').style.display = 'none';
            
            // Scroll al campo de nombre
            inputNombre.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    </script>
</body>
</html>