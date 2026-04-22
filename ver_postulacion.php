<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['rol']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$id_alumno = $_SESSION['id'];

$postulacion = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, e.*, c.nombre as carrera_nombre, 
                                                        a.nombre_completo, a.apellido_completo, a.matricula,
                                                        a.nss, a.nivel, a.grupo, a.telefono, 
                                                        a.correo_personal, a.correo_institucional
                                                        FROM postulaciones p 
                                                        INNER JOIN empresas e ON p.id_empresa = e.id 
                                                        LEFT JOIN carreras c ON p.id_carrera_postulacion = c.id
                                                        INNER JOIN alumnos a ON p.id_alumno = a.id 
                                                        WHERE p.id = $id"));

if (!$postulacion) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['rol'] == 'alumno' && $postulacion['id_alumno'] != $id_alumno) {
    header("Location: alumno.php");
    exit();
}

$nombre = $postulacion['nombre_completo'] . ' ' . $postulacion['apellido_completo'];

$direccion_completa = $postulacion['calle'];
if ($postulacion['num_ext']) $direccion_completa .= " #" . $postulacion['num_ext'];
if ($postulacion['num_int']) $direccion_completa .= " Int. " . $postulacion['num_int'];
$direccion_completa .= ", " . $postulacion['colonia'];
$direccion_completa .= ", " . $postulacion['ciudad'] . ", " . $postulacion['estado'];
$direccion_completa .= " C.P. " . $postulacion['cp'];

// CORRECCIÓN: Convertir a minúsculas para comparar
$estado = strtolower(trim($postulacion['estado']));
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
        <a href="<?php echo $_SESSION['rol'] == 'admin' ? 'admin.php?seccion=postulaciones' : 'alumno.php?seccion=postulaciones'; ?>">
            <button class="btn-danger">Volver</button>
        </a>
    </nav>

    <div class="container">
        <div class="card">
            <h3>Informacion General</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>ID:</label>
                    <input type="text" value="<?php echo $postulacion['id']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Estado:</label>
                    <input type="text" value="<?php echo ucfirst($postulacion['estado']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Fecha:</label>
                    <input type="text" value="<?php echo $postulacion['fecha_postulacion']; ?>" readonly>
                </div>
            </div>
            <div class="form-group">
                <label>Numero de Oficio:</label>
                <input type="text" value="<?php echo $postulacion['numero_oficio']; ?>" readonly>
            </div>
        </div>

        <div class="card">
            <h3>Alumno</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" value="<?php echo $nombre; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Matricula:</label>
                    <input type="text" value="<?php echo $postulacion['matricula']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Carrera:</label>
                    <input type="text" value="<?php echo $postulacion['carrera_nombre']; ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>NSS:</label>
                    <input type="text" value="<?php echo $postulacion['nss'] ?: 'N/A'; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Nivel:</label>
                    <input type="text" value="<?php echo $postulacion['nivel'] ?: 'N/A'; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Grupo:</label>
                    <input type="text" value="<?php echo $postulacion['grupo'] ?: 'N/A'; ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Telefono:</label>
                    <input type="text" value="<?php echo $postulacion['telefono'] ?: 'N/A'; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Correo Personal:</label>
                    <input type="text" value="<?php echo $postulacion['correo_personal'] ?: 'N/A'; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Correo Institucional:</label>
                    <input type="text" value="<?php echo $postulacion['correo_institucional'] ?: 'N/A'; ?>" readonly>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Empresa</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Empresa:</label>
                    <input type="text" value="<?php echo $postulacion['nombre_empresa']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Modalidad:</label>
                    <input type="text" value="<?php echo $postulacion['modalidad']; ?>" readonly>
                </div>
            </div>
            <div class="form-group">
                <label>Direccion Completa:</label>
                <input type="text" value="<?php echo $direccion_completa; ?>" readonly>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Descripción:</label>
                    <input type="text" value="<?php echo $postulacion['descripcion'] ?: 'N/A'; ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Dirigido a:</label>
                    <input type="text" value="<?php echo $postulacion['grado_dirigido'] . ' ' . $postulacion['nombre_dirigido']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Puesto:</label>
                    <input type="text" value="<?php echo $postulacion['puesto_dirigido']; ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Contacto:</label>
                    <input type="text" value="<?php echo $postulacion['nombre_contacto']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Puesto del Contacto:</label>
                    <input type="text" value="<?php echo $postulacion['puesto_contacto']; ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Telefono de Empresa:</label>
                    <input type="text" value="<?php echo $postulacion['telefono_empresa']; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Correo de Empresa:</label>
                    <input type="text" value="<?php echo $postulacion['correo_empresa']; ?>" readonly>
                </div>
            </div>
        </div>

        <!-- BOTONES - CORRECCIÓN: Comparación insensible a mayúsculas -->
        <?php if($estado == 'aceptado'): ?>
        <div class="action-buttons">
            <a href="reporte_postulacion.php?id=<?php echo $postulacion['id']; ?>" target="_blank">
                <button class="btn-success">📄 Generar Reporte</button>
            </a>
            <button onclick="window.print()" class="btn-info">🖨️ Imprimir</button>
        </div>
        <?php else: ?>
        <div class="info-card" style="background: #fff3cd; border-left: 4px solid #ffa502;">
            <p style="color: #856404; margin: 0;">
                ⚠️ Los botones de reporte e impresión solo están disponibles cuando la postulación es <strong>ACEPTADA</strong>.
            </p>
            <p style="color: #856404; margin: 10px 0 0 0; font-size: 12px;">
                Estado actual: <?php echo $postulacion['estado']; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>>