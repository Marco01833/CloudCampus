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
                    $stmt = $conexion->prepare('INSERT INTO SesionesActivas (IDUsuario, TokenSesion, FechaInicio, Estado, Dispositivo) VALUES (?, ?, NOW(), 1, ?)');
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
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <?php if($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-header">Iniciar sesión</div>
        <div class="card-body">
          <form method="post">
            <div class="mb-3">
              <label class="form-label">Correo electrónico</label>
              <input type="email" name="Correo" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" name="Contrasena" class="form-control" required>
            </div>
            <button class="btn btn-primary" type="submit">Entrar</button>
            <a class="btn btn-link" href="registro.php">Registrarse</a>
            <a class="btn btn-link" href="olvido.php">¿Olvidaste tu contraseña?</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>