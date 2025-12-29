<?php
require_once 'config.php';
requiereLogin();

$error = '';
$exito = '';

// Obtener ID de rutina
$rutina_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($rutina_id == 0) {
    header('Location: mis_rutinas.php');
    exit();
}

$conn = getConnection();

// Verificar que la rutina pertenece al usuario
$stmt = $conn->prepare("SELECT * FROM rutinas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $rutina_id, $_SESSION['usuario_id']);
$stmt->execute();
$rutina = $stmt->get_result()->fetch_assoc();

if (!$rutina) {
    header('Location: mis_rutinas.php');
    exit();
}

// Obtener días actuales de la rutina
$stmt = $conn->prepare("SELECT * FROM dias_rutina WHERE rutina_id = ? ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')");
$stmt->bind_param("i", $rutina_id);
$stmt->execute();
$dias_actuales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Crear array de días actuales para el formulario
$dias_seleccionados_actual = [];
$ejercicios_por_dia_actual = [];
$grupos_musculares_actual = [];

foreach ($dias_actuales as $dia) {
    $dias_seleccionados_actual[] = $dia['dia_semana'];
    $ejercicios_por_dia_actual[$dia['dia_semana']] = $dia['num_ejercicios'];
    $grupos_musculares_actual[$dia['dia_semana']] = $dia['grupos_musculares'];
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_rutina = trim($_POST['nombre_rutina']);
    $descripcion = trim($_POST['descripcion']);
    $descripcion_split = trim($_POST['descripcion_split'] ?? '');
    $video_explicativo = trim($_POST['video_explicativo'] ?? '');
    $genero = $_POST['genero'] ?? 'unisex';
    $nivel_experiencia = $_POST['nivel_experiencia'] ?? 'principiante';
    $es_publico = isset($_POST['es_publico']) ? 1 : 0;
    
    $dias_seleccionados = isset($_POST['dias']) ? $_POST['dias'] : [];
    $ejercicios_por_dia = [];
    $grupos_musculares = [];
    
    foreach ($dias_seleccionados as $dia) {
        $ejercicios_por_dia[$dia] = intval($_POST["ejercicios_$dia"]);
        $grupos_musculares[$dia] = trim($_POST["grupos_$dia"]);
    }
    
    // Validaciones
    if (empty($nombre_rutina)) {
        $error = 'El nombre de la rutina es obligatorio';
    } elseif (empty($dias_seleccionados)) {
        $error = 'Debes seleccionar al menos un día de entrenamiento';
    } elseif ($_SESSION['tipo_usuario'] == 'standard' && count($dias_seleccionados) > 4) {
        $error = 'Los usuarios Standard pueden tener rutinas de máximo 4 días. Actualiza a Premium para días ilimitados.';
    } elseif ($_SESSION['tipo_usuario'] == 'standard' && $es_publico) {
        $error = 'Solo los usuarios Premium pueden hacer públicas sus rutinas';
    } else {
        // Validar límite de ejercicios por día para usuarios standard
        foreach ($ejercicios_por_dia as $dia => $num_ejercicios) {
            if ($_SESSION['tipo_usuario'] == 'standard' && $num_ejercicios > 3) {
                $error = "Los usuarios Standard pueden tener máximo 3 ejercicios por día. Actualiza a Premium.";
                break;
            }
        }
        
        if (empty($error)) {
            $conn->begin_transaction();
            
            try {
                // Actualizar la rutina
                $num_dias = count($dias_seleccionados);
                
                $stmt = $conn->prepare("UPDATE rutinas SET nombre_rutina = ?, descripcion = ?, descripcion_split = ?, video_explicativo = ?, num_dias_semana = ?, genero = ?, nivel_experiencia = ?, es_publico = ? WHERE id = ? AND usuario_id = ?");
                $stmt->bind_param("ssssissiii", $nombre_rutina, $descripcion, $descripcion_split, $video_explicativo, $num_dias, $genero, $nivel_experiencia, $es_publico, $rutina_id, $_SESSION['usuario_id']);
                $stmt->execute();
                
                // Eliminar días que ya no están seleccionados
                $dias_actuales_nombres = array_column($dias_actuales, 'dia_semana');
                $dias_a_eliminar = array_diff($dias_actuales_nombres, $dias_seleccionados);
                
                foreach ($dias_a_eliminar as $dia) {
                    // Los ejercicios se eliminarán en cascada si está configurado
                    $stmt = $conn->prepare("DELETE FROM dias_rutina WHERE rutina_id = ? AND dia_semana = ?");
                    $stmt->bind_param("is", $rutina_id, $dia);
                    $stmt->execute();
                }
                
                // Actualizar o insertar días seleccionados
                foreach ($dias_seleccionados as $dia) {
                    $num_ejercicios = $ejercicios_por_dia[$dia];
                    $grupos = $grupos_musculares[$dia];
                    
                    // Verificar si el día ya existe
                    $stmt = $conn->prepare("SELECT id FROM dias_rutina WHERE rutina_id = ? AND dia_semana = ?");
                    $stmt->bind_param("is", $rutina_id, $dia);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        // Actualizar día existente
                        $stmt = $conn->prepare("UPDATE dias_rutina SET num_ejercicios = ?, grupos_musculares = ? WHERE rutina_id = ? AND dia_semana = ?");
                        $stmt->bind_param("isis", $num_ejercicios, $grupos, $rutina_id, $dia);
                        $stmt->execute();
                    } else {
                        // Insertar nuevo día
                        $stmt = $conn->prepare("INSERT INTO dias_rutina (rutina_id, dia_semana, num_ejercicios, grupos_musculares) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isis", $rutina_id, $dia, $num_ejercicios, $grupos);
                        $stmt->execute();
                    }
                }
                
                $conn->commit();
                $exito = 'Rutina actualizada exitosamente. Redirigiendo...';
                header("refresh:2;url=ver_rutina.php?id=$rutina_id");
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error al actualizar la rutina: ' . $e->getMessage();
            }
        }
    }
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Rutina - <?php echo htmlspecialchars($rutina['nombre_rutina']); ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Toast notifications */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .toast {
            background: #333;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            min-width: 300px;
            max-width: 400px;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toast.warning {
            background: #ff9800;
            color: #000;
        }
        
        .toast.error {
            background: #f44336;
        }
        
        .toast.success {
            background: #4caf50;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .toast-container {
                bottom: 10px;
                right: 10px;
                left: 10px;
            }
            
            .toast {
                min-width: auto;
                max-width: none;
            }
        }
        
        .dias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .dia-checkbox {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .dia-checkbox:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .dia-checkbox.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .plan-warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .plan-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .dia-checkbox input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .dia-details {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .dia-details input {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            flex: 1;
            background: #e0e0e0;
            color: #333;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .dias-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>✏️ Editar Rutina</h1>
        <a href="ver_rutina.php?id=<?php echo $rutina_id; ?>" class="btn-volver">← Volver a Ver Rutina</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>📝 Editar: <?php echo htmlspecialchars($rutina['nombre_rutina']); ?></h2>
            <p class="subtitle">Modifica los datos de tu rutina</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($exito): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $exito; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <p>💡 <strong>Editando rutina:</strong> Puedes modificar todos los campos. Si cambias el número de días o ejercicios, los datos registrados se mantendrán. Si eliminas un día, se borrarán todos sus ejercicios y registros.</p>
            </div>
            
            <form method="POST" action="">
                <!-- Información básica -->
                <div class="form-group">
                    <label for="nombre_rutina">Nombre de la Rutina *</label>
                    <input type="text" id="nombre_rutina" name="nombre_rutina" 
                           value="<?php echo htmlspecialchars($rutina['nombre_rutina']); ?>" 
                           required placeholder="Ej: Rutina de Volumen">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" 
                              placeholder="Breve descripción de tu rutina..."><?php echo htmlspecialchars($rutina['descripcion']); ?></textarea>
                </div>
                
                <!-- Género y Nivel -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="genero">Género</label>
                        <select id="genero" name="genero">
                            <option value="masculino" <?php echo $rutina['genero'] == 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="femenino" <?php echo $rutina['genero'] == 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                            <option value="unisex" <?php echo $rutina['genero'] == 'unisex' ? 'selected' : ''; ?>>Unisex</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nivel_experiencia">Nivel de Experiencia</label>
                        <select id="nivel_experiencia" name="nivel_experiencia">
                            <option value="principiante" <?php echo $rutina['nivel_experiencia'] == 'principiante' ? 'selected' : ''; ?>>Principiante</option>
                            <option value="intermedio" <?php echo $rutina['nivel_experiencia'] == 'intermedio' ? 'selected' : ''; ?>>Intermedio</option>
                            <option value="avanzado" <?php echo $rutina['nivel_experiencia'] == 'avanzado' ? 'selected' : ''; ?>>Avanzado</option>
                        </select>
                    </div>
                </div>
                
                <!-- Descripción del Split -->
                <div class="form-group">
                    <label for="descripcion_split">Descripción del Split</label>
                    <textarea id="descripcion_split" name="descripcion_split" 
                              placeholder="Ej: Torso-Pierna, PPL, Full Body, etc."><?php echo htmlspecialchars($rutina['descripcion_split']); ?></textarea>
                </div>
                
                <!-- Video Explicativo -->
                <div class="form-group">
                    <label for="video_explicativo">Video Explicativo (URL de YouTube)</label>
                    <input type="url" id="video_explicativo" name="video_explicativo" 
                           value="<?php echo htmlspecialchars($rutina['video_explicativo']); ?>"
                           placeholder="https://www.youtube.com/watch?v=...">
                </div>
                
                <!-- Público/Privado -->
                <?php if ($_SESSION['tipo_usuario'] != 'standard'): ?>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="es_publico" name="es_publico" 
                               <?php echo $rutina['es_publico'] ? 'checked' : ''; ?>>
                        <label for="es_publico" style="margin: 0;">
                            🌐 Hacer pública esta rutina (visible para todos los usuarios)
                        </label>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Selección de Días -->
                <h3 style="margin-top: 30px; margin-bottom: 15px;">📅 Días de Entrenamiento</h3>
                
                <?php 
                $max_dias = $_SESSION['tipo_usuario'] == 'standard' ? 4 : 7;
                $dias_actuales_count = count($dias_seleccionados_actual);
                ?>
                
                <div class="info-box" style="background: <?php echo $_SESSION['tipo_usuario'] == 'standard' ? '#fff3e0' : '#e3f2fd'; ?>; border-left-color: <?php echo $_SESSION['tipo_usuario'] == 'standard' ? '#ff9800' : '#2196f3'; ?>;">
                    <?php if ($_SESSION['tipo_usuario'] == 'standard'): ?>
                        <p><strong>⚠️ Plan Standard:</strong> Máximo <strong>4 días</strong> por semana. 
                        Actualmente tienes <strong><?php echo $dias_actuales_count; ?>/4</strong> días.
                        <?php if ($dias_actuales_count >= 4): ?>
                            <br>🔒 <strong>Has alcanzado el límite.</strong> Actualiza a Premium para 7 días.
                        <?php else: ?>
                            <br>Puedes agregar <strong><?php echo (4 - $dias_actuales_count); ?></strong> día(s) más.
                        <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p><strong>👑 Plan Premium:</strong> Puedes tener hasta <strong>7 días</strong> de entrenamiento por semana. 
                        Actualmente tienes <strong><?php echo $dias_actuales_count; ?>/7</strong> días.</p>
                    <?php endif; ?>
                </div>
                
                <p style="color: #666; margin-bottom: 15px;">
                    Selecciona los días que entrenas y configura cada uno:
                </p>
                
                <div class="dias-grid">
                    <?php 
                    $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                    foreach ($dias_semana as $dia): 
                        $esta_seleccionado = in_array($dia, $dias_seleccionados_actual);
                    ?>
                        <div class="dia-checkbox <?php echo $esta_seleccionado ? 'selected' : ''; ?>" 
                             onclick="toggleDia('<?php echo $dia; ?>', this)">
                            <input type="checkbox" name="dias[]" value="<?php echo $dia; ?>" 
                                   id="dia_<?php echo $dia; ?>" 
                                   <?php echo $esta_seleccionado ? 'checked' : ''; ?>
                                   onclick="event.stopPropagation();">
                            <label for="dia_<?php echo $dia; ?>" style="cursor: pointer; margin: 0;">
                                📆 <?php echo $dia; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Detalles de cada día -->
                <div id="dias-detalles">
                    <?php foreach ($dias_semana as $dia): 
                        $esta_seleccionado = in_array($dia, $dias_seleccionados_actual);
                        $num_ejercicios = $ejercicios_por_dia_actual[$dia] ?? 3;
                        $grupos = $grupos_musculares_actual[$dia] ?? '';
                    ?>
                        <div class="dia-details" id="detalles_<?php echo $dia; ?>" 
                             style="display: <?php echo $esta_seleccionado ? 'block' : 'none'; ?>;">
                            <h4>📋 Configuración de <?php echo $dia; ?></h4>
                            
                            <label>Número de ejercicios:</label>
                            <input type="number" name="ejercicios_<?php echo $dia; ?>" 
                                   min="1" max="<?php echo $_SESSION['tipo_usuario'] == 'standard' ? '3' : '20'; ?>" 
                                   value="<?php echo $num_ejercicios; ?>"
                                   placeholder="Ej: 5">
                            
                            <label>Grupos musculares * (requerido):</label>
                            <input type="text" name="grupos_<?php echo $dia; ?>" 
                                   value="<?php echo htmlspecialchars($grupos); ?>"
                                   placeholder="Ej: Pecho, Tríceps, Hombros" 
                                   <?php echo $esta_seleccionado ? 'required' : ''; ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Botones -->
                <div class="btn-group">
                    <a href="ver_rutina.php?id=<?php echo $rutina_id; ?>" class="btn-secondary">
                        ❌ Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        💾 Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <script>
        const maxDias = <?php echo $max_dias; ?>;
        const tipoUsuario = '<?php echo $_SESSION['tipo_usuario']; ?>';
        
        // Sistema de toasts
        function showToast(message, type = 'warning') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icon = type === 'warning' ? '⚠️' : type === 'error' ? '❌' : '✅';
            toast.innerHTML = `<span style="font-size: 20px;">${icon}</span><span>${message}</span>`;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function toggleDia(dia, element) {
            const checkbox = document.getElementById('dia_' + dia);
            const detalles = document.getElementById('detalles_' + dia);
            const gruposInput = detalles.querySelector('input[name="grupos_' + dia + '"]');
            
            const diasSeleccionados = document.querySelectorAll('input[name="dias[]"]:checked').length;
            
            if (!checkbox.checked) {
                if (tipoUsuario === 'standard' && diasSeleccionados >= maxDias) {
                    showToast('Plan Standard: Máximo 4 días por semana. Actualiza a Premium para 7 días.', 'warning');
                    return;
                }
            }
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                element.classList.add('selected');
                detalles.style.display = 'block';
                gruposInput.required = true;
            } else {
                element.classList.remove('selected');
                detalles.style.display = 'none';
                gruposInput.required = false;
            }
            
            actualizarContador();
        }
        
        function actualizarContador() {
            const diasSeleccionados = document.querySelectorAll('input[name="dias[]"]:checked').length;
            const infoBox = document.querySelector('.info-box');
            
            if (tipoUsuario === 'standard') {
                const diasRestantes = maxDias - diasSeleccionados;
                const mensaje = diasSeleccionados >= maxDias 
                    ? '🔒 <strong>Has alcanzado el límite.</strong> Actualiza a Premium para 7 días.'
                    : 'Puedes agregar <strong>' + diasRestantes + '</strong> día(s) más.';
                    
                infoBox.innerHTML = '<p><strong>⚠️ Plan Standard:</strong> Máximo <strong>4 días</strong> por semana. ' +
                    'Actualmente tienes <strong>' + diasSeleccionados + '/4</strong> días.<br>' + mensaje + '</p>';
            } else {
                infoBox.innerHTML = '<p><strong>👑 Plan Premium:</strong> Puedes tener hasta <strong>7 días</strong> de entrenamiento por semana. ' +
                    'Actualmente tienes <strong>' + diasSeleccionados + '/7</strong> días.</p>';
            }
        }
        
        document.querySelector('form').addEventListener('submit', function(e) {
            const diasSeleccionados = document.querySelectorAll('input[name="dias[]"]:checked');
            
            if (diasSeleccionados.length === 0) {
                e.preventDefault();
                showToast('Debes seleccionar al menos un día de entrenamiento', 'error');
                return false;
            }
            
            if (tipoUsuario === 'standard' && diasSeleccionados.length > maxDias) {
                e.preventDefault();
                showToast('Plan Standard: Máximo ' + maxDias + ' días por semana. Actualiza a Premium.', 'error');
                return false;
            }
            
            let error = false;
            diasSeleccionados.forEach(function(checkbox) {
                const dia = checkbox.value;
                const gruposInput = document.querySelector('input[name="grupos_' + dia + '"]');
                
                if (!gruposInput.value.trim()) {
                    error = true;
                    showToast('El día ' + dia + ' necesita especificar los grupos musculares', 'error');
                }
            });
            
            if (error) {
                e.preventDefault();
                return false;
            }
        });
        
        actualizarContador();
    </script>
</body>
</html>