<?php
// Ejecutar a través Apache en XAMPP, ej: http://localhost/app_organizacion.php

// 1. Conexión a MySQL
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "organizacion";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexion fallida: " . $conn->connect_error);
}

$vista    = isset($_GET['vista']) ? $_GET['vista'] : 'menu';
$mensaje  = "";

// 2. Procesamiento de Formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    // Registro de PROYECTO
    if ($_POST['accion'] === 'guardar_proyecto') {
        $nombre       = trim($_POST['nombre']);
        $descripcion  = trim($_POST['descripcion']);
        $presupuesto  = $_POST['presupuesto'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin    = $_POST['fecha_fin'];

        if ($nombre !== '' && is_numeric($presupuesto)) {
            $stmt = $conn->prepare("INSERT INTO proyecto (nombre, descripcion, presupuesto, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdss", $nombre, $descripcion, $presupuesto, $fecha_inicio, $fecha_fin);
            $mensaje = $stmt->execute()
                ? "Proyecto registrado correctamente."
                : "Error al registrar proyecto: " . $stmt->error;
            $stmt->close();
        } else {
            $mensaje = "Datos de proyecto invalidos.";
        }
        $vista = 'proyecto';
    }

    // Registro de DONANTE
    if ($_POST['accion'] === 'guardar_donante') {
        $nombre    = trim($_POST['nombre']);
        $email     = trim($_POST['email']);
        $direccion = trim($_POST['direccion']);
        $telefono  = trim($_POST['telefono']);

        if ($nombre !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("INSERT INTO donante (nombre, email, direccion, telefono) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $email, $direccion, $telefono);
            $mensaje = $stmt->execute()
                ? "Donante registrado correctamente."
                : "Error al registrar donante: " . $stmt->error;
            $stmt->close();
        } else {
            $mensaje = "Datos de donante invalidos.";
        }
        $vista = 'donante';
    }

    // Registro de DONACION (solo utiliza id_proyecto e id_donante ya existentes)
    if ($_POST['accion'] === 'guardar_donacion') {
        $monto       = $_POST['monto'];
        $fecha       = $_POST['fecha'];
        $id_proyecto = $_POST['id_proyecto'];
        $id_donante  = $_POST['id_donante'];

        if (is_numeric($monto) && $id_proyecto !== '' && $id_donante !== '') {
            $stmt = $conn->prepare("INSERT INTO donacion (monto, fecha, id_proyecto, id_donante) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("dsii", $monto, $fecha, $id_proyecto, $id_donante);
            $mensaje = $stmt->execute()
                ? "Donacion registrada correctamente."
                : "Error al registrar donacion: " . $stmt->error;
            $stmt->close();
        } else {
            $mensaje = "Datos de donacion invalidos.";
        }
        $vista = 'donacion';
    }
}

// 3. Consultas

// Consulta simple para ver contenido de tabla
function listarTabla($conn, $tabla) {
    $filas = [];
    $resultado = $conn->query("SELECT * FROM $tabla");
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
    }
    return $filas;
}

// Consulta avanzada para ver proyectos con mas de dos donaciones
function proyectosConDonaciones($conn) {
    $sql = "SELECT p.nombre AS proyecto,
                   COUNT(d.id_donacion) AS total_donaciones,
                   SUM(d.monto) AS monto_total
            FROM proyecto p
            INNER JOIN donacion d ON p.id_proyecto = d.id_proyecto
            GROUP BY p.id_proyecto, p.nombre
            HAVING COUNT(d.id_donacion) > 2
            ORDER BY monto_total DESC";
    $resultado = $conn->query($sql);
    $filas = [];
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
    }
    return $filas;
}

$proyectos = listarTabla($conn, 'proyecto');
$donantes  = listarTabla($conn, 'donante');
$donaciones = listarTabla($conn, 'donacion');
$reporte    = ($vista === 'reporte') ? proyectosConDonaciones($conn) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Gestion de Proyectos y Donaciones</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
		nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
		table { border-collapse: collapse; width: 100%; margin-top: 10px; }
		th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
		th { background-color: #eee; }
		form { max-width: 420px; margin-top: 10px; }
		label { display: block; margin-top: 8px; }
		input, textarea, select { width: 100%; padding: 4px; }
		.mensaje { color: #006600; font-weight: bold; }
	</style>
	<script>
		// 4. Validaciones de ddatos con JS
		function validarProyecto() {
			var nombre = document.getElementById('nombre').value;
			var presupuesto = document.getElementById('presupuesto').value;
			if (nombre === '' || presupuesto === '' || isNaN(presupuesto)) {
				alert('Ingresa un nombre y un presupuesto valido.');
				return false;
			}
			return true;
		}

		function validarDonante() {
			var nombre = document.getElementById('nombre').value;
			var email = document.getElementById('email').value;
			if (nombre === '' || email.indexOf('@') === -1) {
				alert('Ingresa un nombre y un mail valido.');
				return false;
			}
			return true;
		}

		function validarDonacion() {
			var monto = document.getElementById('monto').value;
			var idProyecto = document.getElementById('id_proyecto').value;
			var idDonante = document.getElementById('id_donante').value;
			if (monto === '' || isNaN(monto) || idProyecto === '' || idDonante === '') {
				alert('Completa monto, proyecto y donante correctamente.');
				return false;
			}
			return true;
		}
	</script>
</head>
<body>

<h1>Organizacion sin fines de lucro - Gestor web</h1>
<nav>
    <a href="?vista=menu">Inicio</a>
    <a href="?vista=proyecto">Proyectos</a>
    <a href="?vista=donante">Donantes</a>
    <a href="?vista=donacion">Donaciones</a>
    <a href="?vista=reporte">Reporte avanzado</a>
</nav>
<hr>

<?php if ($mensaje): ?>
    <p class="mensaje"><?php echo htmlspecialchars($mensaje); ?></p>
<?php endif; ?>

<?php if ($vista === 'menu'): ?>

    <h2>Bienvenido!</h2>
    <p>Utiliza el menu superior para:</p>
	<p>• Registrar proyectos</p>
	<p>• Registrar donantes</p>
	<p>• Registrar donaciones</p>
    <p>• Ver el reporte avanzado.</p>

<?php elseif ($vista === 'proyecto'): ?>

    <h2>Registrar proyecto</h2>
    <form method="post" onsubmit="return validarProyecto()">
        <input type="hidden" name="accion" value="guardar_proyecto">
        <label for="nombre">Nombre del proyecto</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="descripcion">Descripcion</label>
        <textarea id="descripcion" name="descripcion" rows="3"></textarea>

        <label for="presupuesto">Presupuesto</label>
        <input type="text" id="presupuesto" name="presupuesto" required>

        <label for="fecha_inicio">Fecha de inicio</label>
        <input type="date" id="fecha_inicio" name="fecha_inicio" required>

        <label for="fecha_fin">Fecha de termino</label>
        <input type="date" id="fecha_fin" name="fecha_fin" required>

        <br><br>
        <input type="submit" value="Guardar proyecto">
    </form>

    <h3>Proyectos registrados</h3>
    <table>
        <tr><th>ID</th><th>Nombre</th><th>Descripcion</th><th>Presupuesto</th><th>Inicio</th><th>Termino</th></tr>
        <?php foreach ($proyectos as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['id_proyecto']); ?></td>
            <td><?php echo htmlspecialchars($p['nombre']); ?></td>
            <td><?php echo htmlspecialchars($p['descripcion']); ?></td>
            <td><?php echo htmlspecialchars($p['presupuesto']); ?></td>
            <td><?php echo htmlspecialchars($p['fecha_inicio']); ?></td>
            <td><?php echo htmlspecialchars($p['fecha_fin']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

<?php elseif ($vista === 'donante'): ?>

    <h2>Registrar donante</h2>
    <form method="post" onsubmit="return validarDonante()">
        <input type="hidden" name="accion" value="guardar_donante">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="direccion">Direccion</label>
        <input type="text" id="direccion" name="direccion">

        <label for="telefono">Telefono</label>
        <input type="text" id="telefono" name="telefono">

        <br><br>
        <input type="submit" value="Guardar donante">
    </form>

    <h3>Donantes registrados</h3>
    <table>
        <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Direccion</th><th>Telefono</th></tr>
        <?php foreach ($donantes as $d): ?>
        <tr>
            <td><?php echo htmlspecialchars($d['id_donante']); ?></td>
            <td><?php echo htmlspecialchars($d['nombre']); ?></td>
            <td><?php echo htmlspecialchars($d['email']); ?></td>
            <td><?php echo htmlspecialchars($d['direccion']); ?></td>
            <td><?php echo htmlspecialchars($d['telefono']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

<?php elseif ($vista === 'donacion'): ?>

    <h2>Registrar donacion</h2>
    <form method="post" onsubmit="return validarDonacion()">
        <input type="hidden" name="accion" value="guardar_donacion">
        <label for="monto">Monto</label>
        <input type="text" id="monto" name="monto" required>

        <label for="fecha">Fecha</label>
        <input type="date" id="fecha" name="fecha" required>

        <label for="id_proyecto">Proyecto</label>
        <select id="id_proyecto" name="id_proyecto" required>
            <option value="">Selecciona un proyecto</option>
            <?php foreach ($proyectos as $p): ?>
                <option value="<?php echo $p['id_proyecto']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="id_donante">Donante</label>
        <select id="id_donante" name="id_donante" required>
            <option value="">Selecciona un donante</option>
            <?php foreach ($donantes as $d): ?>
                <option value="<?php echo $d['id_donante']; ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
            <?php endforeach; ?>
        </select>

        <br><br>
        <input type="submit" value="Guardar donacion">
    </form>

    <h3>Donaciones registradas</h3>
    <table>
        <tr><th>ID</th><th>Monto</th><th>Fecha</th><th>ID Proyecto</th><th>ID Donante</th></tr>
        <?php foreach ($donaciones as $don): ?>
        <tr>
            <td><?php echo htmlspecialchars($don['id_donacion']); ?></td>
            <td><?php echo htmlspecialchars($don['monto']); ?></td>
            <td><?php echo htmlspecialchars($don['fecha']); ?></td>
            <td><?php echo htmlspecialchars($don['id_proyecto']); ?></td>
            <td><?php echo htmlspecialchars($don['id_donante']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

<?php elseif ($vista === 'reporte'): ?>

    <h2>Reporte: proyectos con mas de dos donaciones</h2>
    <table>
        <tr><th>Proyecto</th><th>Total de donaciones</th><th>Monto total recaudado</th></tr>
        <?php foreach ($reporte as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['proyecto']); ?></td>
            <td><?php echo htmlspecialchars($r['total_donaciones']); ?></td>
            <td><?php echo htmlspecialchars($r['monto_total']); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($reporte)): ?>
        <tr><td colspan="3">Aun no hay proyectos con mas de dos donaciones.</td></tr>
        <?php endif; ?>
    </table>

<?php endif; ?>

</body>
</html>
<?php
$conn->close();
?>