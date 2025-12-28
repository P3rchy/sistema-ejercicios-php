<?php
require_once 'config.php';

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_completo = trim($_POST['nombre_completo']);
    $ciudad = trim($_POST['ciudad']);
    $usuario = trim($_POST['usuario']);
    $contrasena = $_POST['contrasena'];
    $repetir_contrasena = $_POST['repetir_contrasena'];
    
    // Validaciones
    if (empty($nombre_completo) || empty($ciudad) || empty($usuario) || empty($contrasena)) {
        $error = 'Todos los campos son obligatorios';
    } elseif ($contrasena !== $repetir_contrasena) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($contrasena) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        $conn = getConnection();
        
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $error = 'El nombre de usuario ya está en uso';
        } else {
            // Encriptar contraseña
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, ciudad, usuario, contrasena) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre_completo, $ciudad, $usuario, $contrasena_hash);
            
            if ($stmt->execute()) {
                $exito = 'Registro exitoso. Redirigiendo al login...';
                header('refresh:2;url=login.php');
            } else {
                $error = 'Error al registrar el usuario';
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Entrenamiento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .mensaje {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .exito {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Registro</h1>
        
        <?php if ($error): ?>
            <div class="mensaje error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($exito): ?>
            <div class="mensaje exito"><?php echo $exito; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre_completo">Nombre Completo</label>
                <input type="text" id="nombre_completo" name="nombre_completo" 
                       value="<?php echo isset($_POST['nombre_completo']) ? htmlspecialchars($_POST['nombre_completo']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="ciudad">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" 
                       value="<?php echo isset($_POST['ciudad']) ? htmlspecialchars($_POST['ciudad']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" 
                       value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            
            <div class="form-group">
                <label for="repetir_contrasena">Repetir Contraseña</label>
                <input type="password" id="repetir_contrasena" name="repetir_contrasena" required>
            </div>
            
            <button type="submit">Registrarse</button>
        </form>
        
        <div class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </div>
    </div>
</body>
</html>