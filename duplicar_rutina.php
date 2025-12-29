<?php
require_once 'config.php';
requiereLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$rutina_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($rutina_id == 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

$conn = getConnection();

// Verificar que la rutina pertenece al usuario
$stmt = $conn->prepare("SELECT * FROM rutinas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $rutina_id, $_SESSION['usuario_id']);
$stmt->execute();
$rutina = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rutina) {
    echo json_encode(['success' => false, 'message' => 'Rutina no encontrada']);
    $conn->close();
    exit();
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Duplicar la rutina
    $nuevo_nombre = $rutina['nombre_rutina'] . ' - Duplicado';
    
    $stmt = $conn->prepare("
        INSERT INTO rutinas 
        (usuario_id, nombre_rutina, descripcion, genero, nivel_experiencia, descripcion_split, es_publico, tipo_rutina) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isssssss", 
        $_SESSION['usuario_id'],
        $nuevo_nombre,
        $rutina['descripcion'],
        $rutina['genero'],
        $rutina['nivel_experiencia'],
        $rutina['descripcion_split'],
        $rutina['es_publico'],
        $rutina['tipo_rutina']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error al duplicar rutina');
    }
    
    $nueva_rutina_id = $conn->insert_id;
    $stmt->close();
    
    // 2. Obtener y duplicar días
    $stmt = $conn->prepare("SELECT * FROM dias_rutina WHERE rutina_id = ? ORDER BY id");
    $stmt->bind_param("i", $rutina_id);
    $stmt->execute();
    $dias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $mapeo_dias = []; // Para mapear IDs viejos a nuevos
    
    foreach ($dias as $dia) {
        $stmt = $conn->prepare("
            INSERT INTO dias_rutina 
            (rutina_id, dia_semana, num_ejercicios, grupos_musculares) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param("isis", 
            $nueva_rutina_id,
            $dia['dia_semana'],
            $dia['num_ejercicios'],
            $dia['grupos_musculares']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al duplicar días');
        }
        
        $nuevo_dia_id = $conn->insert_id;
        $mapeo_dias[$dia['id']] = $nuevo_dia_id;
        $stmt->close();
    }
    
    // 3. Obtener y duplicar ejercicios
    $stmt = $conn->prepare("SELECT * FROM ejercicios WHERE dia_rutina_id IN (SELECT id FROM dias_rutina WHERE rutina_id = ?) ORDER BY dia_rutina_id, orden");
    $stmt->bind_param("i", $rutina_id);
    $stmt->execute();
    $ejercicios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    foreach ($ejercicios as $ejercicio) {
        $nuevo_dia_id = $mapeo_dias[$ejercicio['dia_rutina_id']];
        
        $stmt = $conn->prepare("
            INSERT INTO ejercicios 
            (dia_rutina_id, usuario_id, orden, nombre_ejercicio, imagen_url, video_url, objetivo_serie, num_series, num_sesiones, descanso_minutos, descanso_segundos, rir_rpe) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("iiissssiiiis",
            $nuevo_dia_id,
            $_SESSION['usuario_id'],
            $ejercicio['orden'],
            $ejercicio['nombre_ejercicio'],
            $ejercicio['imagen_url'],
            $ejercicio['video_url'],
            $ejercicio['objetivo_serie'],
            $ejercicio['num_series'],
            $ejercicio['num_sesiones'],
            $ejercicio['descanso_minutos'],
            $ejercicio['descanso_segundos'],
            $ejercicio['rir_rpe']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al duplicar ejercicios');
        }
        
        $stmt->close();
    }
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Rutina duplicada exitosamente',
        'nueva_rutina_id' => $nueva_rutina_id
    ]);
    
} catch (Exception $e) {
    // Revertir cambios si algo falla
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>