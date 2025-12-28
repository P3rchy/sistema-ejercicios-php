<?php
require_once 'config.php';
requiereLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'autoguardar') {
    // Autoguardar una celda individual
    $ejercicio_id = intval($_POST['ejercicio_id']);
    $num_sesion = intval($_POST['num_sesion']);
    $num_serie = intval($_POST['num_serie']);
    $campo = $_POST['campo']; // 'peso', 'repeticiones', 'rir_rpe'
    $valor = trim($_POST['valor']);
    
    if ($ejercicio_id == 0 || $num_sesion == 0 || $num_serie == 0) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    $conn = getConnection();
    
    // Verificar que el ejercicio pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM ejercicios WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Ejercicio no encontrado']);
        exit();
    }
    $stmt->close();
    
    // Verificar si ya existe el registro
    $stmt = $conn->prepare("SELECT id FROM sesiones_ejercicio WHERE ejercicio_id = ? AND usuario_id = ? AND num_sesion = ? AND num_serie = ?");
    $stmt->bind_param("iiii", $ejercicio_id, $_SESSION['usuario_id'], $num_sesion, $num_serie);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // UPDATE existente
        $registro = $result->fetch_assoc();
        $stmt->close();
        
        $allowed_campos = ['peso', 'repeticiones', 'rir_rpe'];
        if (!in_array($campo, $allowed_campos)) {
            echo json_encode(['success' => false, 'message' => 'Campo no válido']);
            exit();
        }
        
        // Actualizar solo el campo específico
        if ($campo === 'rir_rpe') {
            // Para RIR/RPE, actualizar el campo del ejercicio base
            $stmt = $conn->prepare("UPDATE ejercicios SET rir_rpe = ? WHERE id = ?");
            $stmt->bind_param("si", $valor, $ejercicio_id);
        } else {
            $stmt = $conn->prepare("UPDATE sesiones_ejercicio SET $campo = ? WHERE id = ?");
            if ($campo === 'peso') {
                $valor_num = floatval($valor);
                $stmt->bind_param("di", $valor_num, $registro['id']);
            } else {
                $valor_num = intval($valor);
                $stmt->bind_param("ii", $valor_num, $registro['id']);
            }
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Actualizado', 'action' => 'update']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
    } else {
        // INSERT nuevo
        $stmt->close();
        
        $peso = $campo === 'peso' ? floatval($valor) : 0;
        $repeticiones = $campo === 'repeticiones' ? intval($valor) : 0;
        
        $stmt = $conn->prepare("INSERT INTO sesiones_ejercicio (ejercicio_id, usuario_id, num_sesion, num_serie, peso, repeticiones, fecha_realizado) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("iiiidi", $ejercicio_id, $_SESSION['usuario_id'], $num_sesion, $num_serie, $peso, $repeticiones);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Guardado', 'action' => 'insert', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar']);
        }
    }
    
    $stmt->close();
    $conn->close();
    
} elseif ($accion === 'guardar_sesion_completa') {
    // Guardar una sesión completa
    $ejercicio_id = intval($_POST['ejercicio_id']);
    $num_sesion = intval($_POST['num_sesion']);
    $series = json_decode($_POST['series'], true);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    
    if ($ejercicio_id == 0 || $num_sesion == 0 || !is_array($series)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    $conn = getConnection();
    
    // Verificar que el ejercicio pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM ejercicios WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Ejercicio no encontrado']);
        exit();
    }
    $stmt->close();
    
    $conn->begin_transaction();
    
    try {
        foreach ($series as $serie) {
            $num_serie = intval($serie['num_serie']);
            $peso = floatval($serie['peso']);
            $repeticiones = intval($serie['repeticiones']);
            
            // Verificar si existe
            $stmt = $conn->prepare("SELECT id FROM sesiones_ejercicio WHERE ejercicio_id = ? AND usuario_id = ? AND num_sesion = ? AND num_serie = ?");
            $stmt->bind_param("iiii", $ejercicio_id, $_SESSION['usuario_id'], $num_sesion, $num_serie);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // UPDATE
                $registro = $result->fetch_assoc();
                $stmt->close();
                
                $stmt = $conn->prepare("UPDATE sesiones_ejercicio SET peso = ?, repeticiones = ?, fecha_realizado = ? WHERE id = ?");
                $stmt->bind_param("disi", $peso, $repeticiones, $fecha, $registro['id']);
                $stmt->execute();
            } else {
                // INSERT
                $stmt->close();
                
                $stmt = $conn->prepare("INSERT INTO sesiones_ejercicio (ejercicio_id, usuario_id, num_sesion, num_serie, peso, repeticiones, fecha_realizado) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiidis", $ejercicio_id, $_SESSION['usuario_id'], $num_sesion, $num_serie, $peso, $repeticiones, $fecha);
                $stmt->execute();
            }
            $stmt->close();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sesión guardada correctamente']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al guardar sesión: ' . $e->getMessage()]);
    }
    
    $conn->close();
    
} elseif ($accion === 'guardar_nota') {
    // Guardar nota de sesión
    $ejercicio_id = intval($_POST['ejercicio_id']);
    $num_sesion = intval($_POST['num_sesion']);
    $nota = trim($_POST['nota']);
    
    if ($ejercicio_id == 0 || $num_sesion == 0) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    $conn = getConnection();
    
    // Verificar que el ejercicio pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM ejercicios WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Ejercicio no encontrado']);
        exit();
    }
    $stmt->close();
    
    // Verificar si ya existe la nota
    $stmt = $conn->prepare("SELECT id FROM notas_sesion WHERE ejercicio_id = ? AND usuario_id = ? AND num_sesion = ?");
    $stmt->bind_param("iii", $ejercicio_id, $_SESSION['usuario_id'], $num_sesion);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // UPDATE
        $registro = $result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE notas_sesion SET nota = ? WHERE id = ?");
        $stmt->bind_param("si", $nota, $registro['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Nota actualizada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar nota']);
        }
    } else {
        // INSERT
        $stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO notas_sesion (ejercicio_id, usuario_id, num_sesion, nota) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $ejercicio_id, $_SESSION['usuario_id'], $num_sesion, $nota);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Nota guardada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar nota']);
        }
    }
    
    $stmt->close();
    $conn->close();

} elseif ($accion === 'guardar_fecha') {
    // Guardar fecha de realización para una sesión
    $ejercicio_id = intval($_POST['ejercicio_id']);
    $num_sesion = intval($_POST['num_sesion']);
    $fecha = trim($_POST['fecha']);
    
    if ($ejercicio_id == 0 || $num_sesion == 0 || empty($fecha)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    $conn = getConnection();
    
    // Verificar que el ejercicio pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM ejercicios WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Ejercicio no encontrado']);
        exit();
    }
    $stmt->close();
    
    // Actualizar la fecha en todas las series de esta sesión
    $stmt = $conn->prepare("UPDATE sesiones_ejercicio SET fecha_realizado = ? WHERE ejercicio_id = ? AND usuario_id = ? AND num_sesion = ?");
    $stmt->bind_param("siii", $fecha, $ejercicio_id, $_SESSION['usuario_id'], $num_sesion);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Fecha guardada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar fecha']);
    }
    
    $stmt->close();
    $conn->close();
    
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
