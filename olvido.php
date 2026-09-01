<?php
include 'bd.php';
require_once __DIR__ . '/mail_config.php';

$mensaje = null;
$error = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $Correo = trim($_POST['Correo'] ?? '');
    if($Correo === ''){
        $error = 'Ingrese su correo.';
    } else {
        try {
            $stmt = $conexion->prepare('SELECT ID, Correo FROM Usuarios WHERE Correo = :Correo LIMIT 1');
            $stmt->execute([':Correo'=>$Correo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
           
            $mensaje = 'Si el correo existe, recibirás un enlace para restablecer tu contraseña.';
            if($user){
                $token = bin2hex(random_bytes(32));
                $ins = $conexion->prepare('INSERT INTO restablecer_contrasena (user_id, token) VALUES (:uid, :tok)');
                $ins->execute([':uid'=>$user['ID'], ':tok'=>$token]);

                $mail = crearMailer();
                $mail->addAddress($user['Correo']);
                $mail->isHTML(true);
                $mail->Subject = 'Restablecer contraseña - Cloud Campus';
                $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/restablecer.php?token=' . urlencode($token);
                $mail->Body = '<p>Hola,</p><p>Para restablecer tu contraseña haz clic en el siguiente enlace:</p><p><a href="' . $url . '">Restablecer contraseña</a></p><p>Si no solicitaste este cambio, ignora este mensaje.</p>';
                $mail->send();
            }
        } catch(Exception $e){
            $error = 'Ocurrió un error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Recuperar contraseña — Punto Código</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous">

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

            box-shadow:
                0 10px 30px -10px rgba(22, 19, 15, 0.08);
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

            line-height: 1.5;

            margin-top: 0.5rem;
        }

        .recovery-icon {
            width: 58px;
            height: 58px;

            margin: 0 auto 1rem;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: rgba(227, 30, 36, 0.08);

            color: var(--red);

            font-size: 1.3rem;
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

            padding:
                0.75rem
                0.9rem
                0.75rem
                2.5rem;

            border: 1px solid var(--border);

            border-radius: 8px;

            font-family: "Inter", sans-serif;

            font-size: 0.9rem;

            outline: none;

            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }

        .input-wrapper input:focus {
            border-color: var(--red);

            box-shadow:
                0 0 0 3px rgba(227, 30, 36, 0.1);
        }


        .auth-card .btn-primary {
            width: 100%;

            justify-content: center;

            padding: 0.8rem;

            font-size: 0.95rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            text-align: center;
            line-height: 1.4;
        }
        .alert-success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
        }
        .alert-danger {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fca5a5;
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

    </style>
</head>

<body>
    <header class="wrap nav">

        <a href="index.php" class="logo">
            <img
                src="imagenes/logotipo.png"
                alt="Punto Código"
            >
        </a>

        <div class="nav-actions">

            <span
                style="font-size:0.9rem; color:var(--muted);"
            >
                ¿Recordaste tu contraseña?
            </span>

            <a
                href="index.php"
                class="btn btn-ghost"
            >
                Iniciar sesión
            </a>

        </div>

    </header>
    <div class="auth-container">

        <div class="auth-card">

            <div class="auth-header">

                <div class="recovery-icon">
                    <i class="fa-solid fa-key"></i>
                </div>

                <span class="eyebrow">
                    // recuperar acceso
                </span>

                <h1>
                    ¿Olvidaste tu contraseña?
                </h1>

                <p>
                    No te preocupes. Ingresa tu correo electrónico
                    y te enviaremos un enlace para crear una nueva contraseña.
                </p>

            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">

                <div class="form-group">

                    <label for="Correo">
                        Correo electrónico
                    </label>

                    <div class="input-wrapper">

                        <i
                            class="fa-regular fa-envelope input-icon"
                        ></i>

                        <input
                            type="email"
                            id="Correo"
                            name="Correo"
                            placeholder="tu@email.com"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary">

                    <span>
                        Enviar enlace
                    </span>

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </form>

            <div class="auth-footer">

                <a href="index.php">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a iniciar sesión
                </a>

            </div>

        </div>

    </div>

</body>

</html>
