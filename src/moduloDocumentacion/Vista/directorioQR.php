<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2, 6]);
require_once __DIR__ . '/../Model/DocumentacionPublicaModelo.php';
$categoriasDocumentales = DocumentacionPublicaModelo::categorias();
?>
<div id="sec-doc-estatica" class="view-section hidden">
    <h3 class="mb-md">Directorio de Documentación Médica</h3>
    <p class="text-muted mb-3">Cada código abre los documentos activos de su categoría. También podés abrir el listado desde el enlace.</p>
    <div class="qr-grid">
        <?php foreach ($categoriasDocumentales as $categoriaDocumental): ?>
            <div class="qr-card">
                <div class="qr-header" style="background:#0f766e;color:white;">
                    <h4><?= htmlspecialchars($categoriaDocumental['nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
                </div>
                <div class="qr-body">
                    <img data-qr-documentacion width="180" height="180" class="qr-img" loading="lazy"
                         alt="QR de <?= htmlspecialchars($categoriaDocumental['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                    <p class="text-center mt-2">
                        <a data-enlace-documentacion href="documentosPublicos.php?categoria=<?= (int) $categoriaDocumental['id_categoria'] ?>"
                           target="_blank" rel="noopener">Abrir documentos</a>
                    </p>
                    <p data-error-qr hidden>No se pudo cargar el QR. Usá el enlace para abrir los documentos.</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (!$categoriasDocumentales): ?><p>No hay categorías documentales registradas.</p><?php endif; ?>
</div>
<script>
    document.querySelectorAll('#sec-doc-estatica .qr-card').forEach(tarjeta => {
        const enlace = tarjeta.querySelector('[data-enlace-documentacion]');
        const imagen = tarjeta.querySelector('[data-qr-documentacion]');
        const aviso = tarjeta.querySelector('[data-error-qr]');
        imagen.addEventListener('error', () => { imagen.hidden = true; aviso.hidden = false; });
        imagen.src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(enlace.href);
    });
</script>
