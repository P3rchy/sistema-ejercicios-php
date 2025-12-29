<?php
require_once 'config.php';
requiereLogin();

$ejercicio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rutina_id = isset($_GET['rutina_id']) ? intval($_GET['rutina_id']) : 0;

if ($ejercicio_id == 0 || $rutina_id == 0) {
    header('Location: mis_rutinas.php');
    exit();
}

$conn = getConnection();

// Obtener ejercicio
$stmt = $conn->prepare("
    SELECT e.*, dr.dia_semana, dr.grupos_musculares 
    FROM ejercicios e 
    JOIN dias_rutina dr ON e.dia_rutina_id = dr.id 
    JOIN rutinas r ON dr.rutina_id = r.id 
    WHERE e.id = ? AND r.usuario_id = ?
");
$stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
$stmt->execute();
$ejercicio = $stmt->get_result()->fetch_assoc();

if (!$ejercicio) {
    header('Location: mis_rutinas.php');
    exit();
}

$error = '';
$exito = '';

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        $stmt = $conn->prepare("
            UPDATE ejercicios 
            SET nombre_ejercicio = ?, imagen_url = ?, video_url = ?, objetivo_serie = ?, 
                num_series = ?, num_sesiones = ?, descanso_minutos = ?, descanso_segundos = ?, rir_rpe = ? 
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->bind_param("ssssiiiisii", $nombre_ejercicio, $imagen_url, $video_url, $objetivo_serie, 
            $num_series, $num_sesiones, $descanso_minutos, $descanso_segundos, $rir_rpe, $ejercicio_id, $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            $exito = 'Ejercicio actualizado exitosamente';
            header("refresh:1;url=agregar_ejercicios.php?rutina_id=$rutina_id");
        } else {
            $error = 'Error al actualizar el ejercicio';
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ejercicio</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <h1>✏️ Editar Ejercicio</h1>
        <a href="javascript:history.back()" class="btn-volver">← Volver</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h2><?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?></h2>
            <p class="subtitle"><?php echo $ejercicio['dia_semana']; ?> - <?php echo $ejercicio['grupos_musculares']; ?></p>
            
            <?php if ($error): ?>
                <div class="mensaje error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($exito): ?>
                <div class="mensaje exito">✅ <?php echo $exito; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nombre del Ejercicio *</label>
                    <input type="text" name="nombre_ejercicio" value="<?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>URL de Imagen</label>
                    <input type="url" name="imagen_url" value="<?php echo htmlspecialchars($ejercicio['imagen_url']); ?>" placeholder="https://...">
                </div>
                
                <div class="form-group">
                    <label>URL de Video (YouTube)</label>
                    <input type="url" name="video_url" value="<?php echo htmlspecialchars($ejercicio['video_url']); ?>" placeholder="https://www.youtube.com/...">
                </div>
                
                <div class="form-group">
                    <label>Objetivo de la Serie</label>
                    <select name="objetivo_serie">
                        <option value="fuerza" <?php echo $ejercicio['objetivo_serie'] == 'fuerza' ? 'selected' : ''; ?>>Fuerza</option>
                        <option value="hipertrofia" <?php echo $ejercicio['objetivo_serie'] == 'hipertrofia' ? 'selected' : ''; ?>>Hipertrofia</option>
                        <option value="resistencia" <?php echo $ejercicio['objetivo_serie'] == 'resistencia' ? 'selected' : ''; ?>>Resistencia</option>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Series *</label>
                        <input type="number" name="num_series" value="<?php echo $ejercicio['num_series']; ?>" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Sesiones *</label>
                        <input type="number" name="num_sesiones" value="<?php echo $ejercicio['num_sesiones']; ?>" min="1" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Descanso (minutos)</label>
                        <input type="number" name="descanso_minutos" value="<?php echo $ejercicio['descanso_minutos']; ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Descanso (segundos)</label>
                        <input type="number" name="descanso_segundos" value="<?php echo $ejercicio['descanso_segundos']; ?>" min="0" max="59">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>RIR/RPE</label>
                    <input type="text" name="rir_rpe" value="<?php echo htmlspecialchars($ejercicio['rir_rpe']); ?>" placeholder="Ej: 2-3">
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <a href="agregar_ejercicios.php?rutina_id=<?php echo $rutina_id; ?>" class="btn-secondary" style="flex: 1; text-align: center;">
                        ❌ Cancelar
                    </a>
                    <button type="submit" class="btn-primary" style="flex: 1;">
                        💾 Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>