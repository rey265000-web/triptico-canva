<?php
session_start();
include 'php/conexion.php';

if (!isset($_GET['id'])) {
    die("ID de postulación no especificado");
}

$id = $_GET['id'];

$postulacion = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, e.*, c.nombre as carrera_nombre, 
                                                        a.nombre_completo, a.apellido_completo, a.matricula,
                                                        a.nss, a.nivel, a.grupo, a.telefono, a.correo_personal, a.correo_institucional
                                                        FROM postulaciones p 
                                                        INNER JOIN empresas e ON p.id_empresa = e.id 
                                                        LEFT JOIN carreras c ON p.id_carrera_postulacion = c.id
                                                        INNER JOIN alumnos a ON p.id_alumno = a.id 
                                                        WHERE p.id = $id"));

if (!$postulacion) {
    die("Postulación no encontrada");
}

// Verificar que esté aceptada (insensible a mayúsculas)
$estado = strtolower(trim($postulacion['estado']));
if ($estado != 'aceptado') {
    die("Esta postulación no está aceptada. El reporte solo está disponible para postulaciones aceptadas.");
}

$nombre_completo = $postulacion['nombre_completo'] . ' ' . $postulacion['apellido_completo'];
$fecha_reporte = date('d/m/Y H:i:s');

// Construir dirección completa con los nuevos campos
$direccion_completa = $postulacion['calle'];
if ($postulacion['num_ext']) $direccion_completa .= " #" . $postulacion['num_ext'];
if ($postulacion['num_int']) $direccion_completa .= " Int. " . $postulacion['num_int'];
$direccion_completa .= ", " . $postulacion['colonia'];
$direccion_completa .= ", " . $postulacion['ciudad'] . ", " . $postulacion['estado'];
$direccion_completa .= " C.P. " . $postulacion['cp'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Postulación #<?php echo $postulacion['id']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="reporte-no-print">
        <button onclick="window.print()" class="btn-success">🖨️ Imprimir / Guardar como PDF</button>
        <button onclick="window.close()" class="btn-danger">✕ Cerrar</button>
    </div>

    <div class="reporte-header">
        <h1>📋 REPORTE DE POSTULACIÓN ACEPTADA</h1>
        <h2>Sistema de Gestión de Estadías Profesionales (SGEP)</h2>
        <p style="color: #999; margin-top: 10px;">Fecha de emisión: <?php echo $fecha_reporte; ?></p>
    </div>

    <div class="reporte-section">
        <div class="reporte-section-title">📌 INFORMACIÓN GENERAL</div>
        <div class="reporte-section-content">
            <div class="reporte-info-row">
                <div class="reporte-info-label">ID de Postulación:</div>
                <div class="reporte-info-value"><?php echo $postulacion['id']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Número de Oficio:</div>
                <div class="reporte-info-value"><?php echo $postulacion['numero_oficio']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Estado:</div>
                <div class="reporte-info-value"><span class="reporte-badge">ACEPTADO ✓</span></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Fecha de Postulación:</div>
                <div class="reporte-info-value"><?php echo date('d/m/Y H:i', strtotime($postulacion['fecha_postulacion'])); ?></div>
            </div>
        </div>
    </div>

    <div class="reporte-section">
        <div class="reporte-section-title">👨‍ DATOS DEL ALUMNO</div>
        <div class="reporte-section-content">
            <div class="reporte-info-row">
                <div class="reporte-info-label">Nombre Completo:</div>
                <div class="reporte-info-value"><?php echo $nombre_completo; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Matrícula:</div>
                <div class="reporte-info-value"><?php echo $postulacion['matricula']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Carrera:</div>
                <div class="reporte-info-value"><?php echo $postulacion['carrera_nombre']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">NSS:</div>
                <div class="reporte-info-value"><?php echo $postulacion['nss'] ?: 'N/A'; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Nivel:</div>
                <div class="reporte-info-value"><?php echo $postulacion['nivel'] ?: 'N/A'; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Grupo:</div>
                <div class="reporte-info-value"><?php echo $postulacion['grupo'] ?: 'N/A'; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Teléfono:</div>
                <div class="reporte-info-value"><?php echo $postulacion['telefono'] ?: 'N/A'; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Correo Personal:</div>
                <div class="reporte-info-value"><?php echo $postulacion['correo_personal'] ?: 'N/A'; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Correo Institucional:</div>
                <div class="reporte-info-value"><?php echo $postulacion['correo_institucional'] ?: 'N/A'; ?></div>
            </div>
        </div>
    </div>

    <div class="reporte-section">
        <div class="reporte-section-title">🏢 DATOS DE LA EMPRESA</div>
        <div class="reporte-section-content">
            <div class="reporte-info-row">
                <div class="reporte-info-label">Nombre de la Empresa:</div>
                <div class="reporte-info-value"><?php echo $postulacion['nombre_empresa']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Dirección Completa:</div>
                <div class="reporte-info-value"><?php echo $direccion_completa; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Ciudad:</div>
                <div class="reporte-info-value"><?php echo $postulacion['ciudad']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Estado:</div>
                <div class="reporte-info-value"><?php echo $postulacion['estado']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">C.P.:</div>
                <div class="reporte-info-value"><?php echo $postulacion['cp']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Modalidad:</div>
                <div class="reporte-info-value"><?php echo $postulacion['modalidad']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Descripción:</div>
                <div class="reporte-info-value"><?php echo $postulacion['descripcion'] ?: 'N/A'; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Dirigido a:</div>
                <div class="reporte-info-value"><?php echo $postulacion['grado_dirigido'] . ' ' . $postulacion['nombre_dirigido']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Puesto:</div>
                <div class="reporte-info-value"><?php echo $postulacion['puesto_dirigido']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Contacto:</div>
                <div class="reporte-info-value"><?php echo $postulacion['nombre_contacto']; ?> - <?php echo $postulacion['puesto_contacto']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Teléfono de Empresa:</div>
                <div class="reporte-info-value"><?php echo $postulacion['telefono_empresa']; ?></div>
            </div>
            <div class="reporte-info-row">
                <div class="reporte-info-label">Correo de Empresa:</div>
                <div class="reporte-info-value"><?php echo $postulacion['correo_empresa']; ?></div>
            </div>
        </div>
    </div>

    <div class="reporte-firma">
        <div class="reporte-firma-linea">
            <div class="linea">Firma del Alumno</div>
            <div><?php echo $nombre_completo; ?></div>
        </div>
        <div class="reporte-firma-linea">
            <div class="linea">Firma de la Empresa</div>
            <div><?php echo $postulacion['nombre_contacto']; ?></div>
        </div>
    </div>

    <div class="reporte-footer">
        <p>Este documento es un comprobante oficial de aceptación de estadía profesional.</p>
        <p>Generado automáticamente por el Sistema SGEP - <?php echo date('Y'); ?></p>
        <p>ID de Postulación: <?php echo $postulacion['id']; ?> | Fecha: <?php echo $fecha_reporte; ?></p>
    </div>
</body>
</html>