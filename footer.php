<?php
if (!isset($url_base)) {
    $url_base = '/cloudcampus/';
}
?>

<footer class="footer mt-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-4">
                <h5 class="fw-bold text-danger">Punto Codigo</h5>
                <p class="text-muted mb-0">
                    Aprende, crea y desarrolla tus habilidades en línea.
                </p>
            </div>

            <div class="col-md-4">
                <h6 class="fw-bold">Enlaces rápidos</h6>
                <ul class="list-unstyled">
                    <li>
                        <a href="<?= htmlspecialchars($url_base) ?>dashboard.php"
                           class="text-decoration-none text-muted">
                            Inicio
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?= htmlspecialchars($url_base) ?>Datos_personales/index.php"
                           class="text-decoration-none text-muted">
                            Mi perfil
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-md-4">
                <h6 class="fw-bold">Contacto</h6>
                <p class="text-muted mb-1">soporte@puntocodigo.com</p>
                <p class="text-muted mb-0">Lunes a viernes, 8:00 a 17:00</p>
            </div>
        </div>

        <hr>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <small class="text-muted">
                &copy; <?= date('Y') ?> Punto Codigo. Todos los derechos reservados.
            </small>

            <div>
                <a href="#" class="text-muted text-decoration-none me-3">Privacidad</a>
                <a href="#" class="text-muted text-decoration-none">Términos</a>
            </div>
        </div>
    </div>
</footer>

</main>
</body>
</html>