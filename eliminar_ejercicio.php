<?php
require_once 'config.php';
requiereLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$ejercicio_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$rutina_id = isset($_POST['rutina_id']) ? intval($_POST['rutina_id']) : 0;

if ($ejercicio_id == 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

$conn = getConnection();

// Verificar que el ejercicio pertenece al usuario
$stmt = $conn->prepare("
    SELECT e.id, e.dia_rutina_id 
    FROM ejercicios e 
    JOIN dias_rutina dr ON e.dia_rutina_id = dr.id 
    JOIN rutinas r ON dr.rutina_id = r.id 
    WHERE e.id = ? AND r.usuario_id = ?
");
$stmt->bind_param("ii", $ejercicio_id, $_SESSION['usuario_id']);
$stmt->execute();
$ejercicio = $stmt->get_result()->fetch_assoc();

if (!$ejercicio) {
    echo json_encode(['success' => false, 'message' => 'Ejercicio no encontrado']);
    $stmt->close();
    $conn->close();
    exit();
}

// Eliminar el ejercicio (las sesiones se eliminan en cascada si está configurado)
$stmt = $conn->prepare("DELETE FROM ejercicios WHERE id = ?");
$stmt->bind_param("i", $ejercicio_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ejercicio eliminado']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
}

$stmt->close();
$conn->close();
?>
