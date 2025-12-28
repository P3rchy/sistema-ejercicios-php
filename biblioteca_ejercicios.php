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
    $stmt = $conn->prepare("SELECT b.*, c.nombre as categoria_nombre, c.icono, u.nombre_completo as creador 
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
    $ejercicios = $conn->query("SELECT b.*, c.nombre as categoria_nombre, c.icono, u.nombre_completo as creador 
                               FROM biblioteca_ejercicios b 
                               JOIN categorias_ejercicios c ON b.categoria_id = c.id 
                               JOIN usuarios u ON b.usuario_id = u.id
                               WHERE b.es_publico = 1
                               ORDER BY c.nombre, b.nombre_ejercicio")->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

$puede_agregar = ($_SESSION['tipo_usuario'] == 'admin' || $_SESSION['tipo_usuario'] == 'premium');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Ejercicios - Sistema de Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
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
                
                <?php if ($puede_agregar): ?>
                    <a href="crear_ejercicio_biblioteca.php" class="btn-primary" style="white-space: nowrap;">
                        ➕ Agregar a Biblioteca
                    </a>
                <?php else: ?>
                    <button class="btn-disabled" onclick="mostrarMensajePremium()">
                        🔒 Agregar a Biblioteca
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
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
                        <?php echo $cat['icono']; ?> <?php echo htmlspecialchars($cat['nombre']); ?>
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
                        echo $cat_actual['icono'] . ' ' . htmlspecialchars($cat_actual['nombre']); 
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
                                    <?php echo $ejercicio['icono']; ?> <?php echo htmlspecialchars($ejercicio['categoria_nombre']); ?>
                                </div>
                                <?php if ($ejercicio['usuario_id'] == $_SESSION['usuario_id'] || $_SESSION['tipo_usuario'] == 'admin'): ?>
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
    
    <!-- Modal de mensaje Premium -->
    <div id="modalPremium" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔒 Función Premium</h3>
                <button class="modal-close" onclick="cerrarModalPremium()">&times;</button>
            </div>
            <div style="text-align: center; padding: 20px 0;">
                <p style="font-size: 48px; margin-bottom: 15px;">⭐</p>
                <p style="margin-bottom: 20px;">
                    Actualiza a <strong>Premium</strong> para agregar tus propios ejercicios a la biblioteca y acceder a funciones ilimitadas.
                </p>
                <p style="color: #666;">
                    Contacta al administrador para actualizar tu cuenta.
                </p>
            </div>
            <button onclick="cerrarModalPremium()" style="background: #667eea;">Entendido</button>
        </div>
    </div>
    
    <script>
        function mostrarMensajePremium() {
            document.getElementById('modalPremium').style.display = 'flex';
        }
        
        function cerrarModalPremium() {
            document.getElementById('modalPremium').style.display = 'none';
        }
        
        function confirmarEliminar(id) {
            if (confirm('¿Estás seguro de eliminar este ejercicio de la biblioteca?')) {
                window.location.href = 'eliminar_ejercicio_biblioteca.php?id=' + id;
            }
        }
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModalPremium();
        });
    </script>
</body>
</html>