<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 3, 5]);

require_once __DIR__ . '/../Model/SeguimientoModelo.php';

$idUsuarioSeguimiento = (int) $_SESSION['id_usuario'];
$esChoferSeguimiento = tieneRol(5);
$esDTISeguimiento = tieneRol(1);

$viajesSeguimiento = SeguimientoModelo::listar(
    $esChoferSeguimiento ? $idUsuarioSeguimiento : null
);

if (empty($_SESSION['csrf_seguimiento'])) {
    $_SESSION['csrf_seguimiento'] = bin2hex(random_bytes(32));
}

function escaparSeguimiento($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$accionesSeguimiento = [
    2 => [3, 'Iniciar traslado'],
    3 => [4, 'Llegué a destino'],
    4 => [5, 'Iniciar retorno'],
    5 => [6, 'Finalizar traslado']
];

$activosSeguimiento = array_filter(
    $viajesSeguimiento,
    fn($viaje) => !in_array((int) $viaje['id_estado'], [6, 7], true)
);
?>

<?php foreach (['mensaje_seguimiento', 'error_seguimiento'] as $clave): ?>
    <?php if (isset($_SESSION[$clave])): ?>
        <p role="alert">
            <?= escaparSeguimiento($_SESSION[$clave]) ?>
        </p>
        <?php unset($_SESSION[$clave]); ?>
    <?php endif; ?>
<?php endforeach; ?>

<div id="seccion-traslados" class="view-section active">
    <div class="tabs-container">
        <button class="tab-btn active" data-tab="tab-proceso">
            Traslados activos
        </button>
        <button class="tab-btn" data-tab="tab-historial">
            Historial de estados
        </button>
    </div>

    <div id="tab-proceso" class="tab-content active">
        <?php if (!$activosSeguimiento): ?>
            <p>No hay traslados activos para mostrar.</p>
        <?php endif; ?>

        <?php foreach ($activosSeguimiento as $viaje): ?>
            <div class="card-traslado">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <div class="header-info">
                        <h3>
                            Traslado #<?= (int) $viaje['id_traslado'] ?>
                            — <?= escaparSeguimiento($viaje['matricula'] ?? 'Sin vehículo') ?>
                        </h3>
                        <span class="badge urgencia-media">
                            <?= escaparSeguimiento($viaje['estado'] ?? 'Sin estado') ?>
                        </span>
                    </div>
                    <i data-lucide="chevron-down" class="accordion-icon"></i>
                </div>

                <div class="accordion-content open">
                    <div class="card-formulario mb-3">
                        <?php
                        $datosViaje = [
                            'Solicitud' => '#' . $viaje['id_solicitud'],
                            'Tipo de elemento' => $viaje['elemento'],
                            'Origen' => $viaje['origen'],
                            'Destino' => $viaje['destino'],
                            'Chofer' => $viaje['chofer'] ?? 'Sin asignar',
                            'Enfermería' => $viaje['enfermeria'] ?? 'Sin asignar'
                        ];
                        ?>

                        <?php foreach ($datosViaje as $etiqueta => $valor): ?>
                            <p>
                                <strong><?= escaparSeguimiento($etiqueta) ?>:</strong>
                                <?= escaparSeguimiento($valor) ?>
                            </p>
                        <?php endforeach; ?>

                        <?php
                        $accionViaje = $accionesSeguimiento[
                            (int) $viaje['id_estado']
                        ] ?? null;

                        $puedeCambiar = $esDTISeguimiento
                            || ($esChoferSeguimiento
                                && (int) $viaje['id_chofer'] === $idUsuarioSeguimiento);
                        ?>

                        <?php if ($puedeCambiar && $accionViaje): ?>
                            <form action="../Controlador/CambiarEstadoTraslado.php"
                                  method="POST">
                                <input type="hidden" name="token"
                                       value="<?= escaparSeguimiento($_SESSION['csrf_seguimiento']) ?>">
                                <input type="hidden" name="id_traslado"
                                       value="<?= (int) $viaje['id_traslado'] ?>">
                                <input type="hidden" name="id_estado"
                                       value="<?= (int) $accionViaje[0] ?>">

                                <button type="submit" class="btn-gigante btn-llegando">
                                    <?= escaparSeguimiento($accionViaje[1]) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="tab-historial" class="tab-content hidden">
        <?php if (!$viajesSeguimiento): ?>
            <p>No hay historial para mostrar.</p>
        <?php endif; ?>

        <?php foreach ($viajesSeguimiento as $viaje): ?>
            <div class="card-traslado">
                <h3>Traslado #<?= (int) $viaje['id_traslado'] ?></h3>

                <div class="timeline">
                    <?php foreach (
                        SeguimientoModelo::historial((int) $viaje['id_traslado'])
                        as $evento
                    ): ?>
                        <div class="timeline-step completed">
                            <div class="step-marker">✓</div>
                            <div class="step-content">
                                <h4><?= escaparSeguimiento($evento['estado']) ?></h4>
                                <p>
                                    <?= escaparSeguimiento($evento['fecha_hora']) ?>
                                    — <?= escaparSeguimiento($evento['usuario']) ?>
                                </p>
                                <p><?= escaparSeguimiento($evento['observaciones']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>