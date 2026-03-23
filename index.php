<?php
require 'soapClient.php';

$data = consultarSymbiot();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Monitoreo Energía Symbiot</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<h1>⚡ Monitoreo Energía</h1>

<table>

<thead>
<tr>
<th>Medidor</th>
<th>Tipo</th>
<th>Timestamp</th>
<th>Valor</th>
</tr>
</thead>

<tbody>

<?php foreach($data as $row): ?>

<tr>
<td><?= $row['meter'] ?></td>
<td><?= $row['type'] ?></td>
<td><?= $row['time'] ?></td>
<td><?= $row['value'] ?></td>
</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</body>
</html>