<?php
require_once 'config.php';
requiereLogin();

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_rutina = trim($_POST['nombre_rutina']);
    $descripcion = trim($_POST['descripcion']);
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
        $error = 'Los usuarios Standard pueden crear rutinas de máximo 4 días. Actualiza a Premium para días ilimitados.';
    } else {
        // Validar límite de ejercicios por día para usuarios standard
        foreach ($ejercicios_por_dia as $dia => $num_ejercicios) {
            if ($_SESSION['tipo_usuario'] == 'standard' && $num_ejercicios > 3) {
                $error = "Los usuarios Standard pueden crear máximo 3 ejercicios por día. Actualiza a Premium.";
                break;
            }
        }
        
        if (empty($error)) {
            $conn = getConnection();
            
            // Crear la rutina
            $num_dias = count($dias_seleccionados);
            $stmt = $conn->prepare("INSERT INTO rutinas (usuario_id, nombre_rutina, descripcion, num_dias_semana) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $_SESSION['usuario_id'], $nombre_rutina, $descripcion, $num_dias);
            
            if ($stmt->execute()) {
                $rutina_id = $conn->insert_id;
                
                // Crear los días
                foreach ($dias_seleccionados as $dia) {
                    $num_ejercicios = $ejercicios_por_dia[$dia];
                    $grupos = $grupos_musculares[$dia];
                    $stmt = $conn->prepare("INSERT INTO dias_rutina (rutina_id, dia_semana, num_ejercicios, grupos_musculares) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isis", $rutina_id, $dia, $num_ejercicios, $grupos);
                    $stmt->execute();
                }
                
                $exito = 'Rutina creada exitosamente. Redirigiendo para agregar ejercicios...';
                header("refresh:2;url=agregar_ejercicios.php?rutina_id=$rutina_id");
            } else {
                $error = 'Error al crear la rutina';
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Rutina - Sistema de Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <h1>📋 Crear Nueva Rutina</h1>
        <a href="index.php" class="btn-volver">← Volver al Inicio</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Nueva Rutina de Entrenamiento</h2>
            <p class="subtitle">Paso 1: Configura tu rutina semanal</p>
            
            <div class="info-box">
                <p>💡 <strong>¿Cómo funciona?</strong> Primero crea tu rutina, selecciona los días de entrenamiento y cuántos ejercicios harás cada día. Luego podrás agregar los ejercicios uno por uno.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="mensaje error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($exito): ?>
                <div class="mensaje exito">✅ <?php echo $exito; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formRutina">
                <!-- Información Básica -->
                <div class="form-section">
                    <div class="section-title">📝 Información Básica</div>
                    
                    <div class="form-group">
                        <label for="nombre_rutina"><span class="icon">🏋️</span>Nombre de la Rutina *</label>
                        <input type="text" id="nombre_rutina" name="nombre_rutina" 
                               placeholder="Ej: PowerMax 4 días, Full Body, Upper/Lower..." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion"><span class="icon">📄</span>Descripción (Opcional)</label>
                        <textarea id="descripcion" name="descripcion" 
                                  placeholder="Describe tu rutina, objetivos, etc..."></textarea>
                    </div>
                </div>
                
                <!-- Selección de Días -->
                <div class="form-section">
                    <div class="section-title">📅 Días de Entrenamiento</div>
                    <p class="helper-text">Selecciona los días en los que entrenarás (máximo 4 días para usuarios Standard)</p>
                    
                    <div id="alertDias" class="alert-limite" style="display: none;">
                        ⚠️ Has alcanzado el límite de <strong><?php echo $_SESSION['tipo_usuario'] == 'standard' ? '4' : '7'; ?> días</strong> para usuarios <?php echo ucfirst($_SESSION['tipo_usuario']); ?>
                    </div>
                    
                    <div class="dias-grid">
                        <?php
                        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                        foreach ($dias as $dia):
                        ?>
                        <div class="dia-checkbox">
                            <input type="checkbox" id="dia_<?php echo $dia; ?>" name="dias[]" value="<?php echo $dia; ?>" onchange="toggleEjerciciosDia('<?php echo $dia; ?>')">
                            <label for="dia_<?php echo $dia; ?>"><?php echo $dia; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Ejercicios por Día -->
                <div class="form-section" id="ejerciciosPorDia" style="display: none;">
                    <div class="section-title">🔢 Ejercicios por Día</div>
                    <p class="helper-text">Indica cuántos ejercicios realizarás en cada día seleccionado 
                        <span class="limite-standard">(máximo <?php echo $_SESSION['tipo_usuario'] == 'standard' ? '3' : '20'; ?> para usuarios <?php echo ucfirst($_SESSION['tipo_usuario']); ?>)</span>
                    </p>
                    
                    <div id="ejerciciosContainer"></div>
                </div>
                
                <button type="submit">✨ Crear Rutina y Agregar Ejercicios</button>
            </form>
        </div>
    </div>
    
    <script>
        const tipoUsuario = '<?php echo $_SESSION['tipo_usuario']; ?>';
        const maxDias = tipoUsuario === 'standard' ? 4 : 7;
        const maxEjercicios = tipoUsuario === 'standard' ? 3 : 20;
        
        function toggleEjerciciosDia(dia) {
            const checkbox = document.getElementById('dia_' + dia);
            const container = document.getElementById('ejerciciosContainer');
            const ejerciciosSection = document.getElementById('ejerciciosPorDia');
            const alertDias = document.getElementById('alertDias');
            
            // Verificar límite de días
            const diasSeleccionados = document.querySelectorAll('input[name="dias[]"]:checked').length;
            
            if (diasSeleccionados > maxDias) {
                checkbox.checked = false;
                alertDias.style.display = 'block';
                setTimeout(() => {
                    alertDias.style.display = 'none';
                }, 4000);
                return;
            } else {
                alertDias.style.display = 'none';
            }
            
            if (checkbox.checked) {
                // Agregar selector de ejercicios
                const div = document.createElement('div');
                div.id = 'ejercicios_' + dia;
                div.className = 'ejercicios-dia-item';
                div.innerHTML = `
                    <div style="width: 100%;">
                        <label style="margin-bottom: 10px;">${dia}:</label>
                        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                            <input type="number" name="ejercicios_${dia}" min="1" max="${maxEjercicios}" value="3" required style="width: 80px;">
                            <span class="helper-text">ejercicios</span>
                        </div>
                        <input type="text" name="grupos_${dia}" placeholder="Grupos musculares (ej: Pecho, Bíceps, Abs)" style="width: 100%; margin-top: 5px;">
                    </div>
                `;
                container.appendChild(div);
                ejerciciosSection.style.display = 'block';
            } else {
                // Eliminar selector
                const div = document.getElementById('ejercicios_' + dia);
                if (div) div.remove();
                
                if (container.children.length === 0) {
                    ejerciciosSection.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>