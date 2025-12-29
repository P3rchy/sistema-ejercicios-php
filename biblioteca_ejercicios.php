<?php
require_once 'config.php';
requiereLogin();

$conn = getConnection();

// Filtro por categoría
$categoria_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

// Obtener categorías
$categorias = $conn->query("SELECT * FROM categorias_ejercicios ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener ejercicios de la biblioteca
if ($categoria_filtro > 0) {
    $stmt = $conn->prepare("SELECT b.*, c.nombre as categoria_nombre, u.nombre_completo as creador 
                           FROM biblioteca_ejercicios b 
                           JOIN categorias_ejercicios c ON b.categoria_id = c.id 
                           JOIN usuarios u ON b.usuario_id = u.id
                           WHERE b.categoria_id = ? AND b.es_publico = 1
                           ORDER BY b.nombre_ejercicio");
    $stmt->bind_param("i", $categoria_filtro);
    $stmt->execute();
    $ejercicios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $ejercicios = $conn->query("SELECT b.*, c.nombre as categoria_nombre, u.nombre_completo as creador 
                               FROM biblioteca_ejercicios b 
                               JOIN categorias_ejercicios c ON b.categoria_id = c.id 
                               JOIN usuarios u ON b.usuario_id = u.id
                               WHERE b.es_publico = 1
                               ORDER BY c.nombre, b.nombre_ejercicio")->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

// Mensajes de sesión
$mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : '';
$mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : '';
unset($_SESSION['mensaje_exito']);
unset($_SESSION['mensaje_error']);

// Debug: Ver tipo de usuario
// echo "DEBUG: tipo_usuario = " . $_SESSION['tipo_usuario'] . " | puede_agregar = " . ($puede_agregar ? 'true' : 'false');

$tipo_lower = strtolower($_SESSION['tipo_usuario']);
$puede_agregar = ($tipo_lower == 'admin' || $tipo_lower == 'premium' || $tipo_lower == 'premium_pro');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Ejercicios - Sistema de Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="notifications.css">
</head>
<body>
    <div class="navbar">
        <h1>📚 Biblioteca de Ejercicios</h1>
        <a href="index.php" class="btn-volver">← Volver al Inicio</a>
    </div>
    
    <div class="container">
        <!-- Header con botón agregar -->
        <div class="card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="margin-bottom: 10px;">Ejercicios Disponibles</h2>
                    <p class="subtitle">
                        <?php if ($puede_agregar): ?>
                            Explora y agrega nuevos ejercicios a la biblioteca
                        <?php else: ?>
                            Explora los ejercicios disponibles. <span class="limite-standard">Actualiza a Premium para agregar tus propios ejercicios</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php if ($puede_agregar): ?>
                        <a href="crear_ejercicio_biblioteca.php" class="btn-primary" style="white-space: nowrap;">
                            ➕ Agregar Ejercicio
                        </a>
                        <a href="admin_categorias.php" class="btn-secondary" style="white-space: nowrap;">
                            🏷️ Administrar Categorías
                        </a>
                    <?php else: ?>
                        <button class="btn-disabled" onclick="mostrarMensajePremium()">
                            🔒 Agregar a Biblioteca
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($mensaje_exito): ?>
            <div class="mensaje exito" style="margin-bottom: 20px;">
                ✅ <?php echo $mensaje_exito; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mensaje_error): ?>
            <div class="mensaje error" style="margin-bottom: 20px;">
                ❌ <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Filtros por categoría -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px;">🔍 Filtrar por Categoría</h3>
            <div class="categorias-filtro">
                <a href="biblioteca_ejercicios.php" class="categoria-filtro-item <?php echo $categoria_filtro == 0 ? 'active' : ''; ?>">
                    🌐 Todas
                </a>
                <?php foreach ($categorias as $cat): ?>
                    <a href="biblioteca_ejercicios.php?categoria=<?php echo $cat['id']; ?>" 
                       class="categoria-filtro-item <?php echo $categoria_filtro == $cat['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Lista de ejercicios -->
        <div class="card">
            <h3 style="margin-bottom: 20px;">
                Ejercicios 
                <?php if ($categoria_filtro > 0): ?>
                    - <?php 
                        $cat_actual = array_filter($categorias, function($c) use ($categoria_filtro) { 
                            return $c['id'] == $categoria_filtro; 
                        });
                        $cat_actual = reset($cat_actual);
                        echo htmlspecialchars($cat_actual['nombre']); 
                    ?>
                <?php endif; ?>
                <span class="badge-count"><?php echo count($ejercicios); ?></span>
            </h3>
            
            <?php if (empty($ejercicios)): ?>
                <div class="no-ejercicios-biblioteca">
                    <p style="font-size: 48px; margin-bottom: 15px;">📭</p>
                    <h3>No hay ejercicios en esta categoría</h3>
                    <p>Sé el primero en agregar un ejercicio</p>
                </div>
            <?php else: ?>
                <div class="ejercicios-biblioteca-grid">
                    <?php foreach ($ejercicios as $ejercicio): ?>
                        <div class="ejercicio-biblioteca-card">
                            <div class="ejercicio-header">
                                <div class="categoria-badge">
                                    <?php echo htmlspecialchars($ejercicio['categoria_nombre']); ?>
                                </div>
                                <?php 
                                $tipo_lower = strtolower($_SESSION['tipo_usuario']);
                                if ($ejercicio['usuario_id'] == $_SESSION['usuario_id'] || $tipo_lower == 'admin' || $tipo_lower == 'premium_pro'): 
                                ?>
                                    <div class="ejercicio-acciones">
                                        <a href="editar_ejercicio_biblioteca.php?id=<?php echo $ejercicio['id']; ?>" class="btn-icon" title="Editar">
                                            ✏️
                                        </a>
                                        <button onclick="confirmarEliminar(<?php echo $ejercicio['id']; ?>)" class="btn-icon" title="Eliminar">
                                            🗑️
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="ejercicio-nombre"><?php echo htmlspecialchars($ejercicio['nombre_ejercicio']); ?></h4>
                            
                            <?php if ($ejercicio['descripcion']): ?>
                                <p class="ejercicio-descripcion"><?php echo htmlspecialchars($ejercicio['descripcion']); ?></p>
                            <?php endif; ?>
                            
                            <div class="ejercicio-recursos">
                                <?php if ($ejercicio['imagen_url']): ?>
                                    <a href="<?php echo htmlspecialchars($ejercicio['imagen_url']); ?>" target="_blank" class="recurso-link">
                                        🖼️ Ver Imagen
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($ejercicio['video_url']): ?>
                                    <a href="<?php echo htmlspecialchars($ejercicio['video_url']); ?>" target="_blank" class="recurso-link">
                                        🎥 Ver Video
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ejercicio-footer">
                                <small>👤 <?php echo htmlspecialchars($ejercicio['creador']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function mostrarMensajePremium() {
            showModal(
                '🔒 Función Premium',
                'Actualiza a Premium para agregar tus propios ejercicios a la biblioteca y acceder a funciones ilimitadas.',
                () => {
                    // Usuario acepta, podrías redirigir a planes.php
                },
                false
            );
        }
        
        function confirmarEliminar(id) {
            showModal(
                '¿Eliminar ejercicio?',
                'Esta acción no se puede deshacer.',
                () => {
                    window.location.href = 'eliminar_ejercicio_biblioteca.php?id=' + id;
                },
                true
            );
        }

    </script>
    
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
        // Mostrar toasts automáticamente si hay mensajes
        <?php if ($mensaje_exito): ?>
            showToast('<?php echo addslashes($mensaje_exito); ?>', 'success');
        <?php endif; ?>
        
        <?php if ($mensaje_error): ?>
            showToast('<?php echo addslashes($mensaje_error); ?>', 'error');
        <?php endif; ?>
    </script>
</body>
</html>