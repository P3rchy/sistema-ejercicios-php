<?php
require_once 'config.php';
requiereLogin();

$error = '';
$exito = '';

// Obtener ID del ejercicio
$ejercicio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ejercicio_id == 0) {
    header('Location: biblioteca_ejercicios.php');
    exit();
}

$conn = getConnection();

// Verificar que el ejercicio existe y el usuario tiene permisos
$stmt = $conn->prepare("SELECT b.*, c.nombre as categoria_nombre 
                        FROM biblioteca_ejercicios b 
                        JOIN categorias_ejercicios c ON b.categoria_id = c.id 
                        WHERE b.id = ?");
$stmt->bind_param("i", $ejercicio_id);
$stmt->execute();
$ejercicio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ejercicio) {
    header('Location: biblioteca_ejercicios.php');
    exit();
}

// Verificar permisos (creador, admin o premium_pro)
$tipo_lower = strtolower($_SESSION['tipo_usuario']);
if ($ejercicio['usuario_id'] != $_SESSION['usuario_id'] && $tipo_lower != 'admin' && $tipo_lower != 'premium_pro') {
    header('Location: biblioteca_ejercicios.php');
    exit();
}

// Obtener todas las categorías
$categorias = $conn->query("SELECT * FROM categorias_ejercicios ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_ejercicio = trim($_POST['nombre_ejercicio']);
    $categoria_id = intval($_POST['categoria_id']);
    $descripcion = trim($_POST['descripcion']);
    $imagen_url = trim($_POST['imagen_url']);
    $video_url = trim($_POST['video_url']);
    $es_publico = isset($_POST['es_publico']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre_ejercicio)) {
        $error = 'El nombre del ejercicio es obligatorio';
    } elseif ($categoria_id == 0) {
        $error = 'Debes seleccionar una categoría';
    } else {
        // Actualizar ejercicio
        $stmt = $conn->prepare("UPDATE biblioteca_ejercicios 
                               SET nombre_ejercicio = ?, categoria_id = ?, descripcion = ?, 
                                   imagen_url = ?, video_url = ?, es_publico = ? 
                               WHERE id = ?");
        $stmt->bind_param("sisssii", $nombre_ejercicio, $categoria_id, $descripcion, 
                         $imagen_url, $video_url, $es_publico, $ejercicio_id);
        
        if ($stmt->execute()) {
            $exito = 'Ejercicio actualizado exitosamente. Redirigiendo...';
            header("refresh:2;url=biblioteca_ejercicios.php");
        } else {
            $error = 'Error al actualizar el ejercicio';
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ejercicio - Sistema de Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="notifications.css">
</head>
<body>
    <div class="navbar">
        <h1>✏️ Editar Ejercicio</h1>
        <a href="javascript:history.back()" class="btn-volver">← Volver</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Editar Ejercicio de la Biblioteca</h2>
            <p class="subtitle">Modifica la información del ejercicio</p>
            
            <?php if ($error): ?>
                <div class="mensaje error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($exito): ?>
                <div class="mensaje exito">✅ <?php echo $exito; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formEjercicio">
                <div class="form-section">
                    <div class="section-title">📋 Información del Ejercicio</div>
                    
                    <div class="form-group">
                        <label for="nombre_ejercicio">
                            <span class="icon">💪</span>Nombre del Ejercicio *
                        </label>
                        <input type="text" id="nombre_ejercicio" name="nombre_ejercicio" 
                               value="<?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?>" 
                               placeholder="Ej: Press de Banca, Sentadillas, Dominadas..." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria_id">
                            <span class="icon">🏷️</span>Categoría (Grupo Muscular) *
                        </label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">-- Selecciona una categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $ejercicio['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">
                            <span class="icon">📝</span>Descripción (Opcional)
                        </label>
                        <textarea id="descripcion" name="descripcion" rows="4"
                                  placeholder="Describe cómo realizar el ejercicio, puntos clave, etc..."><?php echo htmlspecialchars($ejercicio['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagen_url">
                            <span class="icon">🖼️</span>URL de Imagen (Opcional)
                        </label>
                        <input type="url" id="imagen_url" name="imagen_url" 
                               value="<?php echo htmlspecialchars($ejercicio['imagen_url'] ?? ''); ?>"
                               placeholder="https://ejemplo.com/imagen.jpg">
                        <small style="color: #666; font-size: 12px;">
                            Link directo a una imagen que muestre el ejercicio
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="video_url">
                            <span class="icon">🎥</span>URL de Video (Opcional)
                        </label>
                        <input type="url" id="video_url" name="video_url" 
                               value="<?php echo htmlspecialchars($ejercicio['video_url'] ?? ''); ?>"
                               placeholder="https://www.youtube.com/watch?v=...">
                        <small style="color: #666; font-size: 12px;">
                            Link a YouTube o similar con tutorial del ejercicio
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; font-weight: bold;">
                            🌐 Visibilidad
                        </label>
                        <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                            <div class="checkbox-group" style="margin-bottom: 10px;">
                                <input type="checkbox" id="es_publico" name="es_publico" value="1"
                                       <?php echo $ejercicio['es_publico'] ? 'checked' : ''; ?>>
                                <label for="es_publico" style="margin: 0; font-weight: bold;">
                                    Hacer público este ejercicio
                                </label>
                            </div>
                            <p style="margin: 0; font-size: 14px; color: #666;">
                                Los ejercicios públicos son visibles para todos los usuarios en la biblioteca.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="javascript:history.back()" class="btn-secondary">
                        ❌ Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        💾 Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de confirmación -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box">
            <div class="modal-title" id="modalTitle">Confirmar acción</div>
            <div class="modal-message" id="modalMessage"></div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancelar</button>
                <button class="modal-btn modal-btn-confirm" id="modalConfirmBtn" onclick="confirmAction()">Aceptar</button>
            </div>
        </div>
    </div>
    
    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <script src="notifications.js"></script>
    
    <script>
        // Validación adicional del formulario
        document.getElementById('formEjercicio').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre_ejercicio').value.trim();
            const categoria = document.getElementById('categoria_id').value;
            
            if (!nombre) {
                e.preventDefault();
                showToast('El nombre del ejercicio es obligatorio', 'error');
                return;
            }
            
            if (!categoria) {
                e.preventDefault();
                showToast('Debes seleccionar una categoría', 'error');
                return;
            }
        });
    </script>
</body>
</html>