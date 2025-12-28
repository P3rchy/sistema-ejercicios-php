<?php
require_once 'config.php';
requiereLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema de Entrenamiento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .usuario-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .badge {
            background: rgba(255,255,255,0.3);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge.premium {
            background: gold;
            color: #333;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
            line-height: 1.6;
        }
        
        .funciones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .funcion-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .funcion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .funcion-card.bloqueado {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .funcion-card.bloqueado:hover {
            transform: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .funcion-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .funcion-card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .funcion-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .premium-badge {
            display: inline-block;
            background: gold;
            color: #333;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .info-box p {
            margin: 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🏋️ Sistema de Entrenamiento</h1>
        <div class="navbar-info">
            <div class="usuario-info">
                <span><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></span>
                <span class="badge <?php echo $_SESSION['tipo_usuario'] == 'premium' ? 'premium' : ''; ?>">
                    <?php echo strtoupper($_SESSION['tipo_usuario']); ?>
                </span>
            </div>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h2>¡Bienvenido/a, <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?>! 👋</h2>
            <p>Este es tu panel de entrenamiento. Aquí podrás crear y gestionar tus ejercicios, llevar un registro de tus sesiones y alcanzar tus objetivos fitness.</p>
        </div>
        
        <h2 style="margin-bottom: 20px; color: #333;">Funciones Disponibles</h2>
        
        <div class="funciones-grid">
            <a href="crear_rutina.php" class="funcion-card">
                <div class="funcion-icon">📝</div>
                <h3>Crear Rutina</h3>
                <p>Crea tu rutina semanal completa con múltiples ejercicios por día.</p>
            </a>
            
            <a href="mis_rutinas.php" class="funcion-card">
                <div class="funcion-icon">📋</div>
                <h3>Mis Rutinas</h3>
                <p>Visualiza y gestiona todas tus rutinas creadas. Edita o elimina según necesites.</p>
            </a>
            
            <a href="biblioteca_ejercicios.php" class="funcion-card">
                <div class="funcion-icon">📚</div>
                <h3>Biblioteca de Ejercicios</h3>
                <p>Explora ejercicios disponibles. <?php echo $_SESSION['tipo_usuario'] != 'standard' ? 'Agrega tus propios ejercicios' : 'Actualiza a Premium para agregar ejercicios'; ?>.</p>
            </a>
            
            <?php if ($_SESSION['tipo_usuario'] == 'standard'): ?>
            <div class="funcion-card bloqueado">
                <div class="funcion-icon">⭐</div>
                <h3>Ejercicios Ilimitados <span class="premium-badge">PREMIUM</span></h3>
                <p>Con la versión premium podrás crear más de 3 ejercicios por día.</p>
            </div>
            <?php endif; ?>
            
            <?php if ($_SESSION['tipo_usuario'] == 'admin'): ?>
            <a href="admin_usuarios.php" class="funcion-card">
                <div class="funcion-icon">👥</div>
                <h3>Gestionar Usuarios</h3>
                <p>Administra usuarios y actualiza membresías a Premium.</p>
            </a>
            <?php endif; ?>
        </div>
        
        <?php if ($_SESSION['tipo_usuario'] == 'standard'): ?>
        <div class="info-box">
            <p><strong>Cuenta Standard:</strong> Puedes crear hasta 3 ejercicios por día. Para crear ejercicios ilimitados, actualiza a Premium contactando al administrador.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
