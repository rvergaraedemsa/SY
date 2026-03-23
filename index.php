<?php
require 'functions.php';

$data = [];

// 🔥 PROCESAR POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action == "query") {
        $meters = $_POST['meters'] ?? [];
        $result = consultarMedidores(implode(",", $meters));
    }

    if ($action == "events") {
        $result = consultarEventos(implode(",", $_POST['meters']));
    }

    if ($action == "control") {
        $result = controlarMedidor($_POST['meter'], $_POST['cmd']);
    }

    // 🔥 guardar resultado en sesión
    session_start();
    $_SESSION['data'] = $result;

    // 🔥 REDIRECT (clave)
    header("Location: index.php");
    exit;
}

// 🔥 GET: recuperar datos
session_start();
if (isset($_SESSION['data'])) {
    $data = $_SESSION['data'];
    unset($_SESSION['data']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

    <meta charset="UTF-8">
    <title>Panel Symbiot</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>⚡ Panel Symbiot</h1>

    <div class="container">

        <div class="menu">

            <!-- 🔍 CONSULTA -->
            <form method="POST">
                <h3>🔍 Consulta Medidores</h3>

                <select name="meters[]" multiple size="5">
                    <option value="413 MT880_PROD_94134458_172.33.10.182">
                        413 MT880_PROD_94134458_172.33.10.182
                    </option>

                    <option value="413 MT880_LAB_90385962_172_33_3_201">
                        413 MT880_LAB_90385962_172_33_3_201
                    </option>
                </select>
                                <input type="hidden" name="action" value="query">
                <button type="submit">Consultar</button>
                <p><small>Usa CTRL para seleccionar varios</small></p>
            </form>


            <!-- 📜 EVENTOS -->
            <form method="POST">
                <h3>📜 Ver Eventos</h3>

                <select name="meters[]" multiple size="5">
                    <option value="413 MT880_PROD_94134458_172.33.10.182">
                        413 MT880_PROD_94134458_172.33.10.182
                    </option>

                    <option value="413 MT880_LAB_90385962_172_33_3_201">
                        413 MT880_LAB_90385962_172_33_3_201
                    </option>
                </select>               
                
                <input type="hidden" name="action" value="events">
                <button type="submit">Ver Eventos</button>
                <p><small>Usa CTRL para seleccionar varios</small></p>
            </form>


            <!-- ⚡ CONTROL -->
            <form method="POST">
                <h3>⚡ Control Medidor</h3>

                <select name="meter">
                    <option value="413 MT880_PROD_94134458_172.33.10.182">
                        413 MT880_PROD_94134458_172.33.10.182
                    </option>

                    <option value="413 MT880_LAB_90385962_172_33_3_201">
                        413 MT880_LAB_90385962_172_33_3_201
                    </option>
                </select>

                <select name="cmd">
                    <option value="disconnect">Cortar</option>
                    <option value="connect">Reconectar</option>
                </select>

                <input type="hidden" name="action" value="control">
                <button type="submit">Ejecutar</button>
            </form>
        </div>


        <!-- 📊 RESULTADOS -->
        <?php if (!empty($data)): ?>
            <table id="tablaDatos">

                <thead>
                    <tr>
                        <?php foreach (array_keys($data[0]) as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $val): ?>
                                <td><?= htmlspecialchars($val) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
            <div style="display:flex; gap:15px; margin-top:20px;">
                <div style="flex:1;">
                    <label>Medidor</label>
                    <select id="filtroMedidor"></select>
                </div>

                <div style="flex:1;">
                    <label>Tipo</label>
                    <select id="filtroTipo"></select>
                </div>
            </div>
            <button onclick="generarGrafico()">Actualizar gráfico</button>
            <canvas id="grafico" style="margin-top:30px;"></canvas>
            <div id="datosGrafico" style="margin-top:30px; padding:15px; background:#f5f5f5; border-radius:5px; max-height:300px; overflow-y:auto;"></div>

        <?php endif; ?>

    </div>
    <script>
        let tablaData = <?= json_encode($data) ?>;
    </script>
    <script src="index.js"></script>
</body>

</html>