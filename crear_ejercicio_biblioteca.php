<?php
require_once 'config.php';
requiereLogin();

// Verificar que sea Premium, Premium Pro o Admin
if ($_SESSION['tipo_usuario'] == 'standard') {
    header('Location: biblioteca_ejercicios.php');
    exit();
}

$error = '';
$exito = '';

$conn = getConnection();

// Obtener categorías
$categorias = $conn->query("SELECT * FROM categorias_ejercicios ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $categoria_id = intval($_POST['categoria_id']);
    $nombre_ejercicio = trim($_POST['nombre_ejercicio']);
    $descripcion = trim($_POST['descripcion']);
    $imagen_url = trim($_POST['imagen_url']);
    $video_url = trim($_POST['video_url']);
    $es_publico = isset($_POST['es_publico']) ? 1 : 0;
    $es_privado = isset($_POST['es_privado']) ? 1 : 0;
    
    // Validaciones
    if ($categoria_id == 0) {
        $error = 'Debes seleccionar una categoría';
    } elseif (empty($nombre_ejercicio)) {
        $error = 'El nombre del ejercicio es obligatorio';
    } else {
        // Verificar si ya existe un ejercicio con el mismo nombre en la misma categoría
        $stmt = $conn->prepare("SELECT id FROM biblioteca_ejercicios WHERE nombre_ejercicio = ? AND categoria_id = ? AND usuario_id = ?");
        $stmt->bind_param("sii", $nombre_ejercicio, $categoria_id, $_SESSION['usuario_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Ya tienes un ejercicio con ese nombre en esta categoría';
        } else {
            // Insertar ejercicio
            $stmt = $conn->prepare("INSERT INTO biblioteca_ejercicios (usuario_id, categoria_id, nombre_ejercicio, descripcion, imagen_url, video_url, es_publico, es_privado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssii", $_SESSION['usuario_id'], $categoria_id, $nombre_ejercicio, $descripcion, $imagen_url, $video_url, $es_publico, $es_privado);
            
            if ($stmt->execute()) {
                $exito = 'Ejercicio agregado a la biblioteca exitosamente';
                header('refresh:2;url=biblioteca_ejercicios.php');
            } else {
                $error = 'Error al agregar el ejercicio';
            }
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
    <title>Agregar a Biblioteca - Sistema de Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <h1>➕ Agregar Ejercicio a Biblioteca</h1>
        <a href="biblioteca_ejercicios.php" class="btn-volver">← Volver a Biblioteca</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Nuevo Ejercicio para la Biblioteca</h2>
            <p class="subtitle">Agrega ejercicios que podrás reutilizar en tus rutinas</p>
            
            <div class="info-box">
                <p><strong>💡 Sobre la privacidad:</strong></p>
                <p><strong>Público:</strong> Otros usuarios podrán ver y usar este ejercicio en sus rutinas.</p>
                <p><strong>Privado:</strong> Las URLs de imagen y video solo serán visibles para ti. Otros usuarios verán el nombre pero no tus recursos.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="mensaje error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($exito): ?>
                <div class="mensaje exito">✅ <?php echo $exito; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-section">
                    <div class="section-title">🏋️ Información del Ejercicio</div>
                    
                    <div class="form-group">
                        <label for="categoria_id">Categoría / Grupo Muscular *</label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="0">Selecciona una categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo $cat['icono'] . ' ' . htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="helper-text">Selecciona el grupo muscular principal de este ejercicio</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre_ejercicio">Nombre del Ejercicio *</label>
                        <input type="text" id="nombre_ejercicio" name="nombre_ejercicio" 
                               placeholder="Ej: Press de Banca Inclinado, Sentadilla Búlgara..." required>
                        <div class="helper-text">Usa un nombre descriptivo y único</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción (Opcional)</label>
                        <textarea id="descripcion" name="descripcion" 
                                  placeholder="Describe la técnica, variación o beneficios del ejercicio..."></textarea>
                        <div class="helper-text">Ayuda a otros usuarios a entender mejor el ejercicio</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">📎 Recursos Multimedia (Opcional)</div>
                    
                    <div class="form-group">
                        <label for="imagen_url">URL de Imagen</label>
                        <input type="url" id="imagen_url" name="imagen_url" 
                               placeholder="https://ejemplo.com/imagen.jpg">
                        <div class="helper-text">Imagen de referencia del ejercicio</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="video_url">URL de Video Tutorial</label>
                        <input type="url" id="video_url" name="video_url" 
                               placeholder="https://youtube.com/watch?v=...">
                        <div class="helper-text">Video explicativo de la técnica (YouTube, Vimeo, etc.)</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">🔒 Configuración de Privacidad</div>
                    
                    <div class="form-group">
                        <div class="checkbox-group" style="background: #e8f5e9; border-color: #4caf50;">
                            <input type="checkbox" id="es_publico" name="es_publico" checked>
                            <label for="es_publico" style="color: #2e7d32;">
                                🌐 Hacer este ejercicio público (otros usuarios pueden verlo y usarlo)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group" style="background: #fff3e0; border-color: #ff9800;">
                            <input type="checkbox" id="es_privado" name="es_privado">
                            <label for="es_privado" style="color: #e65100;">
                                🔒 Proteger URLs (imagen y video solo visibles para mí)
                            </label>
                        </div>
                        
                        <!-- Mensaje de advertencia URLs protegidas -->
                        <div id="mensaje-protegido" class="alert-limite" style="display: none; margin-top: 10px;">
                            🔒 Las URLs de imagen y video estarán protegidas. Otros usuarios verán el ejercicio pero no tus recursos multimedia.
                        </div>
                    </div>
                    
                    <!-- Mensaje cuando se desmarca público -->
                    <div id="mensaje-privado" class="alert-limite" style="display: none; background: #fff3cd;">
                        🔒 Este ejercicio será completamente privado. Solo tú podrás verlo y usarlo en tus rutinas.
                    </div>
                    
                    <div class="info-box" style="background: #e3f2fd; border-left-color: #2196f3; margin-top: 15px;">
                        <p><strong>📋 Combinaciones posibles:</strong></p>
                        <p>✅ <strong>Público + No protegido:</strong> Todos ven todo (ideal para compartir)</p>
                        <p>🔓 <strong>Público + Protegido:</strong> Todos ven el nombre/descripción, solo tú ves las URLs</p>
                        <p>🔒 <strong>No público:</strong> Solo tú puedes ver y usar este ejercicio</p>
                    </div>
                </div>
                
                <button type="submit">💾 Guardar en Biblioteca</button>
            </form>
        </div>
    </div>
    
    <script>
        // Advertencias visuales en lugar de alerts
        const mensajePrivado = document.getElementById('mensaje-privado');
        const mensajeProtegido = document.getElementById('mensaje-protegido');
        
        // Mostrar/ocultar mensajes según checkboxes
        document.getElementById('es_privado').addEventListener('change', function() {
            if (this.checked) {
                mensajeProtegido.style.display = 'block';
            } else {
                mensajeProtegido.style.display = 'none';
            }
        });
        
        document.getElementById('es_publico').addEventListener('change', function() {
            if (!this.checked) {
                mensajePrivado.style.display = 'block';
            } else {
                mensajePrivado.style.display = 'none';
            }
        });
    </script>
</body>
</html>