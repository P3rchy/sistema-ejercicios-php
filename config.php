<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_entrenamiento');

// Crear conexión
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    return $conn;
}

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function estaLogueado() {
    return isset($_SESSION['usuario_id']);
}

// Función para verificar el tipo de usuario
function tipoUsuario() {
    return isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : null;
}

// Función para redirigir si no está logueado
function requiereLogin() {
    if (!estaLogueado()) {
        header('Location: login.php');
        exit();
    }
}
?>