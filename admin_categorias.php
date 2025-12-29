<?php
require_once 'config.php';
requiereLogin();

// Solo admin y premium_pro pueden gestionar categorías
$tipo_lower = strtolower($_SESSION['tipo_usuario']);
if ($tipo_lower != 'admin' && $tipo_lower != 'premium_pro') {
    header('Location: index.php');
    exit();
}

$error = '';
$exito = '';

$conn = getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion == 'editar') {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        
        if (empty($nombre)) {
            $error = 'El nombre de la categoría es obligatorio';
        } else {
            $stmt = $conn->prepare("UPDATE categorias_ejercicios SET nombre = ? WHERE id = ?");
            $stmt->bind_param("si", $nombre, $id);
            
            if ($stmt->execute()) {
                $exito = 'Categoría actualizada exitosamente';
            } else {
                $error = 'Error al actualizar la categoría';
            }
            $stmt->close();
        }
    } elseif ($accion == 'agregar') {
        $nombre = trim($_POST['nombre']);
        
        if (empty($nombre)) {
            $error = 'El nombre de la categoría es obligatorio';
        } else {
            $stmt = $conn->prepare("INSERT INTO categorias_ejercicios (nombre) VALUES (?)");
            $stmt->bind_param("s", $nombre);
            
            if ($stmt->execute()) {
                $exito = 'Categoría agregada exitosamente';
            } else {
                $error = 'Error al agregar la categoría';
            }
            $stmt->close();
        }
    } elseif ($accion == 'eliminar') {
        $id = intval($_POST['id']);
        
        // Verificar que no tenga ejercicios asociados
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM biblioteca_ejercicios WHERE categoria_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result['count'] > 0) {
            $error = 'No se puede eliminar la categoría porque tiene ' . $result['count'] . ' ejercicio(s) asociado(s)';
        } else {
            $stmt = $conn->prepare("DELETE FROM categorias_ejercicios WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $exito = 'Categoría eliminada exitosamente';
            } else {
                $error = 'Error al eliminar la categoría';
            }
            $stmt->close();
        }
    }
}

// Obtener todas las categorías con conteo de ejercicios
$categorias = $conn->query("
    SELECT c.*, COUNT(b.id) as ejercicios_count 
    FROM categorias_ejercicios c 
    LEFT JOIN biblioteca_ejercicios b ON c.id = b.categoria_id 
    GROUP BY c.id 
    ORDER BY c.nombre
")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Categorías - Sistema de Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="notifications.css">
    <style>
        .categorias-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .categoria-admin-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .categoria-admin-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .categoria-display {
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .categoria-nombre-grande {
            font-size: 20px;
            font-weight: bold;
            color: white;
            text-align: center;
        }
        
        .categoria-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .categoria-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-editar-cat {
            flex: 1;
            padding: 8px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-editar-cat:hover {
            background: #5568d3;
        }
        
        .btn-eliminar-cat {
            flex: 1;
            padding: 8px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-eliminar-cat:hover {
            background: #dc2626;
        }
        
        .btn-eliminar-cat:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .form-agregar-categoria {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
            border: 2px dashed #667eea;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏷️ Administrar Categorías</h1>
        <a href="biblioteca_ejercicios.php" class="btn-volver">← Volver a Biblioteca</a>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="mensaje error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($exito): ?>
            <div class="mensaje exito">✅ <?php echo $exito; ?></div>
        <?php endif; ?>
        
        <!-- Formulario agregar nueva categoría -->
        <div class="card form-agregar-categoria">
            <h3>➕ Agregar Nueva Categoría</h3>
            <form method="POST" action="" id="formAgregar">
                <input type="hidden" name="accion" value="agregar">
                
                <div style="display: grid; grid-template-columns: 1fr 150px; gap: 15px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label for="nombre_nuevo">Nombre de la Categoría</label>
                        <input type="text" id="nombre_nuevo" name="nombre" 
                               placeholder="Ej: Pecho, Espalda, Piernas, Abdominales..." required>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        ➕ Agregar
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Lista de categorías existentes -->
        <div class="card">
            <h2>Categorías Existentes (<?php echo count($categorias); ?>)</h2>
            <p class="subtitle">Haz clic en "Editar" para modificar el nombre de una categoría</p>
            
            <div class="categorias-admin-grid">
                <?php foreach ($categorias as $cat): ?>
                    <div class="categoria-admin-card" id="cat-<?php echo $cat['id']; ?>">
                        <div class="categoria-display">
                            <div class="categoria-nombre-grande"><?php echo htmlspecialchars($cat['nombre']); ?></div>
                        </div>
                        
                        <div class="categoria-stats">
                            <span>📊 <?php echo $cat['ejercicios_count']; ?> ejercicio(s)</span>
                            <span>🆔 ID: <?php echo $cat['id']; ?></span>
                        </div>
                        
                        <div class="categoria-actions">
                            <button class="btn-editar-cat" onclick="editarCategoria(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['nombre'], ENT_QUOTES); ?>')">
                                ✏️ Editar
                            </button>
                            <button class="btn-eliminar-cat" 
                                    onclick="eliminarCategoria(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['nombre'], ENT_QUOTES); ?>')"
                                    <?php echo $cat['ejercicios_count'] > 0 ? 'disabled title="No se puede eliminar porque tiene ejercicios asociados"' : ''; ?>>
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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
    
    <!-- Modal editar -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-title">✏️ Editar Categoría</div>
            <form method="POST" action="" id="formEditar">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_nombre">Nombre de la Categoría</label>
                    <input type="text" id="edit_nombre" name="nombre" 
                           placeholder="Ej: Pecho, Espalda, Piernas..." required>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="modal-btn modal-btn-confirm">💾 Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <script src="notifications.js"></script>
    
    <script>
        function editarCategoria(id, nombre) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function eliminarCategoria(id, nombre) {
            showModal(
                '¿Eliminar categoría?',
                `¿Estás seguro de eliminar "${nombre}"? Esta acción no se puede deshacer.`,
                () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                },
                true
            );
        }
        
        // Mostrar toasts si hay mensajes
        <?php if ($exito): ?>
            showToast('<?php echo addslashes($exito); ?>', 'success');
        <?php endif; ?>
        
        <?php if ($error): ?>
            showToast('<?php echo addslashes($error); ?>', 'error');
        <?php endif; ?>
    </script>
</body>
</html>