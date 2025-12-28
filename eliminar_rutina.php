<?php
require_once 'config.php';
requiereLogin();

if (!isset($_GET['id'])) {
    header('Location: mis_rutinas.php');
    exit();
}

$rutina_id = intval($_GET['id']);
$usuario_id = $_SESSION['usuario_id'];

$conn = getConnection();

// Verificar que la rutina pertenece al usuario
$stmt = $conn->prepare("SELECT id, nombre_rutina FROM rutinas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $rutina_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    $_SESSION['error'] = 'No tienes permisos para eliminar esta rutina';
    header('Location: mis_rutinas.php');
    exit();
}

$rutina = $result->fetch_assoc();
$stmt->close();

// Eliminar la rutina (las foreign keys CASCADE eliminarán días y ejercicios automáticamente)
$stmt = $conn->prepare("DELETE FROM rutinas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $rutina_id, $usuario_id);

if ($stmt->execute()) {
    $_SESSION['exito'] = "Rutina '{$rutina['nombre_rutina']}' eliminada exitosamente";
} else {
    $_SESSION['error'] = 'Error al eliminar la rutina';
}

$stmt->close();
$conn->close();

header('Location: mis_rutinas.php');
exit();
?>
