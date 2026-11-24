<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(Config::DB_HOST, Config::DB_USERNAME, Config::DB_PASSWORD, Config::DB_NAME);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$solo_completadas = isset($_GET['completadas']) && $_GET['completadas'] === '1';

// Verifica si existe la columna commission_rate; si no, usa 60% (0.60) por defecto
$col_check = $conn->query("SHOW COLUMNS FROM ea_users LIKE 'commission_rate'");
$tiene_comision = ($col_check && $col_check->num_rows > 0);
$campo_comision = $tiene_comision ? 'u.commission_rate' : '0.60';

$filtro_estado = $solo_completadas ? "AND a.status = 'Completada'" : "AND a.status != 'Cancelada'";

$sql = "
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) AS barbero,
        ROUND($campo_comision * 100) AS porcentaje,
        COUNT(a.id) AS total_citas,
        COALESCE(SUM(s.price), 0) AS total_generado,
        COALESCE(SUM(s.price * $campo_comision), 0) AS pago_barbero,
        COALESCE(SUM(s.price * (1 - $campo_comision)), 0) AS ganancia_local
    FROM ea_appointments a
    JOIN ea_users u ON a.id_users_provider = u.id
    JOIN ea_services s ON a.id_services = s.id
    WHERE DATE(a.start_datetime) = ?
      AND a.is_unavailability = 0
      $filtro_estado
    GROUP BY u.id
    ORDER BY total_generado DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $fecha);
$stmt->execute();
$resultado = $stmt->get_result();

$filas = [];
$gran_total = 0;
$total_barberos = 0;
$total_local = 0;

while ($row = $resultado->fetch_assoc()) {
    $filas[] = $row;
    $gran_total += $row['total_generado'];
    $total_barberos += $row['pago_barbero'];
    $total_local += $row['ganancia_local'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre Diario - BarberCR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0b0b0d; color: #f4f4f6; font-family: system-ui, sans-serif; }
        .card-bcr { background-color: #141418; border: 1px solid #26262e; border-radius: 12px; }
        .border-red { border-top: 3px solid #D90429; }
        .btn-red { background-color: #D90429; border-color: #D90429; color: #fff; font-weight: bold; }
        .btn-red:hover { background-color: #ef233c; color: #fff; }
        .table-dark-bcr { --bs-table-bg: #141418; --bs-table-border-color: #26262e; color: #f4f4f6; }
    </style>
</head>
<body class="py-4">
<div class="container" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><span style="color:#D90429;">Barber</span>CR · Cierre de Caja</h3>
            <small class="text-secondary">Liquidación diaria y cálculo automático de comisiones</small>
        </div>
        <a href="index.php/backend" class="btn btn-outline-light btn-sm">Volver al Calendario</a>
    </div>

    <form method="GET" class="card-bcr p-3 mb-4 row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label small text-secondary">Fecha a consultar</label>
            <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="form-control bg-dark text-white border-secondary">
        </div>
        <div class="col-sm-5">
            <label class="form-label small text-secondary">Filtro de citas</label>
            <select name="completadas" class="form-select bg-dark text-white border-secondary">
                <option value="0" <?= !$solo_completadas ? 'selected' : '' ?>>Todas las citas activas del día</option>
                <option value="1" <?= $solo_completadas ? 'selected' : '' ?>>Solo marcadas como "Completada"</option>
            </select>
        </div>
        <div class="col-sm-3">
            <button type="submit" class="btn btn-red w-100">Calcular</button>
        </div>
    </form>

    <div class="row g-3 mb-4 text-center">
        <div class="col-md-4">
            <div class="card-bcr border-red p-3">
                <span class="text-secondary small">Total Facturado</span>
                <h3 class="fw-bold mt-1 mb-0">₡<?= number_format($gran_total, 0, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-bcr border-red p-3">
                <span class="text-secondary small">Pago a Barberos</span>
                <h3 class="fw-bold mt-1 mb-0 text-warning">₡<?= number_format($total_barberos, 0, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-bcr border-red p-3">
                <span class="text-secondary small">Ganancia Neta Local</span>
                <h3 class="fw-bold mt-1 mb-0 text-success">₡<?= number_format($total_local, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <div class="card-bcr p-3">
        <div class="table-responsive">
            <table class="table table-dark-bcr align-middle mb-0">
                <thead>
                    <tr class="text-secondary small">
                        <th>Barbero</th>
                        <th class="text-center">Cortes</th>
                        <th class="text-center">Esquema</th>
                        <th class="text-end">Generado</th>
                        <th class="text-end">Comisión Barbero</th>
                        <th class="text-end">Para el Local</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filas)): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay citas registradas para esta fecha.</td></tr>
                    <?php else: ?>
                        <?php foreach ($filas as $f): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($f['barbero']) ?></td>
                                <td class="text-center"><?= $f['total_citas'] ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?= $f['porcentaje'] ?>% / <?= 100 - $f['porcentaje'] ?>%</span></td>
                                <td class="text-end">₡<?= number_format($f['total_generado'], 0, ',', '.') ?></td>
                                <td class="text-end text-warning fw-bold">₡<?= number_format($f['pago_barbero'], 0, ',', '.') ?></td>
                                <td class="text-end text-success fw-bold">₡<?= number_format($f['ganancia_local'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>