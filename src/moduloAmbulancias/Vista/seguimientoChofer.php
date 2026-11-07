<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 3, 5]);

require_once __DIR__ . '/../Model/SeguimientoModelo.php';

$idUsuarioSeguimiento = (int) $_SESSION['id_usuario'];
$esChoferSeguimiento = tieneRol(5);
$esDTISeguimiento = tieneRol(1);

$viajesSeguimiento = SeguimientoModelo::listar(
    $esChoferSeguimiento ? $idUsuarioSeguimiento : null,
    true
);

if (empty($_SESSION['csrf_seguimiento'])) {
    $_SESSION['csrf_seguimiento'] = bin2hex(random_bytes(32));
}

function escaparSeguimiento($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$accionesSeguimiento = [
    2 => [3, t('seguimiento.accion_iniciar')],
    3 => [4, t('seguimiento.accion_llegada')],
    4 => [5, t('seguimiento.accion_retorno')],
    5 => [6, t('seguimiento.accion_finalizar')]
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
            <?= te('traslado.activos') ?>
        </button>
        <button class="tab-btn" data-tab="tab-historial">
            <?= te('seguimiento.historial_estados') ?>
        </button>
    </div>

    <div id="tab-proceso" class="tab-content active">
        <?php if (!$activosSeguimiento): ?>
            <p><?= te('seguimiento.sin_activos') ?></p>
        <?php endif; ?>

        <?php foreach ($activosSeguimiento as $viaje): ?>
            <div class="card-traslado">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <div class="header-info">
                        <h3>
                            <?= te('traslado.numero', ['id' => (int) $viaje['id_traslado']]) ?>
                            — <?= escaparSeguimiento($viaje['matricula'] ?? t('traslado.sin_vehiculo')) ?>
                        </h3>
                        <span class="badge urgencia-media">
                            <?= escaparSeguimiento($viaje['estado'] !== null ? etiquetaBd('estado', $viaje['id_estado'], $viaje['estado']) : t('traslado.sin_estado')) ?>
                        </span>
                    </div>
                    <i data-lucide="chevron-down" class="accordion-icon"></i>
                </div>

                <div class="accordion-content open">
                    <div class="card-formulario mb-3">
                        <?php
                        $datosViaje = [
                            t('campo.solicitud') => '#' . $viaje['id_solicitud'],
                            t('campo.tipo_elemento') => etiquetaBd('elemento', $viaje['id_tipo_elemento'], $viaje['elemento']),
                            t('campo.origen') => nombreUbicacion($viaje['origen'], $viaje['origen_tipo']),
                            t('campo.destino') => nombreUbicacion($viaje['destino'], $viaje['destino_tipo']),
                            t('campo.chofer') => $viaje['chofer'] ?? t('comun.sin_asignar'),
                            t('campo.enfermeria') => $viaje['enfermeria'] ?? t('comun.sin_asignar')
                        ];
                        if (!empty($viaje['direccion_domicilio'])) {
                            $datosViaje[t('campo.direccion_domicilio')] = $viaje['direccion_domicilio'];
                        }
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
            <p><?= te('seguimiento.sin_historial') ?></p>
        <?php endif; ?>

        <?php foreach ($viajesSeguimiento as $viaje): ?>
            <div class="card-traslado">
                <h3><?= te('traslado.numero', ['id' => (int) $viaje['id_traslado']]) ?></h3>

                <div class="timeline">
                    <?php foreach (
                        SeguimientoModelo::historial((int) $viaje['id_traslado'])
                        as $evento
                    ): ?>
                        <div class="timeline-step completed">
                            <div class="step-marker">✓</div>
                            <div class="step-content">
                                <h4><?= escaparSeguimiento(etiquetaBd('estado', $evento['id_estado'], $evento['estado'])) ?></h4>
                                <p>
                                    <?= escaparSeguimiento($evento['fecha_hora']) ?>
                                    — <?= escaparSeguimiento($evento['usuario']) ?>
                                </p>
                                <p><?= escaparSeguimiento(textoObservacionHistorial($evento['observaciones'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>