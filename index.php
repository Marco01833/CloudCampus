<?php
include 'bd.php';
include 'dispositivos.php';
session_start();

if(isset($_SESSION['user_id'])){
    header('Location: dashboard.php');
    exit;
}
$error = null;
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $Correo = trim($_POST['Correo'] ?? '');
    $Contrasena = $_POST['Contrasena'] ?? '';
    
    if(empty($Correo) || empty($Contrasena)){
        $error = 'Ingrese su correo y contraseña.';
    } else {
        $stmt = $conexion->prepare('SELECT ID, Correo, Contrasena, Estado, Verificado, IDRol, intentos_fallidos, bloqueado_hasta, NumeroSesiones FROM Usuarios WHERE Correo = :Correo LIMIT 1');
        $stmt->execute([':Correo'=>$Correo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$user){
            $error = 'Credenciales inválidas.';
        } elseif($user['Estado'] != 1){
            $error = 'Tu usuario está bloqueado';
        } elseif($user['Verificado'] != 1){
            $error = 'Tu correo no está verificado. Revisa tu correo electrónico.';
        } else {
            if ($user['bloqueado_hasta'] && new DateTime() >= new DateTime($user['bloqueado_hasta'])) {
                $upd = $conexion->prepare('UPDATE Usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE ID = :id');
                $upd->execute([':id' => $user['ID']]);
                $user['intentos_fallidos'] = 0;
                $user['bloqueado_hasta'] = null;
            }

            $bloqueado_hasta = $user['bloqueado_hasta'];
            if($bloqueado_hasta && new DateTime() < new DateTime($bloqueado_hasta)){
                $error = 'Cuenta bloqueada. Intenta nuevamente después de ' . date('H:i', strtotime($bloqueado_hasta));
            } else {
                if(password_verify($Contrasena, $user['Contrasena'])){
                    $upd = $conexion->prepare('UPDATE Usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE ID = :id');
                    $upd->execute([':id'=>$user['ID']]);
                    $limite = $user['NumeroSesiones'] ?? 2;
                    $stmt = $conexion->prepare('SELECT COUNT(*) as total FROM SesionesActivas WHERE IDUsuario = ? AND Estado = 1');
                    $stmt->execute([$user['ID']]);
                    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    if($total >= $limite){
                        $stmt = $conexion->prepare('SELECT ID FROM SesionesActivas WHERE IDUsuario = ? AND Estado = 1 ORDER BY FechaInicio ASC LIMIT 1');
                        $stmt->execute([$user['ID']]);
                        $oldest = $stmt->fetch(PDO::FETCH_ASSOC);
                        if($oldest){
                            $upd = $conexion->prepare('UPDATE SesionesActivas SET Estado = 0 WHERE ID = ?');
                            $upd->execute([$oldest['ID']]);
                        }
                    }
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['ID'];
                    $_SESSION['correo'] = $user['Correo'];
                    $_SESSION['rol'] = $user['IDRol'];
                    $token = bin2hex(random_bytes(32));
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
                    $dispositivo = obtenerNombreDispositivo($userAgent); 
                    $stmt = $conexion->prepare('INSERT INTO SesionesActivas (IDUsuario, TokenSesion, FechaInicio, Estado, Dispositivo) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 3 HOUR), 1, ?)');
                    if($stmt->execute([$user['ID'], $token, $dispositivo])){
                        $_SESSION['session_id'] = $conexion->lastInsertId();
                        header('Location: dashboard.php');
                        exit;
                    }
                } else {
                    $intentos = $user['intentos_fallidos'] + 1;
                    $bloqueo = null;
                    if($intentos >= 3){
                        $bloqueo = date('Y-m-d H:i:s', strtotime('+3 minutes'));
                        $error = 'Has superado el número de intentos. Cuenta bloqueada por 3 minutos.';
                    } else {
                        $error = 'Credenciales inválidas. Intento ' . $intentos . ' de 3.';
                    }
                    $upd = $conexion->prepare('UPDATE Usuarios SET intentos_fallidos = :int, bloqueado_hasta = :bloq WHERE ID = :id');
                    $upd->execute([':int'=>$intentos, ':bloq'=>$bloqueo, ':id'=>$user['ID']]);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — Punto Código</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

    <link rel="stylesheet" href="css/style.css">

    <style>
        .auth-container {
            min-height: calc(100vh - 80px - 100px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
        }

        .auth-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 10px 30px -10px rgba(22, 19, 15, 0.08);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            font-family: "Poppins", sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            margin-top: 0.5rem;
        }

        .auth-header p {
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: 0.2rem;
        }

        .social-auth {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .btn-social {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--ink);
            font-size: 0.85rem;
            padding: 0.65rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: border-color 0.2s, background 0.2s;
        }

        .btn-social:hover {
            border-color: var(--ink);
            background: #fff;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: var(--muted);
            font-size: 0.75rem;
            font-family: "JetBrains Mono", monospace;
            margin: 1.5rem 0;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border);
        }

        .divider span {
            padding: 0 0.75rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-bottom: 1.25rem;
        }

        .form-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--ink);
        }

        .forgot-link {
            font-size: 0.8rem;
            color: var(--red);
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 0.9rem;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.75rem 0.9rem 0.75rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: "Inter", sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-wrapper input:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(227, 30, 36, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 0.9rem;
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 0.9rem;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: var(--muted);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            accent-color: var(--red);
        }

        .auth-card .btn-primary {
            width: 100%;
            justify-content: center;
            padding: 0.8rem;
            font-size: 0.95rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.88rem;
            color: var(--muted);
        }

        .auth-footer a {
            color: var(--red);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
    </style>
</head>

<body>

    <header class="wrap nav">
        <a href="index.php" class="logo"><img src="imagenes/logotipo.png" alt="Punto Código"></a>
        <div class="nav-actions">
            <span style="font-size:0.9rem; color:var(--muted);">¿No tenés cuenta?</span>
            <a href="registro.php" class="btn btn-ghost">Empezar gratis</a>
        </div>
    </header>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <span class="eyebrow">// bienvenido</span>
                <h1>Iniciar sesión</h1>
                <p>Ingresá tus credenciales para continuar tu aprendizaje</p>
            </div>

            <div class="divider"><span></span></div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="Correo">Correo electrónico</label>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" id="Correo" name="Correo" placeholder="tu@email.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-group-header">
                        <label for="Contrasena">Contraseña</label>
                        <a href="olvido.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="Contrasena" name="Contrasena" placeholder="Ingresá tu contraseña" required>
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Recordarme en este dispositivo</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span>Iniciar sesión</span> <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="auth-footer">
                ¿Aún no tienes cuenta? <a href="registro.php">Regístrate gratis</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const input = document.getElementById('Contrasena');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-regular fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa-regular fa-eye';
            }
        });
    </script>

</body>

</html>