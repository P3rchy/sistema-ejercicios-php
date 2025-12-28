<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Prueba de Conexión a Base de Datos</h1>";

// Datos de conexión
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sistema_entrenamiento';

echo "<h2>Paso 1: Intentando conectar a MySQL...</h2>";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo "<p style='color: red; font-weight: bold;'>❌ ERROR DE CONEXIÓN: " . $conn->connect_error . "</p>";
    die();
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ Conexión exitosa a la base de datos!</p>";
}

echo "<h2>Paso 2: Verificando tabla 'usuarios'...</h2>";

$sql = "SELECT * FROM usuarios";
$resultado = $conn->query($sql);

if ($resultado) {
    echo "<p style='color: green;'>✅ Tabla 'usuarios' encontrada</p>";
    echo "<p><strong>Número de usuarios:</strong> " . $resultado->num_rows . "</p>";
    
    echo "<h2>Paso 3: Usuarios en la base de datos:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #667eea; color: white;'>
            <th>ID</th>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Tipo</th>
            <th>Hash Contraseña</th>
          </tr>";
    
    while ($fila = $resultado->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $fila['id'] . "</td>";
        echo "<td>" . $fila['nombre_completo'] . "</td>";
        echo "<td>" . $fila['usuario'] . "</td>";
        echo "<td>" . $fila['tipo_usuario'] . "</td>";
        echo "<td style='font-size: 10px; max-width: 300px; word-break: break-all;'>" . substr($fila['contrasena'], 0, 50) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Paso 4: Probando autenticación del usuario 'admin'...</h2>";
    
    $usuario_test = 'admin';
    $contrasena_test = 'admin123';
    
    $stmt = $conn->prepare("SELECT id, nombre_completo, contrasena, tipo_usuario FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario_test);
    $stmt->execute();
    $resultado_auth = $stmt->get_result();
    
    if ($resultado_auth->num_rows == 1) {
        echo "<p style='color: green;'>✅ Usuario 'admin' encontrado</p>";
        
        $usuario_data = $resultado_auth->fetch_assoc();
        $hash_guardado = $usuario_data['contrasena'];
        
        echo "<p><strong>Hash guardado en BD:</strong><br><code style='background: #f0f0f0; padding: 5px;'>" . $hash_guardado . "</code></p>";
        
        // Generar hash de prueba
        $hash_correcto = password_hash($contrasena_test, PASSWORD_DEFAULT);
        echo "<p><strong>Hash nuevo generado para 'admin123':</strong><br><code style='background: #f0f0f0; padding: 5px;'>" . $hash_correcto . "</code></p>";
        
        // Verificar contraseña
        if (password_verify($contrasena_test, $hash_guardado)) {
            echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✅ ¡CONTRASEÑA CORRECTA! La autenticación funciona.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold; font-size: 18px;'>❌ CONTRASEÑA INCORRECTA</p>";
            echo "<p style='color: orange;'>El hash en la base de datos NO corresponde a 'admin123'</p>";
            echo "<h3>Solución:</h3>";
            echo "<p>Ejecuta este SQL en phpMyAdmin:</p>";
            echo "<textarea style='width: 100%; height: 80px; font-family: monospace;'>UPDATE usuarios SET contrasena = '$hash_correcto' WHERE usuario = 'admin';</textarea>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Usuario 'admin' NO encontrado</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Error al consultar la tabla: " . $conn->error . "</p>";
}

$conn->close();

echo "<hr>";
echo "<p><a href='login.php'>← Volver al Login</a></p>";
?>