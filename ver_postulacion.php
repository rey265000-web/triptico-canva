<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['rol']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

$postulacion = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, e.*, c.nombre as carrera_nombre, 
                                                        a.nombre_completo, a.apellido_completo, a.matricula 
                                                        FROM postulaciones p 
                                                        INNER JOIN empresas e ON p.id_empresa = e.id 
                                                        LEFT JOIN carreras c ON p.id_carrera_postulacion = c.id
                                                        INNER JOIN alumnos a ON p.id_alumno = a.id 
                                                        WHERE p.id = $id"));

if (!$postulacion) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['rol'] == 'alumno' && $postulacion['id_alumno'] != $_SESSION['id']) {
    header("Location: alumno.php");
    exit();
}

$nombre = $postulacion['nombre_completo'] . ' ' . $postulacion['apellido_completo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Postulacion - SGEP</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav>
        <h1>Detalle de Postulacion</h1>
        <a href="alumno.php?seccion=postulaciones"><button class="btn-danger">Volver</button></a>
    </nav>

    <div class="container">
        <div class="card">
            <h3>Informacion General</h3>
            <div class="form-row">
                <div class="form-group"><label>ID:</label><input type="text" value="<?php echo $postulacion['id']; ?>" readonly></div>
                <div class="form-group"><label>Estado:</label><input type="text" value="<?php echo $postulacion['estado']; ?>" readonly></div>
                <div class="form-group"><label>Fecha:</label><input type="text" value="<?php echo $postulacion['fecha_postulacion']; ?>" readonly></div>
            </div>
            <div class="form-group"><label>Numero de Oficio:</label><input type="text" value="<?php echo $postulacion['numero_oficio']; ?>" readonly></div>
        </div>

        <div class="card">
            <h3>Alumno</h3>
            <div class="form-row">
                <div class="form-group"><label>Nombre:</label><input type="text" value="<?php echo $nombre; ?>" readonly></div>
                <div class="form-group"><label>Matricula:</label><input type="text" value="<?php echo $postulacion['matricula']; ?>" readonly></div>
                <div class="form-group"><label>Carrera Seleccionada:</label><input type="text" value="<?php echo $postulacion['carrera_nombre']; ?>" readonly></div>
            </div>
        </div>

        <div class="card">
            <h3>Empresa</h3>
            <div class="form-row">
                <div class="form-group"><label>Empresa:</label><input type="text" value="<?php echo $postulacion['nombre_empresa']; ?>" readonly></div>
                <div class="form-group"><label>Modalidad:</label><input type="text" value="<?php echo $postulacion['modalidad']; ?>" readonly></div>
                <div class="form-group"><label>Ubicacion:</label><input type="text" value="<?php echo $postulacion['ubicacion']; ?>" readonly></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Contacto:</label><input type="text" value="<?php echo $postulacion['nombre_contacto']; ?>" readonly></div>
                <div class="form-group"><label>Telefono:</label><input type="text" value="<?php echo $postulacion['telefono_empresa']; ?>" readonly></div>
                <div class="form-group"><label>Correo:</label><input type="text" value="<?php echo $postulacion['correo_empresa']; ?>" readonly></div>
            </div>
        </div>
    </div>
</body>
</html>