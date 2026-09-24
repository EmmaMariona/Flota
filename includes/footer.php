<?php if (!isset($es_pagina_publica) || !$es_pagina_publica): ?>
        </main>
        <footer class="mt-auto py-3 bg-dark border-top border-secondary border-opacity-25 px-4 text-center text-secondary small">
            <span>&copy; <?= date('Y') ?> Sistema de Gestión de Flota Vehicular | Proyecto de Ingeniería en Computación</span>
        </footer>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Scripts de la Aplicación -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>