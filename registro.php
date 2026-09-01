<?php
include("bd.php");
require_once __DIR__ . '/mail_config.php';

$mensaje = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Correo = trim($_POST['Correo'] ?? '');
    $Contrasena = $_POST['Contrasena'] ?? '';
    $Metodo = $_POST['Metodo'] ?? 'enlace';
    $IdRol = 1;
    $IdPlan = 1;

    if ($Correo === '' || $Contrasena === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($Correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico inválido.';
    } else {
        try {
            $stmt = $conexion->prepare('SELECT 1 FROM Usuarios WHERE Correo = :Correo LIMIT 1');
            $stmt->execute([':Correo' => $Correo]);
            if ($stmt->fetch()) {
                $error = 'El correo ya está registrado.';
            } else {
                $hash = password_hash($Contrasena, PASSWORD_BCRYPT);
                $stmt = $conexion->prepare('INSERT INTO Usuarios (Correo, Contrasena, Estado, Verificado, IDRol, IDPlan, NumeroSesiones) VALUES (:Correo, :pass, 2, 0, :IdRol, :IdPlan, 2)');
                $stmt->execute([
                    ':Correo' => $Correo,
                    ':pass'   => $hash,
                    ':IdRol'  => $IdRol,
                    ':IdPlan' => $IdPlan
                ]);
                $userId = (int) $conexion->lastInsertId();

                $token = bin2hex(random_bytes(32));
                $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiracion = date('Y-m-d H:i:s', strtotime('+1 minutes'));

                $stmt = $conexion->prepare('INSERT INTO verificacion_email (user_id, token, codigo, fecha_expiracion) VALUES (:uid, :tok, :cod, :exp)');
                $stmt->execute([':uid' => $userId, ':tok' => $token, ':cod' => $codigo, ':exp' => $expiracion]);

                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $base = $protocol . '://' . $host . $path;
                $verificationUrl = $base . '/verificar.php?token=' . urlencode($token);

                $mail = crearMailer();
                $mail->addAddress($Correo);
                $mail->isHTML(true);
                $mail->Subject = 'Verifica tu cuenta - Cloud Campus';

                if ($Metodo === 'enlace') {
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; }
                            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
                            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
                            .header h1 { color: #2c3e50; margin: 0; font-size: 24px; }
                            .content { padding: 25px 0; }
                            .content p { color: #555; font-size: 16px; line-height: 1.6; }
                            .btn { display: inline-block; background-color: #007bff; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                            .footer { text-align: center; padding-top: 20px; border-top: 2px solid #e9ecef; color: #999; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>🎓 Cloud Campus</h1>
                            </div>
                            <div class="content">
                                <p>Hola,</p>
                                <p>Gracias por registrarte en <strong>Cloud Campus</strong>.</p>
                                <p>Haz clic en el botón para verificar tu cuenta:</p>
                                <p style="text-align: center;">
                                    <a href="' . $verificationUrl . '" class="btn">Verificar mi cuenta</a>
                                </p>
                                <p>Si no has solicitado esta cuenta, ignora este mensaje.</p>
                                <p>El enlace expirará en 24 horas.</p>
                            </div>
                            <div class="footer">
                                <p>© 2025 Cloud Campus - Todos los derechos reservados</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ';
                    $mail->AltBody = "Hola,\n\nGracias por registrarte en Cloud Campus.\n\nPara verificar tu cuenta, haz clic en este enlace:\n" . $verificationUrl . "\n\nSi no has solicitado esta cuenta, ignora este mensaje.";
                } else {
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; }
                            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
                            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
                            .header h1 { color: #2c3e50; margin: 0; font-size: 24px; }
                            .content { padding: 25px 0; }
                            .content p { color: #555; font-size: 16px; line-height: 1.6; }
                            .codigo-box { background-color: #f8f9fa; border: 2px dashed #007bff; padding: 15px; text-align: center; font-size: 28px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 8px; color: #007bff; }
                            .footer { text-align: center; padding-top: 20px; border-top: 2px solid #e9ecef; color: #999; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>🎓 Cloud Campus</h1>
                            </div>
                            <div class="content">
                                <p>Hola,</p>
                                <p>Gracias por registrarte en <strong>Cloud Campus</strong>.</p>
                                <p>Usa el siguiente código para verificar tu cuenta:</p>
                                <div class="codigo-box">' . $codigo . '</div>
                                <p>Ingresa este código en la página de verificación.</p>
                                <p>Si no has solicitado esta cuenta, ignora este mensaje.</p>
                                
                            </div>
                            <div class="footer">
                                <p>© 2025 Cloud Campus - Todos los derechos reservados</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ';
                    $mail->AltBody = "Hola,\n\nGracias por registrarte en Cloud Campus.\n\nTu código de verificación es: " . $codigo . "\n\nIngresa este código en la página de verificación.\n\nEl código expirará en 24 horas.";
                }

                $mail->send();

                if ($Metodo === 'codigo') {
                    header('Location: verificar.php?correo=' . urlencode($Correo));
                    exit;
                } else {
                    $mensaje = 'Registro exitoso. Te hemos enviado un enlace de verificación a tu correo.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Error SQL: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta — Punto Código</title>

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

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--ink);
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

        .form-terms {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.4;
        }

        .form-terms input[type="checkbox"] {
            margin-top: 0.15rem;
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
            text-align: center;
        }
        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }
        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
    </style>
</head>

<body>
 
    <header class="wrap nav">
        <a href="index.php" class="logo"><img src="imagenes/logotipo.png" alt="Punto Código"></a>
        <div class="nav-actions">
            <span style="font-size:0.9rem; color:var(--muted);">¿Ya tenés cuenta?</span>
            <a href="index.php" class="btn btn-ghost">Iniciar sesión</a>
        </div>
    </header>
 
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <span class="eyebrow">// registro</span>
                <h1>Comenzá gratis</h1>
                <p>Crea tu cuenta y empieza a programar hoy</p>
            </div>
 
            <div class="divider"><span></span></div>
 
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
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
                    <label for="Contrasena">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="Contrasena" name="Contrasena" placeholder="Mínimo 8 caracteres" required minlength="8">
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Método de verificación:</label>
                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" id="enlace" name="Metodo" value="enlace" checked>
                            <label for="enlace" style="font-size: 0.85rem; font-weight: normal;">Enviar enlace</label>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" id="codigo" name="Metodo" value="codigo">
                            <label for="codigo" style="font-size: 0.85rem; font-weight: normal;">Enviar código</label>
                        </div>
                    </div>
                </div>
 
                <div class="form-terms">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">Acepto los <a href="#" style="color:var(--ink);">términos del servicio</a> y la <a href="#" style="color:var(--ink);">política de privacidad</a>.</label>
                </div>
 
                <button type="submit" class="btn btn-primary">
                    <span>Crear mi cuenta</span> <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
 
            <div class="auth-footer">
                ¿Ya tienes una cuenta? <a href="index.php">Inicia sesión</a>
            </div>
        </div>
    </div>
 
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('Contrasena');

        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.innerHTML = type === 'password'
                ? '<i class="fa-regular fa-eye"></i>'
                : '<i class="fa-regular fa-eye-slash"></i>';
        });
    </script>
</body>

</html>