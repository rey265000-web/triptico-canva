<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

if (isset($_POST['accion_postulacion'])) {
    $id_postulacion = $_POST['id_postulacion'];
    $id_empresa = $_POST['id_empresa'];
    $id_carrera = $_POST['id_carrera'];
    $id_alumno = $_POST['id_alumno'];
    $accion = $_POST['accion_postulacion'];
    
    $empresa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nombre_empresa FROM empresas WHERE id=$id_empresa"));
    
    if ($accion == 'aceptar') {
        mysqli_query($conn, "UPDATE postulaciones SET estado='aceptado' WHERE id=$id_postulacion");
        mysqli_query($conn, "UPDATE empresas_carreras_lugares SET lugares_ocupados = lugares_ocupados + 1 
                            WHERE id_empresa=$id_empresa AND id_carrera=$id_carrera");
        
        $mensaje_notificacion = "Felicidades ya esta usted en una empresa: " . $empresa['nombre_empresa'];
        mysqli_query($conn, "INSERT INTO notificaciones (id_alumno, mensaje, tipo) 
                            VALUES ($id_alumno, '$mensaje_notificacion', 'aceptado')");
        
        $mensaje = "Postulación aceptada. Se notificó al alumno.";
        $tipo_mensaje = "exito";
        
    } elseif ($accion == 'rechazar') {
        mysqli_query($conn, "DELETE FROM postulaciones WHERE id=$id_postulacion");
        mysqli_query($conn, "INSERT INTO empresas_carreras_bloqueadas (id_alumno, id_empresa, id_carrera) 
                            VALUES ($id_alumno, $id_empresa, $id_carrera)");
        
        $mensaje_notificacion = "Eliga otra empresa. Su postulacion a " . $empresa['nombre_empresa'] . " fue rechazada.";
        mysqli_query($conn, "INSERT INTO notificaciones (id_alumno, mensaje, tipo) 
                            VALUES ($id_alumno, '$mensaje_notificacion', 'rechazado')");
        
        $mensaje = "Postulación rechazada. El alumno puede postularse a otras empresas.";
        $tipo_mensaje = "exito";
    }
}

if ($_SESSION['rol'] == 'admin') {
    $nombre_usuario = $_SESSION['nombre'];
} else {
    $nombre_usuario = $_SESSION['nombre_completo'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Postulaciones - SGEP</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav>
        <h1><?php echo $_SESSION['rol'] == 'admin' ? 'Panel Direccion' : 'Portal del Alumno'; ?></h1>
        <div class="user-info">
            <span><?php echo $nombre_usuario; ?></span>
            <a href="logout.php"><button class="btn-danger">Cerrar Sesion</button></a>
        </div>
        <div class="menu-buttons">
            <?php if($_SESSION['rol'] == 'alumno'): ?>
            <a href="alumno.php"><button class="menu-btn">Empresas</button></a>
            <a href="mis_postulaciones.php?rol=alumno"><button class="menu-btn">Mis Postulaciones</button></a>
            <?php else: ?>
            <a href="admin.php"><button class="menu-btn">Empresas</button></a>
            <a href="admin.php?seccion=postulaciones"><button class="menu-btn">Postulaciones</button></a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <?php if($mensaje != ""): ?>
        <div class="<?php echo $tipo_mensaje == 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="info-card">
            <h3><?php echo $_SESSION['rol'] == 'admin' ? 'Todas las Postulaciones' : 'Mis Postulaciones'; ?></h3>
        </div>

        <?php
        if ($_SESSION['rol'] == 'alumno') {
            $sql = "SELECT p.*, e.nombre_empresa, c.nombre as carrera_nombre
                    FROM postulaciones p 
                    INNER JOIN empresas e ON p.id_empresa = e.id 
                    LEFT JOIN carreras c ON p.id_carrera_postulacion = c.id 
                    WHERE p.id_alumno = {$_SESSION['id']} 
                    ORDER BY p.fecha_postulacion DESC";
        } else {
            $sql = "SELECT p.*, e.nombre_empresa, c.nombre as carrera_nombre, 
                           a.nombre_completo, a.matricula
                    FROM postulaciones p 
                    INNER JOIN empresas e ON p.id_empresa = e.id 
                    LEFT JOIN carreras c ON p.id_carrera_postulacion = c.id 
                    INNER JOIN alumnos a ON p.id_alumno = a.id 
                    ORDER BY p.fecha_postulacion DESC";
        }
        
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                if ($row['estado'] == 'pendiente') {
                    $badge = 'badge-pendiente';
                    $estadoTexto = 'Pendiente';
                } elseif ($row['estado'] == 'aceptado') {
                    $badge = 'badge-aceptado';
                    $estadoTexto = 'Aceptado';
                } else {
                    $badge = 'badge-rechazado';
                    $estadoTexto = 'Rechazado';
                }
                
                echo "<div class='card'>";
                echo "<h4>{$row['nombre_empresa']} - <span class='badge {$badge}'>{$estadoTexto}</span></h4>";
                
                if ($_SESSION['rol'] == 'admin') {
                    echo "<p><strong>Alumno:</strong> {$row['nombre_completo']} ({$row['matricula']})</p>";
                }
                
                echo "<p><strong>Carrera:</strong> " . ($row['carrera_nombre'] ? $row['carrera_nombre'] : 'N/A') . "</p>";
                echo "<p><strong>Fecha:</strong> {$row['fecha_postulacion']}</p>";
                echo "<p><strong>Numero de Oficio:</strong> {$row['numero_oficio']}</p>";
                
                if ($_SESSION['rol'] == 'admin' && $row['estado'] == 'pendiente') {
                    echo "<form method='POST' style='margin-top:10px;'>";
                    echo "<input type='hidden' name='id_postulacion' value='{$row['id']}'>";
                    echo "<input type='hidden' name='id_empresa' value='{$row['id_empresa']}'>";
                    echo "<input type='hidden' name='id_carrera' value='{$row['id_carrera_postulacion']}'>";
                    echo "<input type='hidden' name='id_alumno' value='{$row['id_alumno']}'>";
                    echo "<input type='hidden' name='accion_postulacion' value=''>";
                    echo "<div class='action-buttons'>";
                    echo "<button type='button' onclick=\"this.form.accion_postulacion.value='aceptar'; this.form.submit();\" class='btn-success'>✓ Aceptar</button>";
                    echo "<button type='button' onclick=\"this.form.accion_postulacion.value='rechazar'; this.form.submit();\" class='btn-danger'>✗ Rechazar</button>";
                    echo "</div>";
                    echo "</form>";
                }
                
                echo "<a href='ver_postulacion.php?id={$row['id']}'><button class='btn-info' style='margin-top:10px;'>Ver Detalles</button></a>";
                echo "</div>";
            }
        } else {
            echo "<div class='card' style='text-align:center; padding:50px;'>";
            echo "<h3>No hay postulaciones</h3>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>