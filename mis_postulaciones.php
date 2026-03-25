<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

if (isset($_POST['actualizar_estado'])) {
    if (isset($_POST['nuevo_estado']) && isset($_POST['id_postulacion']) && isset($_POST['id_empresa'])) {
        $id_postulacion = $_POST['id_postulacion'];
        $nuevo_estado = $_POST['nuevo_estado'];
        $id_empresa = $_POST['id_empresa'];
        
        mysqli_query($conn, "UPDATE postulaciones SET estado='$nuevo_estado' WHERE id=$id_postulacion");
        
        if ($nuevo_estado == 'rechazado') {
            mysqli_query($conn, "UPDATE empresas SET cupos = cupos + 1 WHERE id=$id_empresa");
        }
        
        $mensaje = "Estado actualizado";
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
    <title>Mis Postulaciones - SGEP</title>
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
            <a href="mis_postulaciones.php?rol=admin"><button class="menu-btn">Postulaciones</button></a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <?php if($mensaje != ""): ?>
        <div class="<?php echo $tipo_mensaje == 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3><?php echo $_SESSION['rol'] == 'admin' ? 'Todas las Postulaciones' : 'Mis Postulaciones'; ?></h3>
        </div>

        <?php
        if ($_SESSION['rol'] == 'alumno') {
            $id_alumno = $_SESSION['id'];
            $sql = "SELECT p.*, e.* FROM postulaciones p INNER JOIN empresas e ON p.id_empresa = e.id WHERE p.id_alumno = '$id_alumno' ORDER BY p.fecha_postulacion DESC";
        } else {
            $sql = "SELECT p.*, e.*, u.nombre_completo, u.apellido_completo, u.matricula FROM postulaciones p INNER JOIN empresas e ON p.id_empresa = e.id INNER JOIN alumnos u ON p.id_alumno = u.id ORDER BY p.fecha_postulacion DESC";
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
                
                echo "<div class='form-section' style='margin-top:15px;'>";
                echo "<h3>Datos de la Empresa</h3>";
                echo "<div class='form-row'>";
                echo "<div class='form-group'><label>Ubicacion:</label><input type='text' value='{$row['ubicacion']}' readonly></div>";
                echo "<div class='form-group'><label>Carrera:</label><input type='text' value='{$row['carrera']}' readonly></div>";
                echo "<div class='form-group'><label>Modalidad:</label><input type='text' value='{$row['modalidad']}' readonly></div>";
                echo "</div>";
                echo "<div class='form-row'>";
                echo "<div class='form-group'><label>Cupos:</label><input type='text' value='{$row['cupos']}' readonly></div>";
                echo "<div class='form-group'><label>Estatus:</label><input type='text' value='{$row['estatus']}' readonly></div>";
                echo "</div>";
                echo "<div class='form-group'><label>Descripcion:</label><textarea rows='2' readonly>{$row['descripcion']}</textarea></div>";
                echo "<div class='form-row'>";
                echo "<div class='form-group'><label>Dirigido a:</label><input type='text' value='{$row['grado_dirigido']} {$row['nombre_dirigido']}' readonly></div>";
                echo "<div class='form-group'><label>Puesto:</label><input type='text' value='{$row['puesto_dirigido']}' readonly></div>";
                echo "</div>";
                echo "<div class='form-row'>";
                echo "<div class='form-group'><label>Contacto:</label><input type='text' value='{$row['nombre_contacto']}' readonly></div>";
                echo "<div class='form-group'><label>Puesto Contacto:</label><input type='text' value='{$row['puesto_contacto']}' readonly></div>";
                echo "</div>";
                echo "<div class='form-row'>";
                echo "<div class='form-group'><label>Telefono:</label><input type='text' value='{$row['telefono_empresa']}' readonly></div>";
                echo "<div class='form-group'><label>Correo:</label><input type='text' value='{$row['correo_empresa']}' readonly></div>";
                echo "</div>";
                echo "</div>";
                
                echo "<p><strong>Fecha:</strong> {$row['fecha_postulacion']}</p>";
                echo "<p><strong>Numero de Oficio:</strong> {$row['numero_oficio']}</p>";
                
                if ($_SESSION['rol'] == 'alumno') {
                    echo "<a href='ver_postulacion.php?id={$row['id']}'><button class='btn-info'>Ver Detalles Completos</button></a>";
                } else {
                    echo "<p><strong>Alumno:</strong> {$row['nombre_completo']} {$row['apellido_completo']} ({$row['matricula']})</p>";
                    
                    if ($row['estado'] == 'pendiente') {
                        echo "<form method='POST' style='margin-top:10px;'>";
                        echo "<input type='hidden' name='id_postulacion' value='{$row['id']}'>";
                        echo "<input type='hidden' name='id_empresa' value='{$row['id_empresa']}'>";
                        echo "<div class='action-buttons'>";
                        echo "<button type='submit' name='actualizar_estado' value='aceptado' class='btn-success'>Aceptar</button>";
                        echo "<button type='submit' name='actualizar_estado' value='rechazado' class='btn-danger'>Rechazar</button>";
                        echo "</div>";
                        echo "</form>";
                    }
                    echo "<a href='ver_postulacion.php?id={$row['id']}'><button class='btn-info' style='margin-top:10px;'>Ver Detalles Completos</button></a>";
                }
                
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