<?php
session_start();

date_default_timezone_set('America/Bogota');



// if (!isset($_SESSION['ge_email'])) {
//     header('Location: login.php');
//     exit();
// }



require_once '../modelo/conexion.php';

// // Obtener el código y la ciudad del gestor logueado
// $ge_cod = $_SESSION['ge_cod'];
// $ge_ciudad = $_SESSION['ge_ciudad'];

$query = "SELECT DISTINCT tu_ciudad FROM turno";
$stmt = $conn->prepare($query);
$stmt->execute();
$ciudades = $stmt->fetchAll(PDO::FETCH_COLUMN);

$ciudad_seleccionada = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'conteos'; 

if (!empty($ciudad_seleccionada)) {
    $fecha_inicio = date('Y-m-d 00:00:00');
    $fecha_fin = date('Y-m-d 23:59:59');

    

    if ($vista == 'conteos') {
        $query = "SELECT 
        asignacion.as_caja, 
        COUNT(asignacion.as_caja) AS total_turnos
    FROM (
        SELECT DISTINCT as_caja
        FROM asignacion
        WHERE as_ciudad = :ciudad_seleccionada
    ) AS caja
    LEFT JOIN asignacion ON caja.as_caja = asignacion.as_caja
        AND asignacion.as_est = 'finalizado'
        AND asignacion.as_fec_reg BETWEEN :fecha_inicio AND :fecha_fin
        AND asignacion.as_ciudad = :ciudad_seleccionada
    GROUP BY asignacion.as_caja, asignacion.as_ciudad;
    ";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
        $stmt->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
        $stmt->bindParam(':ciudad_seleccionada', $ciudad_seleccionada, PDO::PARAM_STR);
        $stmt->execute();
        $cajas_turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener el histórico de turnos atendidos por día
                $query_historico = "SELECT as_caja, DATE(as_fec_reg) AS dia, COUNT(*) AS total_turnos
                FROM asignacion
                WHERE as_est = 'finalizado'
                AND as_fec_reg BETWEEN date_trunc('month', CURRENT_DATE)
                AND (date_trunc('month', CURRENT_DATE) + INTERVAL '1 month - 1 day')
                AND as_ciudad = :ciudad_seleccionada
                GROUP BY dia, as_caja
                ORDER BY dia, as_caja";

                $stmt_historico = $conn->prepare($query_historico);
                $stmt_historico->bindParam(':ciudad_seleccionada', $ciudad_seleccionada, PDO::PARAM_STR);
                $stmt_historico->execute();
                $historico_turnos = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

        // Calcular el total de todos los "Total Turnos"
        $total_turnos_historico = array_sum(array_column($historico_turnos, 'total_turnos'));

    } else if ($vista == 'novedades') {
        // Obtener las novedades del día
            $query_novedades = "SELECT as_caja, COALESCE(as_obs, 'Sin observaciones') AS as_obs, COUNT(as_obs) AS total_novedades
            FROM asignacion
            WHERE as_est = 'finalizado'
            AND as_fec_reg BETWEEN :fecha_inicio AND :fecha_fin
            AND as_ciudad = :ciudad_seleccionada
            GROUP BY as_caja, as_obs";

            $stmt_novedades = $conn->prepare($query_novedades);
            $stmt_novedades->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
            $stmt_novedades->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
            $stmt_novedades->bindParam(':ciudad_seleccionada', $ciudad_seleccionada, PDO::PARAM_STR);
            $stmt_novedades->execute();
            $novedades_turnos = $stmt_novedades->fetchAll(PDO::FETCH_ASSOC);


       // Obtener el histórico de novedades por día y caja
$query_historico_novedades = "SELECT as_caja, DATE(as_fec_reg) AS dia, COUNT(as_obs) AS total_novedades 
                               FROM asignacion 
                               WHERE as_est = 'finalizado'
                                 AND as_fec_reg BETWEEN date_trunc('month', CURRENT_DATE)
                                 AND (date_trunc('month', CURRENT_DATE) + INTERVAL '1 month - 1 day')
                                 AND as_ciudad = :ciudad_seleccionada
                               GROUP BY dia, as_caja
                               ORDER BY dia, as_caja";

$stmt_historico_novedades = $conn->prepare($query_historico_novedades);
$stmt_historico_novedades->bindParam(':ciudad_seleccionada', $ciudad_seleccionada, PDO::PARAM_STR);
$stmt_historico_novedades->execute();
$historico_novedades = $stmt_historico_novedades->fetchAll(PDO::FETCH_ASSOC);


        // Calcular el total de todos los "Total Turnos"
        $total_historico_novedad = array_sum(array_column($historico_novedades, 'total_novedades'));
    }


}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Turnos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
    .sidebar {
        background-color: #4A90E2;
        backdrop-filter: blur(10px);
        transition: transform 0.3s ease;
        z-index: 1000;
        transform: translateX(-100%);
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .sidebar-overlay {
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 100%;
        z-index: 999;
        display: none;
    }

    .sidebar-overlay.show {
        display: block;
    }

    @media (min-width: 768px) {
        .sidebar {
            width: 250px;
            transform: translateX(0);
        }

        .sidebar-closed {
            transform: translateX(-100%);
        }

        .sidebar-overlay {
            display: none;
        }
    }

    .dropdown-container {
        max-width: 200px;
    }

    .table-auto th,
    .table-auto td {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dropdown-container {
        max-width: 200px;
    }

    .table-auto th,
    .table-auto td {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    </style>
</head>

<body class="bg-gray-100">
    <div class="md:flex">
        <div class="md:hidden">
            <button id="sidebarToggle" class="fixed top-4 left-4 z-50 bg-blue-500 text-white px-4 py-2 rounded">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7">
                    </path>
                </svg>
            </button>
        </div>
        <div id="sidebar" class="sidebar">
            <div class="py-4 px-6">
                <h2 class="text-2xl font-bold mb-6">Dashboard</h2>
                <ul>
                    <li class="mb-4">
                        <a href="?vista=conteos" class="block py-2 px-4 rounded hover:bg-blue-700">Conteos</a>
                    </li>
                    <li>
                        <a href="?vista=novedades" class="block py-2 px-4 rounded hover:bg-blue-700">Novedades</a>
                    </li>
                    <br>
                    <li>
                        <a href="logout.php" class="block py-2 px-4 rounded hover:bg-blue-700">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
        <div id="sidebarOverlay" class="sidebar-overlay"></div>
        <div class="flex-1 md:ml-64 p-8">
            <div class="container mx-auto py-8">
                <h1 class="text-3xl font-bold mb-4">Estadísticas de Turnos</h1>
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-4">
                    <input type="hidden" name="vista" value="<?php echo $vista; ?>">
                    <label for="ciudad" class="block mb-2 font-bold">Selecciona una ciudad:</label>
                    <div class="flex items-center">
                        <select name="ciudad" id="ciudad" class="block w-40 p-2 border rounded">
                            <option value="">Seleccione una ciudad</option>
                            <?php foreach ($ciudades as $ciudad): ?>
                            <option value="<?php echo $ciudad; ?>"
                                <?php if ($ciudad == $ciudad_seleccionada) echo 'selected'; ?>><?php echo $ciudad; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded ml-2">Ver
                            Estadísticas</button>
                    </div>
                </form>


                <?php if (!empty($ciudad_seleccionada)): ?>
                <?php if ($vista == 'conteos'): ?>
                <div class="mt-8">
                    <h2 class="text-2xl font-bold mb-4">Conteos de Turnos día para
                        <?php echo htmlspecialchars($ciudad_seleccionada); ?></h2>
                    <div class="flex">
                        <table class="table-auto w-full max-w-sm bg-white shadow rounded overflow-hidden mr-4">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 w-1/2">Caja</th>
                                    <th class="px-4 py-2 w-1/2">Total Turnos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cajas_turnos as $caja_turno): ?>
                                <tr>
                                    <td class="border px-4 py-2 truncate">
                                        <?php echo htmlspecialchars($caja_turno['as_caja']); ?></td>
                                    <td class="border px-4 py-2 truncate">
                                        <?php echo htmlspecialchars($caja_turno['total_turnos']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="bg-white shadow rounded overflow-hidden w-2/3">
                            <canvas id="historicoTurnos"></canvas>
                        </div>
                    </div>
                </div>
                <div class="mt-8">
                    <h2 class="text-2xl font-bold mb-4">Histórico de Turnos Atendidos en el Mes</h2>
                    <table class="table-auto w-full bg-white shadow rounded overflow-hidden">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Caja</th>
                                <th class="px-4 py-2">Día</th>
                                <th class="px-4 py-2">Total Turnos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico_turnos as $historico_turno): ?>
                            <tr>
                                <td class="border px-4 py-2">
                                    <?php echo htmlspecialchars($historico_turno['as_caja']); ?></td>
                                <td class="border px-4 py-2 text-center">
                                    <?php echo htmlspecialchars($historico_turno['dia']); ?></td>
                                <td class="border px-4 py-2 text-center">
                                    <?php echo htmlspecialchars($historico_turno['total_turnos']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="px-4 py-2 text-center">Total</th>
                                <th class="px-4 py-2"></th>
                                <th class="px-4 py-2 text-center"><?php echo $total_turnos_historico; ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php elseif ($vista == 'novedades'): ?>
            <div class="mt-8">
                <h2 class="text-2xl font-bold mb-4">Novedades del Día para
                    <?php echo htmlspecialchars($ciudad_seleccionada); ?></h2>
                <table class="table-auto w-full bg-white shadow rounded overflow-hidden">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Caja</th>
                            <th class="px-4 py-2">Observación</th>
                            <th class="px-4 py-2">Total Novedades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($novedades_turnos as $novedad_turno): ?>
                        <tr>
                            <td class="border px-4 py-2 text-center">
                                <?php echo htmlspecialchars($novedad_turno['as_caja']); ?></td>
                            <td class="border px-4 py-2 text-center">
                                <?php echo htmlspecialchars($novedad_turno['as_obs']); ?></td>
                            <td class="border px-4 py-2 text-center">
                                <?php echo htmlspecialchars($novedad_turno['total_novedades']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
    // Consultar todas las cajas disponibles en la ciudad seleccionada
    $query_cajas = "SELECT DISTINCT as_caja FROM asignacion WHERE as_ciudad = :ciudad AND as_est = 'finalizado'";
    $stmt_cajas = $conn->prepare($query_cajas);
    $stmt_cajas->bindParam(':ciudad', $ciudad_seleccionada, PDO::PARAM_STR);
    $stmt_cajas->execute();
    $cajas_disponibles = $stmt_cajas->fetchAll(PDO::FETCH_COLUMN);
    // Calcular el total de novedades del día
    $total_novedades_dia = array_sum(array_column($novedades_turnos, 'total_novedades'));
    ?>
            <div class="mt-4">
                <div class="bg-white shadow rounded overflow-hidden p-4">
                    <h3 class="text-lg font-bold mb-2">Total Novedades del Día para
                        <?php echo htmlspecialchars($ciudad_seleccionada); ?></h3>
                    <p class="font-bold">Total de novedades: <?php echo $total_novedades_dia; ?></p>
                </div>
            </div>


            <div class="mt-8">
                <h2 class="text-2xl font-bold mb-4">Histórico de Novedades en el Mes</h2>
                <table class="table-auto w-full bg-white shadow rounded overflow-hidden">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Caja</th>
                            <th class="px-4 py-2">Día</th>
                            <th class="px-4 py-2">Total Novedades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico_novedades as $historico_novedad): ?>
                        <tr>
                            <td class="border px-4 py-2 text-center">
                                <?php echo htmlspecialchars($historico_novedad['as_caja']); ?></td>
                            <td class="border px-4 py-2 text-center">
                                <?php echo htmlspecialchars($historico_novedad['dia']); ?></td>
                            <td class="border px-4 py-2 text-center">
                                <?php echo htmlspecialchars($historico_novedad['total_novedades']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="px-4 py-2 text-center">Total</th>
                            <th class="px-4 py-2"></th>
                            <th class="px-4 py-2 text-center"><?php echo $total_historico_novedad; ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-6">
                <h2 class="text-2xl font-bold mb-4">Gráfico de Novedades del Mes por Caja</h2>
                <div class="bg-white shadow rounded overflow-hidden">
                    <canvas id="novedadesChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    });

    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });

    <?php if (!empty($ciudad_seleccionada)): ?>
    <?php if ($vista == 'conteos'): ?>
    const historicoTurnosCtx = document.getElementById('historicoTurnos').getContext('2d');
    new Chart(historicoTurnosCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($historico_turnos, 'dia')); ?>,
            datasets: [{
                label: 'Turnos Atendidos',
                data: <?php echo json_encode(array_column($historico_turnos, 'total_turnos')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(<?php echo rand(0, 255); ?>, <?php echo rand(0, 255); ?>, <?php echo rand(0, 255); ?>, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php elseif ($vista == 'novedades'): ?>

    const novedadesChartCtx = document.getElementById('novedadesChart').getContext('2d');
    new Chart(novedadesChartCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($historico_novedades, 'dia')); ?>,
            datasets: [
                <?php foreach ($cajas_disponibles as $caja): ?> {
                    label: 'Caja <?php echo htmlspecialchars($caja); ?>',
                    data: <?php echo json_encode(array_map(function($historico_novedad) use ($caja) {
                                return isset($historico_novedad['as_caja']) && $historico_novedad['as_caja'] === $caja ? $historico_novedad['total_novedades'] : null;
                            }, $historico_novedades)); ?>,
                    borderColor: 'rgba(<?php echo rand(0, 255); ?>, <?php echo rand(0, 255); ?>, <?php echo rand(0, 255); ?>, 1)',
                    borderWidth: 2,
                    fill: false
                },
                <?php endforeach; ?>
            ]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>
    <?php endif; ?>
    </script>

</body>

</html>