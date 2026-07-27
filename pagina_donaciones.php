<?php
/**
 * Pagina de donaciones
 * nueva funcionalidad desarrollada en la rama
 * "feature/pagina-donaciones" como parte del flujo de pull request).
 *
 * Funcionalidades:
 *  - Listar campañas de recaudacion activas con su avance (barra de progreso).
 *  - Registrar una donacion mediante formulario (INSERT con sentencia preparada).
 *  - Actualizar el monto recaudado de la campaña.
 *  - Mostrar una notificacion cuando una campaña alcanza su meta.
 *
 * Base de datos: MySQL / MariaDB via XAMPP (ver script_base_datos.sql)
 */

// --- Conexion a la base de datos ---
$host = "127.0.0.1";
$dbname = "ong_web";
$user = "root";
$pass = "";

try {
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

$mensaje = "";
$notificacionMeta = "";

// --- Procesar nueva donacion ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["donar"])) {
    $idCampana = (int) $_POST["id_campana"];
    $nombreDonante = trim($_POST["nombre_donante"]);
    $emailDonante = trim($_POST["email_donante"]);
    $monto = (float) $_POST["monto"];

    if ($nombreDonante !== "" && $emailDonante !== "" && $monto > 0) {
        // Insertar la donacion (sentencia preparada, evita inyeccion SQL)
        $sqlInsert = "INSERT INTO donaciones (id_campana, nombre_donante, email_donante, monto)
                      VALUES (:id_campana, :nombre, :email, :monto)";
        $stmt = $conexion->prepare($sqlInsert);
        $stmt->execute([
            ":id_campana" => $idCampana,
            ":nombre" => $nombreDonante,
            ":email" => $emailDonante,
            ":monto" => $monto
        ]);

        // Actualizar el monto recaudado de la campaña
        $sqlUpdate = "UPDATE campanas SET monto_recaudado = monto_recaudado + :monto
                      WHERE id_campana = :id_campana";
        $stmt2 = $conexion->prepare($sqlUpdate);
        $stmt2->execute([":monto" => $monto, ":id_campana" => $idCampana]);

        $mensaje = "Donacion registrada correctamente. Gracias por tu aporte.";

        // Verificar si la campaña alcanzo o supero su meta
        $stmt3 = $conexion->prepare("SELECT nombre, meta_monto, monto_recaudado FROM campanas WHERE id_campana = :id");
        $stmt3->execute([":id" => $idCampana]);
        $campanaActual = $stmt3->fetch(PDO::FETCH_ASSOC);
        if ($campanaActual && $campanaActual["monto_recaudado"] >= $campanaActual["meta_monto"]) {
            $notificacionMeta = "Meta alcanzada para la campana \"" . $campanaActual["nombre"] . "\".";
        }
    } else {
        $mensaje = "Debes completar nombre, correo y un monto valido.";
    }
}

// --- Obtener campañas para listar ---
$campanas = $conexion->query("SELECT * FROM campanas ORDER BY fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Donaciones - Organizacion sin fines de lucro</title>
<style>
    body { font-family: Arial, sans-serif; margin: 0 auto; max-width: 700px; padding: 20px; color: #222; }
    h1 { font-size: 22px; border-bottom: 2px solid #2c7a4b; padding-bottom: 8px; }
    .campana { border: 1px solid #ccc; border-radius: 6px; padding: 14px; margin-bottom: 18px; }
    .campana h2 { font-size: 17px; margin: 0 0 6px 0; }
    .barra-fondo { background: #e0e0e0; border-radius: 6px; height: 14px; overflow: hidden; }
    .barra-avance { background: #2c7a4b; height: 100%; }
    .datos-avance { font-size: 13px; color: #444; margin-top: 4px; }
    form.donar { margin-top: 10px; }
    form.donar input { display: block; width: 100%; margin-bottom: 8px; padding: 6px; box-sizing: border-box; }
    form.donar button { background: #2c7a4b; color: #fff; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; }
    .mensaje { background: #eef7ee; border: 1px solid #2c7a4b; padding: 10px; border-radius: 6px; margin-bottom: 16px; }
    .notificacion { background: #fff6e0; border: 1px solid #cc9a06; padding: 10px; border-radius: 6px; margin-bottom: 16px; }
</style>
</head>
<body>

<h1>Campañas de recaudacion de fondos</h1>

<?php if ($mensaje !== ""): ?>
    <div class="mensaje"><?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>

<?php if ($notificacionMeta !== ""): ?>
    <div class="notificacion"><?php echo htmlspecialchars($notificacionMeta); ?></div>
<?php endif; ?>

<?php foreach ($campanas as $c):
    $porcentaje = $c["meta_monto"] > 0 ? min(100, ($c["monto_recaudado"] / $c["meta_monto"]) * 100) : 0;
?>
    <div class="campana">
        <h2><?php echo htmlspecialchars($c["nombre"]); ?></h2>
        <p><?php echo htmlspecialchars($c["descripcion"]); ?></p>

        <div class="barra-fondo">
            <div class="barra-avance" style="width: <?php echo round($porcentaje); ?>%;"></div>
        </div>
        <div class="datos-avance">
            Recaudado: $<?php echo number_format($c["monto_recaudado"], 0, ",", "."); ?>
            de $<?php echo number_format($c["meta_monto"], 0, ",", "."); ?>
            (<?php echo round($porcentaje); ?>%)
        </div>

        <?php if ($c["monto_recaudado"] >= $c["meta_monto"]): ?>
            <p><strong>Meta cumplida. Gracias a todos los donantes.</strong></p>
        <?php else: ?>
            <form class="donar" method="post" action="">
                <input type="hidden" name="id_campana" value="<?php echo $c['id_campana']; ?>">
                <input type="text" name="nombre_donante" placeholder="Nombre completo" required>
                <input type="email" name="email_donante" placeholder="Correo electronico" required>
                <input type="number" name="monto" placeholder="Monto a donar" min="1" step="1" required>
                <button type="submit" name="donar" value="1">Donar</button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

</body>
</html>