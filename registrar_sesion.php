<?php
require_once 'config.php';
requiereLogin();

$ejercicio_id = isset($_GET['ejercicio_id']) ? intval($_GET['ejercicio_id']) : 0;

if ($ejercicio_id == 0) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();

// Obtener datos del ejercicio
$stmt = $conn->prepare("SELECT * FROM ejercicios WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
$stmt->execute();
$ejercicio = $stmt->get_result()->fetch_assoc();

if (!$ejercicio) {
    header('Location: index.php');
    exit();
}

// Obtener datos guardados
$stmt = $conn->prepare("SELECT * FROM sesiones_ejercicio WHERE ejercicio_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
$stmt->execute();
$sesiones_guardadas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Organizar datos por sesión y serie
$datos = [];
foreach ($sesiones_guardadas as $sesion) {
    $datos[$sesion['num_sesion']][$sesion['num_serie']] = $sesion;
}

// Obtener notas de sesiones
$stmt = $conn->prepare("SELECT * FROM notas_sesion WHERE ejercicio_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
$stmt->execute();
$notas_guardadas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$notas = [];
foreach ($notas_guardadas as $nota) {
    $notas[$nota['num_sesion']] = $nota['nota'];
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ejercicio['nombre_rutina']); ?> - Registro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        h1 {
            color: #333;
            font-size: 28px;
        }
        
        .btn-volver {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-volver:hover {
            background: #5568d3;
        }
        
        .rutina-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #667eea;
        }
        
        .tabla-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .tabla-scroll {
            overflow-x: auto;
            max-width: 100%;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 800px;
        }
        
        th, td {
            border: 2px solid #dee2e6;
            padding: 10px;
            text-align: center;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .header-sesion {
            background: #5568d3;
            font-size: 14px;
        }
        
        .row-label {
            background: #f8f9fa;
            font-weight: bold;
            text-align: left;
            padding-left: 15px;
            color: #333;
            position: sticky;
            left: 0;
            z-index: 5;
        }
        
        .input-cell input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
        }
        
        .input-cell input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102,126,234,0.2);
        }
        
        .nota-cell {
            background: #e3f2fd;
        }
        
        .btn-nota {
            background: #2196f3;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }
        
        .btn-nota:hover {
            background: #1976d2;
        }
        
        .btn-guardar-sesion {
            background: #4caf50;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn-guardar-sesion:hover {
            background: #45a049;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .modal textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            resize: vertical;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-guardar-nota {
            background: #4caf50;
            color: white;
        }
        
        .btn-cancelar {
            background: #f44336;
            color: white;
        }
        
        .success-message {
            background: #4caf50;
            color: white;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            display: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <h1>📊 <?php echo htmlspecialchars($ejercicio['nombre_rutina']); ?></h1>
            <a href="index.php" class="btn-volver">← Volver</a>
        </div>
        
        <?php if ($ejercicio['descripcion']): ?>
        <p style="color: #666; margin-top: 10px;"><?php echo htmlspecialchars($ejercicio['descripcion']); ?></p>
        <?php endif; ?>
        
        <div class="rutina-info">
            <?php if ($ejercicio['objetivo_serie']): ?>
            <div class="info-item">
                <span class="info-label">🎯 Objetivo:</span>
                <span><?php echo htmlspecialchars($ejercicio['objetivo_serie']); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <span class="info-label">🔢 Series:</span>
                <span><?php echo $ejercicio['num_series']; ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">📅 Sesiones:</span>
                <span><?php echo $ejercicio['num_sesiones']; ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">⏰ Descanso:</span>
                <span><?php echo $ejercicio['descanso_minutos']; ?>' <?php echo $ejercicio['descanso_segundos']; ?>"</span>
            </div>
            
            <?php if ($ejercicio['rir_default']): ?>
            <div class="info-item">
                <span class="info-label">💪 RIR:</span>
                <span><?php echo $ejercicio['rir_default']; ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="successMessage" class="success-message"></div>
    
    <div class="tabla-container">
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="min-width: 150px;">EJERCICIO</th>
                        <?php for ($s = 1; $s <= $ejercicio['num_sesiones']; $s++): ?>
                            <th colspan="2" class="header-sesion">Sesión <?php echo $s; ?></th>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <th></th>
                        <?php for ($s = 1; $s <= $ejercicio['num_sesiones']; $s++): ?>
                            <th>Peso / kg</th>
                            <th>Repeticiones</th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($serie = 1; $serie <= $ejercicio['num_series']; $serie++): ?>
                    <tr>
                        <td class="row-label">S.<?php echo $serie; ?></td>
                        <?php for ($sesion = 1; $sesion <= $ejercicio['num_sesiones']; $sesion++): ?>
                            <?php 
                            $peso = isset($datos[$sesion][$serie]['peso']) ? $datos[$sesion][$serie]['peso'] : '';
                            $reps = isset($datos[$sesion][$serie]['repeticiones']) ? $datos[$sesion][$serie]['repeticiones'] : '';
                            ?>
                            <td class="input-cell">
                                <input type="number" 
                                       step="0.5" 
                                       class="peso-input" 
                                       data-sesion="<?php echo $sesion; ?>" 
                                       data-serie="<?php echo $serie; ?>"
                                       value="<?php echo $peso; ?>"
                                       placeholder="kg">
                            </td>
                            <td class="input-cell">
                                <input type="number" 
                                       class="reps-input" 
                                       data-sesion="<?php echo $sesion; ?>" 
                                       data-serie="<?php echo $serie; ?>"
                                       value="<?php echo $reps; ?>"
                                       placeholder="reps">
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endfor; ?>
                    
                    <!-- Fila de botones -->
                    <tr>
                        <td class="row-label">Acciones</td>
                        <?php for ($sesion = 1; $sesion <= $ejercicio['num_sesiones']; $sesion++): ?>
                            <td colspan="2" class="nota-cell">
                                <button class="btn-nota" onclick="abrirNota(<?php echo $sesion; ?>)">📝 Nota</button>
                                <button class="btn-guardar-sesion" onclick="guardarSesion(<?php echo $sesion; ?>)">💾 Guardar</button>
                            </td>
                        <?php endfor; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal para notas -->
    <div id="modalNota" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📝 Nota de Sesión <span id="notaSesionNum"></span></h3>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <textarea id="notaTexto" placeholder="Escribe aquí tus observaciones de la sesión..."></textarea>
            <div class="modal-buttons">
                <button class="btn-guardar-nota" onclick="guardarNota()">Guardar Nota</button>
                <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
            </div>
        </div>
    </div>
    
    <script>
        const ejercicioId = <?php echo $ejercicio_id; ?>;
        let sesionActual = 0;
        
        // Cargar notas guardadas
        const notasGuardadas = <?php echo json_encode($notas); ?>;
        
        function abrirNota(numSesion) {
            sesionActual = numSesion;
            document.getElementById('notaSesionNum').textContent = numSesion;
            document.getElementById('notaTexto').value = notasGuardadas[numSesion] || '';
            document.getElementById('modalNota').style.display = 'flex';
        }
        
        function cerrarModal() {
            document.getElementById('modalNota').style.display = 'none';
        }
        
        function guardarNota() {
            const nota = document.getElementById('notaTexto').value;
            
            fetch('guardar_nota.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ejercicio_id=${ejercicioId}&num_sesion=${sesionActual}&nota=${encodeURIComponent(nota)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    notasGuardadas[sesionActual] = nota;
                    mostrarMensaje('✅ Nota guardada correctamente');
                    cerrarModal();
                } else {
                    alert('Error al guardar la nota');
                }
            });
        }
        
        function guardarSesion(numSesion) {
            const datos = [];
            
            document.querySelectorAll(`.peso-input[data-sesion="${numSesion}"]`).forEach(input => {
                const serie = input.dataset.serie;
                const peso = input.value;
                const repsInput = document.querySelector(`.reps-input[data-sesion="${numSesion}"][data-serie="${serie}"]`);
                const reps = repsInput.value;
                
                if (peso || reps) {
                    datos.push({serie, peso, reps});
                }
            });
            
            fetch('guardar_sesion.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    ejercicio_id: ejercicioId,
                    num_sesion: numSesion,
                    datos: datos
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    mostrarMensaje(`✅ Sesión ${numSesion} guardada correctamente`);
                } else {
                    alert('Error al guardar la sesión');
                }
            });
        }
        
        function mostrarMensaje(mensaje) {
            const elem = document.getElementById('successMessage');
            elem.textContent = mensaje;
            elem.style.display = 'block';
            setTimeout(() => {
                elem.style.display = 'none';
            }, 3000);
        }
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModal();
        });
    </script>
</body>
</html>