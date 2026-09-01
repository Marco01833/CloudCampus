<?php
include("../autenticacion.php");
include("../bd.php");

$user_id = $_SESSION['user_id'];
$rol_usuario = $_SESSION['rol'] ?? 0;

if ($rol_usuario != 1) {
    header('Location: ../dashboard.php');
    exit;
}

$error = null;
$mensaje = null;
$plan_actual = null;
$suscripcion_activa = null;
$planes_disponibles = [];

function verificarYActualizarSuscripcion($conexion, $user_id) {
    $stmt = $conexion->prepare("
        SELECT ID, IDPlan, FechaFin 
        FROM Suscripciones 
        WHERE IDUsuario = ? AND Estado = 1 
        ORDER BY FechaInicio DESC LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $suscripcion = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($suscripcion && $suscripcion['FechaFin'] !== null) {
        $fecha_fin = new DateTime($suscripcion['FechaFin']);
        $ahora = new DateTime();
        if ($ahora > $fecha_fin) {
            $conexion->beginTransaction();
            try {
                $stmt = $conexion->prepare("UPDATE Suscripciones SET Estado = 0 WHERE ID = ?");
                $stmt->execute([$suscripcion['ID']]);
                $stmt = $conexion->prepare("UPDATE Usuarios SET IDPlan = 1 WHERE ID = ?");
                $stmt->execute([$user_id]);
                $conexion->commit();
                return true; 
            } catch (Exception $e) {
                $conexion->rollBack();
                return false;
            }
        }
    }
    return false; 
}

verificarYActualizarSuscripcion($conexion, $user_id);

$stmt = $conexion->prepare("
    SELECT u.IDPlan, p.Nombre, p.Precio, p.Descuento, p.DuracionDias
    FROM Usuarios u
    JOIN Planes p ON u.IDPlan = p.ID
    WHERE u.ID = ?
");
$stmt->execute([$user_id]);
$plan_actual = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conexion->prepare("
    SELECT ID, IDPlan, FechaInicio, FechaFin, Estado
    FROM Suscripciones
    WHERE IDUsuario = ? AND Estado = 1
    ORDER BY FechaInicio DESC LIMIT 1
");
$stmt->execute([$user_id]);
$suscripcion_activa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($plan_actual) {
    $stmt = $conexion->prepare("
        SELECT * FROM Planes 
        WHERE ID != 1 AND ID != ? AND Precio > 0
        ORDER BY ID
    ");
    $stmt->execute([$plan_actual['IDPlan']]);
    $planes_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conexion->prepare("SELECT * FROM Planes WHERE ID != 1 AND Precio > 0 ORDER BY ID");
    $stmt->execute();
    $planes_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar_plan'])) {
    $id_plan_seleccionado = (int)$_POST['id_plan'];
    $metodo_pago = $_POST['metodo_pago'] ?? 'QR';

    if ($id_plan_seleccionado <= 0) {
        $error = "Selecciona un plan válido.";
    } else {
        try {
            $stmt = $conexion->prepare("SELECT * FROM Planes WHERE ID = ?");
            $stmt->execute([$id_plan_seleccionado]);
            $plan_seleccionado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan_seleccionado) {
                $error = "El plan seleccionado no existe.";
            } elseif ($plan_seleccionado['Precio'] == 0) {
                $error = "El plan básico no se puede comprar, se asigna automáticamente.";
            } else {
                $total = $plan_seleccionado['Precio'];
                $descuento_monto = 0; 
                $conexion->beginTransaction();
                $sql_factura = "INSERT INTO Facturas (IDUsuario, Fecha, Total, Estado, MetodoPago) 
                               VALUES (?, NOW(), ?, 1, ?)";
                $stmt_factura = $conexion->prepare($sql_factura);
                $stmt_factura->execute([$user_id, $total, $metodo_pago]);
                $factura_id = $conexion->lastInsertId();
                $sql_detalle = "INSERT INTO DetalleFactura (IDFactura, PrecioUnidad, Descuento, TipoCompra, IDReferencia) 
                               VALUES (?, ?, 0, 'plan', ?)";
                $stmt_detalle = $conexion->prepare($sql_detalle);
                $stmt_detalle->execute([$factura_id, $total, $id_plan_seleccionado]);

                $stmt = $conexion->prepare("UPDATE Suscripciones SET Estado = 0 WHERE IDUsuario = ? AND Estado = 1");
                $stmt->execute([$user_id]);

                $duracion_dias = $plan_seleccionado['DuracionDias'];
                $fecha_fin = ($duracion_dias) ? date('Y-m-d H:i:s', strtotime("+$duracion_dias days")) : null;

                $sql_suscripcion = "INSERT INTO Suscripciones (IDUsuario, IDPlan, FechaInicio, FechaFin, Estado) 
                                   VALUES (?, ?, NOW(), ?, 1)";
                $stmt_suscripcion = $conexion->prepare($sql_suscripcion);
                $stmt_suscripcion->execute([$user_id, $id_plan_seleccionado, $fecha_fin]);
                $stmt = $conexion->prepare("UPDATE Usuarios SET IDPlan = ? WHERE ID = ?");
                $stmt->execute([$id_plan_seleccionado, $user_id]);
                $conexion->commit();
                $_SESSION['mensaje_plan'] = "¡Plan actualizado con éxito! Ahora tienes el plan " . $plan_seleccionado['Nombre'];
                header('Location: ../dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $conexion->rollBack();
            $error = "Error al procesar la compra del plan: " . $e->getMessage();
        }
    }
}

include("../header.php");
?>

<div class="container py-4">
    <h1 class="mb-4">📦 Actualizar Plan</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Tu plan actual</h5>
        </div>
        <div class="card-body">
            <p><strong>Plan:</strong> <?= htmlspecialchars($plan_actual['Nombre'] ?? 'No definido') ?></p>
            <?php if ($plan_actual && $plan_actual['Precio'] > 0): ?>
                <p><strong>Precio:</strong> $<?= number_format($plan_actual['Precio'], 2) ?></p>
                <p><strong>Descuento para cursos:</strong> <?= $plan_actual['Descuento'] ?>%</p>
            <?php endif; ?>
            <?php if ($suscripcion_activa): ?>
                <p><strong>Fecha de inicio:</strong> <?= date('d/m/Y', strtotime($suscripcion_activa['FechaInicio'])) ?></p>
                <?php if ($suscripcion_activa['FechaFin']): ?>
                    <p><strong>Válido hasta:</strong> <?= date('d/m/Y H:i:s', strtotime($suscripcion_activa['FechaFin'])) ?></p>
                    <?php 
                    $fecha_fin_ts = strtotime($suscripcion_activa['FechaFin']);
                    $now = time();
                    $restante = max(0, $fecha_fin_ts - $now);
                    if ($restante > 0): ?>
                        <div class="mt-3">
                            <strong>Tiempo restante:</strong>
                            <span id="countdown" data-seconds="<?= $restante ?>" class="fw-bold text-primary">Calculando...</span>
                        </div>
                    <?php else: ?>
                        <div class="mt-3 text-danger">
                            <strong>⚠️ Suscripción expirada</strong>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p><strong>Válido hasta:</strong> Sin fecha de vencimiento (plan básico)</p>
                <?php endif; ?>
            <?php else: ?>
                <p><strong>Estado:</strong> Sin suscripción activa (plan básico)</p>
            <?php endif; ?>
        </div>
    </div>

    <h3 class="mb-3">Planes disponibles para cambiar</h3>
    <?php if (empty($planes_disponibles)): ?>
        <div class="alert alert-info">
            No hay otros planes de pago disponibles. El plan básico es gratuito y se asigna automáticamente cuando tu suscripción premium caduca.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($planes_disponibles as $plan): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header text-center bg-warning">
                            <h4 class="mb-0"><?= htmlspecialchars($plan['Nombre']) ?></h4>
                        </div>
                        <div class="card-body text-center">
                            <p class="display-6 fw-bold">
                                $<?= number_format($plan['Precio'], 2) ?>
                                <?php if ($plan['Descuento'] > 0): ?>
                                    <span class="badge bg-success"><?= $plan['Descuento'] ?>% de descuento</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($plan['DuracionDias']): ?>
                                <p class="text-muted">Válido por <?= $plan['DuracionDias'] ?> días</p>
                            <?php else: ?>
                                <p class="text-muted">Sin límite de tiempo</p>
                            <?php endif; ?>
                            <p><small>Acceso completo a todos los cursos</small></p>
                            <form method="post">
                                <input type="hidden" name="id_plan" value="<?= $plan['ID'] ?>">
                                <div class="mb-2">
                                    <select name="metodo_pago" class="form-select">
                                        <option value="QR">Pago con QR</option>
                                        <option value="Tarjeta">Pago con tarjeta</option>
                                    </select>
                                </div>
                                <button type="submit" name="comprar_plan" class="btn btn-primary">
                                    Comprar plan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('countdown');
    if (!el) return;
    let seconds = parseInt(el.dataset.seconds) || 0;
    function update() {
        if (seconds <= 0) {
            el.textContent = '¡Suscripción expirada!';
            el.className = 'fw-bold text-danger';
            return;
        }
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        let parts = [];
        if (days > 0) parts.push(days + 'd');
        parts.push(String(hours).padStart(2, '0') + 'h');
        parts.push(String(minutes).padStart(2, '0') + 'm');
        parts.push(String(secs).padStart(2, '0') + 's');
        el.textContent = parts.join(' ');
        seconds--;
    }
    update();
    setInterval(update, 1000);
});
</script>

<?php include("../footer.php"); ?>