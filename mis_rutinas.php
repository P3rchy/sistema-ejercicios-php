<?php
require_once 'config.php';
requiereLogin();

$conn = getConnection();

// Obtener rutinas del usuario
$usuario_id = $_SESSION['usuario_id'];

// Verificar si existe la tabla ejercicios_rutina
$tabla_ejercicios_rutina = $conn->query("SHOW TABLES LIKE 'ejercicios_rutina'")->num_rows > 0;
$tabla_ejercicios = $conn->query("SHOW TABLES LIKE 'ejercicios'")->num_rows > 0;

// Query universal que funciona con ambos tipos de rutinas
$stmt = $conn->prepare("SELECT r.*, 
                        COUNT(DISTINCT dr.id) as total_dias,
                        COALESCE(
                            (SELECT COUNT(*) FROM ejercicios_rutina er WHERE er.dia_id IN (
                                SELECT id FROM dias_rutina WHERE rutina_id = r.id
                            )),
                            0
                        ) + COALESCE(
                            (SELECT COUNT(*) FROM ejercicios e WHERE e.dia_rutina_id IN (
                                SELECT id FROM dias_rutina WHERE rutina_id = r.id
                            )),
                            0
                        ) as total_ejercicios
                        FROM rutinas r
                        LEFT JOIN dias_rutina dr ON r.id = dr.rutina_id
                        WHERE r.usuario_id = ?
                        GROUP BY r.id
                        ORDER BY r.fecha_creacion DESC");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$rutinas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// NO cerrar $conn aquí porque se usa más adelante
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- PWA -->
<meta name="theme-color" content="#2563eb">
<link rel="manifest" href="/sistema_entrenamiento/manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Rutinas - Sistema de Entrenamiento</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .rutinas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .rutina-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .rutina-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .rutina-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .rutina-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 0;
            flex: 1;
        }
        
        .rutina-tipo-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .tipo-metodologica {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .tipo-basica {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .rutina-descripcion {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
            line-height: 1.5;
        }
        
        .rutina-stats {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .stat-number {
            font-weight: bold;
            color: #2563eb;
        }
        
        .rutina-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
        }
        
        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 15px;
            font-size: 12px;
            color: #555;
        }
        
        .rutina-fecha {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        
        .rutina-acciones {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-accion {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        
        .btn-ver {
            background: #2563eb;
            color: white;
        }
        
        .btn-ver:hover {
            background: #1e40af;
        }
        
        .btn-editar {
            background: #ffc107;
            color: #333;
        }
        
        .btn-editar:hover {
            background: #e0a800;
        }
        
        .btn-duplicar {
            background: #17a2b8;
            color: white;
        }
        
        .btn-duplicar:hover {
            background: #138496;
        }
        
        .btn-eliminar {
            background: #dc3545;
            color: white;
        }
        
        .btn-eliminar:hover {
            background: #c82333;
        }
        
        .rutina-publica {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4caf50;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .calificacion-stars {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }
        
        .stars {
            color: #ffc107;
            font-size: 16px;
        }
        
        .votos {
            font-size: 12px;
            color: #666;
        }
        
        .no-rutinas {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-rutinas-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .no-rutinas h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .no-rutinas p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .filtros-rutinas {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filtros-grupo {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filtro-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filtro-item label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .filtro-item select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        
        /* Botones de filtro por tipo */
        .btn-filtro-tipo {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-filtro-tipo:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-filtro-tipo.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .badge-count {
            background: rgba(0,0,0,0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-filtro-tipo.active .badge-count {
            background: rgba(255,255,255,0.3);
        }
        
        .input-buscador:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .contador-resultados {
            padding: 8px 0;
            font-size: 14px;
        }
        
        /* Modal de confirmación custom */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: modalSlideUp 0.3s ease;
        }
        
        @keyframes modalSlideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        
        .modal-message {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .modal-btn-cancel {
            background: #e0e0e0;
            color: #333;
        }
        
        .modal-btn-cancel:hover {
            background: #d0d0d0;
        }
        
        .modal-btn-confirm {
            background: #17a2b8;
            color: white;
        }
        
        .modal-btn-confirm:hover {
            background: #138496;
        }
        
        .modal-btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .modal-btn-delete:hover {
            background: #c82333;
        }
        
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
        
        .toast.success {
            background: #4caf50;
        }
        
        .toast.error {
            background: #f44336;
        }
        
        .toast.warning {
            background: #ff9800;
            color: #000;
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
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📋 Mis Rutinas</h1>
        <a href="index.php" class="btn-volver">← Volver al Inicio</a>
    </div>
    
    <div class="container">
        <!-- Header con botón crear -->
        <div class="card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="margin-bottom: 10px;">Mis Rutinas de Entrenamiento</h2>
                    <p class="subtitle">Gestiona todas tus rutinas creadas</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="crear_rutina.php" class="btn-primary">
                        ➕ Nueva Rutina Metodológica
                    </a>
                    <a href="crear_rutina_basica.php" class="btn-primary" style="background: #1e40af;">
                        ➕ Nueva Rutina Básica
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filtros-rutinas">
            <!-- Buscador -->
            <div class="buscador-container" style="margin-bottom: 20px;">
                <input type="text" 
                       id="buscadorRutinas" 
                       class="input-buscador"
                       placeholder="🔍 Buscar rutinas por nombre..." 
                       onkeyup="filtrarRutinas()"
                       style="width: 100%; max-width: 500px; padding: 12px 20px; font-size: 16px; border: 2px solid #e0e0e0; border-radius: 25px; outline: none;">
            </div>
            
            <!-- Botones de filtro por tipo -->
            <div class="filtro-tipo-botones" style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                <button class="btn-filtro-tipo active" data-tipo="todas" onclick="cambiarFiltroTipo('todas')">
                    🎯 Todas <span class="badge-count" id="count-todas">0</span>
                </button>
                <button class="btn-filtro-tipo" data-tipo="metodologica" onclick="cambiarFiltroTipo('metodologica')">
                    📊 Metodológicas <span class="badge-count" id="count-metodologica">0</span>
                </button>
                <button class="btn-filtro-tipo" data-tipo="basica_gym" onclick="cambiarFiltroTipo('basica_gym')">
                    💪 Básicas <span class="badge-count" id="count-basica">0</span>
                </button>
            </div>
            
            <!-- Contador de resultados -->
            <div class="contador-resultados" style="margin-bottom: 15px; font-weight: 600; color: #666;">
                <span id="contadorRutinas">0</span> rutinas mostradas
            </div>
            
            <!-- Filtros adicionales -->
            <div class="filtros-grupo">
                <span style="font-weight: bold; color: #333;">🔍 Filtros adicionales:</span>
                
                <div class="filtro-item">
                    <label>Nivel:</label>
                    <select id="filtroNivel" onchange="filtrarRutinas()">
                        <option value="todas">Todos</option>
                        <option value="principiante">Principiante</option>
                        <option value="intermedio">Intermedio</option>
                        <option value="avanzado">Avanzado</option>
                    </select>
                </div>
                
                <div class="filtro-item">
                    <label>Género:</label>
                    <select id="filtroGenero" onchange="filtrarRutinas()">
                        <option value="todas">Todos</option>
                        <option value="hombre">Hombre</option>
                        <option value="mujer">Mujer</option>
                        <option value="unisex">Unisex</option>
                    </select>
                </div>
            </div>
        </div>
        
        <?php if (empty($rutinas)): ?>
            <!-- No hay rutinas -->
            <div class="no-rutinas">
                <div class="no-rutinas-icon">📝</div>
                <h3>No tienes rutinas creadas</h3>
                <p>Crea tu primera rutina para comenzar a entrenar</p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <a href="crear_rutina.php" class="btn-primary">
                        ➕ Crear Rutina Metodológica
                    </a>
                    <a href="crear_rutina_basica.php" class="btn-primary" style="background: #1e40af;">
                        ➕ Crear Rutina Básica
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Grid de rutinas -->
            <div class="rutinas-grid">
                <?php foreach ($rutinas as $rutina): ?>
                    <div class="rutina-card" 
                         data-tipo="<?php echo htmlspecialchars($rutina['tipo_rutina'] ?? 'metodologica'); ?>"
                         data-nivel="<?php echo htmlspecialchars($rutina['nivel_experiencia'] ?? 'Principiante'); ?>"
                         data-genero="<?php echo htmlspecialchars($rutina['genero'] ?? 'Unisex'); ?>">
                        
                        <?php if ($rutina['es_publico']): ?>
                            <div class="rutina-publica">🌐 PÚBLICA</div>
                        <?php endif; ?>
                        
                        <div class="rutina-header">
                            <div style="flex: 1;">
                                <h3 class="rutina-title">
                                    <?php echo htmlspecialchars($rutina['nombre_rutina']); ?>
                                </h3>
                                <span class="rutina-tipo-badge tipo-<?php echo $rutina['tipo_rutina'] ?? 'metodologica'; ?>">
                                    <?php 
                                    $tipo = $rutina['tipo_rutina'] ?? 'metodologica';
                                    echo $tipo == 'metodologica' ? '📊 Metodológica' : '📸 Básica'; 
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($rutina['descripcion']): ?>
                            <p class="rutina-descripcion">
                                <?php echo htmlspecialchars(substr($rutina['descripcion'], 0, 100)); ?>
                                <?php echo strlen($rutina['descripcion']) > 100 ? '...' : ''; ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="rutina-stats">
                            <div class="stat-item">
                                <span>📅</span>
                                <span class="stat-number"><?php echo $rutina['total_dias']; ?></span>
                                <span>días</span>
                            </div>
                            <div class="stat-item">
                                <span>💪</span>
                                <span class="stat-number"><?php echo $rutina['total_ejercicios']; ?></span>
                                <span>ejercicios</span>
                            </div>
                        </div>
                        
                        <div class="rutina-meta">
                            <?php 
                            // Badge de tipo de rutina
                            $tipo_rutina = $rutina['tipo_rutina'] ?? 'metodologica';
                            if ($tipo_rutina == 'basica_gym'): ?>
                                <span class="meta-badge" style="background: #10b981; color: white;">
                                    💪 Básica
                                </span>
                            <?php else: ?>
                                <span class="meta-badge" style="background: #2563eb; color: white;">
                                    📊 Metodológica
                                </span>
                            <?php endif; ?>
                            
                            <?php if (isset($rutina['nivel_experiencia'])): ?>
                                <span class="meta-badge">
                                    🎯 <?php echo ucfirst($rutina['nivel_experiencia']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (isset($rutina['genero'])): ?>
                                <span class="meta-badge">
                                    <?php 
                                    $genero_lower = strtolower($rutina['genero']);
                                    $iconos = ['hombre' => '♂️', 'mujer' => '♀️', 'unisex' => '⚧'];
                                    echo $iconos[$genero_lower] ?? '⚧';
                                    ?>
                                    <?php echo htmlspecialchars($rutina['genero']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($rutina['es_publico'] && $rutina['total_votos'] > 0): ?>
                            <div class="calificacion-stars">
                                <span class="stars">
                                    <?php
                                    $rating = floatval($rutina['calificacion_promedio']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '⭐' : '☆';
                                    }
                                    ?>
                                </span>
                                <span class="votos">(<?php echo $rutina['total_votos']; ?> votos)</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="rutina-fecha">
                            Creada: <?php echo date('d/m/Y', strtotime($rutina['fecha_creacion'])); ?>
                        </div>
                        
                        <div class="rutina-acciones">
                            <?php 
                            $tipo_rutina = $rutina['tipo_rutina'] ?? 'metodologica';
                            
                            // Para rutinas básicas, "Ver" realmente es "Entrenar" 
                            if ($tipo_rutina == 'basica_gym') {
                                // Obtener el primer día de la rutina básica
                                $stmt_dia = $conn->prepare("SELECT dia_semana FROM dias_rutina WHERE rutina_id = ? ORDER BY id LIMIT 1");
                                $stmt_dia->bind_param("i", $rutina['id']);
                                $stmt_dia->execute();
                                $primer_dia = $stmt_dia->get_result()->fetch_assoc();
                                $stmt_dia->close();
                                
                                $dia_entrenar = $primer_dia ? $primer_dia['dia_semana'] : 'Lunes';
                                $ver_url = "entrenar_rutina_basica.php?id={$rutina['id']}&dia={$dia_entrenar}&ejercicio=0";
                                $ver_texto = "💪 Entrenar";
                            } else {
                                $ver_url = "ver_rutina.php?id={$rutina['id']}";
                                $ver_texto = "👁️ Ver";
                            }
                            
                            $editar_url = $tipo_rutina == 'basica_gym' ? 'editar_rutina_basica.php' : 'editar_rutina.php';
                            ?>
                            <a href="<?php echo $ver_url; ?>" class="btn-accion btn-ver">
                                <?php echo $ver_texto; ?>
                            </a>
                            <a href="<?php echo $editar_url; ?>?id=<?php echo $rutina['id']; ?>" class="btn-accion btn-editar">
                                ✏️ Editar
                            </a>
                            <button onclick="duplicarRutina(<?php echo $rutina['id']; ?>, '<?php echo htmlspecialchars($rutina['nombre_rutina']); ?>')" 
                                    class="btn-accion btn-duplicar">
                                📋 Duplicar
                            </button>
                            <button onclick="confirmarEliminar(<?php echo $rutina['id']; ?>, '<?php echo htmlspecialchars($rutina['nombre_rutina']); ?>')" 
                                    class="btn-accion btn-eliminar">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
    
    <script>
        let modalCallback = null;
        
        function showModal(title, message, callback, isDelete = false) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            
            const confirmBtn = document.getElementById('modalConfirmBtn');
            confirmBtn.className = 'modal-btn ' + (isDelete ? 'modal-btn-delete' : 'modal-btn-confirm');
            
            modalCallback = callback;
            document.getElementById('confirmModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
            modalCallback = null;
        }
        
        function confirmAction() {
            if (modalCallback) modalCallback();
            closeModal();
        }
        
        // Sistema de toasts
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : '⚠️';
            toast.innerHTML = `<span style="font-size: 20px;">${icon}</span><span>${message}</span>`;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        let tipoFiltroActual = 'todas';
        
        // Función para cambiar filtro de tipo (con botones)
        function cambiarFiltroTipo(tipo) {
            tipoFiltroActual = tipo;
            
            // Actualizar botones activos
            document.querySelectorAll('.btn-filtro-tipo').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.btn-filtro-tipo[data-tipo="${tipo}"]`).classList.add('active');
            
            // Aplicar filtros
            filtrarRutinas();
        }
        
        // Función principal de filtrado
        function filtrarRutinas() {
            const buscador = document.getElementById('buscadorRutinas').value.toLowerCase();
            const nivel = document.getElementById('filtroNivel').value;
            const genero = document.getElementById('filtroGenero').value;
            
            const cards = document.querySelectorAll('.rutina-card');
            let contador = 0;
            
            cards.forEach(card => {
                const cardTipo = card.getAttribute('data-tipo');
                const cardNivel = card.getAttribute('data-nivel')?.toLowerCase() || '';
                const cardGenero = card.getAttribute('data-genero')?.toLowerCase() || '';
                const cardNombre = card.querySelector('.rutina-title')?.textContent.toLowerCase() || '';
                
                // Filtros
                const matchTipo = tipoFiltroActual === 'todas' || cardTipo === tipoFiltroActual;
                const matchNivel = nivel === 'todas' || cardNivel === nivel.toLowerCase();
                const matchGenero = genero === 'todas' || cardGenero === genero.toLowerCase();
                const matchBuscador = buscador === '' || cardNombre.includes(buscador);
                
                if (matchTipo && matchNivel && matchGenero && matchBuscador) {
                    card.style.display = 'block';
                    contador++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Actualizar contador
            document.getElementById('contadorRutinas').textContent = contador;
        }
        
        // Función para actualizar contadores en badges
        function actualizarContadores() {
            const cards = document.querySelectorAll('.rutina-card');
            let totalTodas = 0;
            let totalMetodologica = 0;
            let totalBasica = 0;
            
            cards.forEach(card => {
                const tipo = card.getAttribute('data-tipo');
                totalTodas++;
                
                if (tipo === 'metodologica') {
                    totalMetodologica++;
                } else if (tipo === 'basica_gym' || tipo === 'basica') {
                    totalBasica++;
                }
            });
            
            document.getElementById('count-todas').textContent = totalTodas;
            document.getElementById('count-metodologica').textContent = totalMetodologica;
            document.getElementById('count-basica').textContent = totalBasica;
            document.getElementById('contadorRutinas').textContent = totalTodas;
        }
        
        // Ejecutar al cargar la página
        window.addEventListener('DOMContentLoaded', function() {
            actualizarContadores();
            filtrarRutinas();
        });
        
        function duplicarRutina(id, nombre) {
            showModal(
                '¿Duplicar rutina?',
                `Se creará "${nombre} - Duplicado". Los registros de entrenamiento NO se copiarán.`,
                () => {
                    fetch('duplicar_rutina.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'id=' + id
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Rutina duplicada exitosamente', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(() => showToast('Error al duplicar rutina', 'error'));
                }
            );
        }
        
        function confirmarEliminar(id, nombre) {
            showModal(
                '¿Eliminar rutina?',
                `¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`,
                () => {
                    window.location.href = 'eliminar_rutina.php?id=' + id;
                },
                true
            );
        }
    </script>
    <script src="/sistema_entrenamiento/pwa-register.js"></script>
</body>
</html>
<?php
// Cerrar conexión al final
if (isset($conn)) {
    $conn->close();
}
?>
