<?php
require_once 'config.php';
requiereLogin();

$ejercicio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ejercicio_id == 0) {
    header('Location: biblioteca_ejercicios.php');
    exit();
}

$conn = getConnection();

// Verificar que el ejercicio existe y el usuario tiene permisos
$stmt = $conn->prepare("SELECT usuario_id FROM biblioteca_ejercicios WHERE id = ?");
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

// Eliminar ejercicio
$stmt = $conn->prepare("DELETE FROM biblioteca_ejercicios WHERE id = ?");
$stmt->bind_param("i", $ejercicio_id);

if ($stmt->execute()) {
    $_SESSION['mensaje_exito'] = 'Ejercicio eliminado exitosamente';
} else {
    $_SESSION['mensaje_error'] = 'Error al eliminar el ejercicio';
}

$stmt->close();
$conn->close();

header('Location: biblioteca_ejercicios.php');
exit();
?>
