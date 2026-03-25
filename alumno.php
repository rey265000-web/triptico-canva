<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'alumno') {
    header("Location: index.php");
    exit();
}

$nombre_completo = $_SESSION['nombre_completo'] . ' ' . $_SESSION['apellido_completo'];
$matricula = $_SESSION['matricula'];

$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'empresas';

$ya_postulo = mysqli_query($conn, "SELECT id_empresa FROM postulaciones WHERE id_alumno={$_SESSION['id']}");
$empresas_postuladas = [];
while ($row = mysqli_fetch_assoc($ya_postulo)) {
    $empresas_postuladas[] = $row['id_empresa'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Alumno - SGEP</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav>
        <h1>Portal del Alumno</h1>
        <div class="user-info">
            <span>Bienvenido, <?php echo $nombre_completo; ?></span>
            <a href="logout.php"><button class="btn-danger">Cerrar Sesion</button></a>
        </div>
    </nav>

    <div class="container">
        <div class="bienvenida">
            <h2>Bienvenido, <?php echo $nombre_completo; ?></h2>
        </div>

        <div class="menu-horizontal">
            <button onclick="location.href='alumno.php?seccion=empresas'" class="<?php echo $seccion == 'empresas' ? 'activo' : ''; ?>">
                Empresas
            </button>
            <button onclick="location.href='alumno.php?seccion=postulaciones'" class="<?php echo $seccion == 'postulaciones' ? 'activo' : ''; ?>">
                Postulaciones
            </button>
        </div>

        <?php if($seccion == 'empresas'): ?>
        <div class="info-card">
            <h3>Empresas Disponibles</h3>
        </div>

        <div class="grid-empresas">
            <?php
            $sql = "SELECT e.*, GROUP_CONCAT(c.nombre SEPARATOR ', ') as carreras_nombres
                    FROM empresas e
                    LEFT JOIN empresas_carreras ec ON e.id = ec.id_empresa
                    LEFT JOIN carreras c ON ec.id_carrera = c.id
                    GROUP BY e.id
                    ORDER BY e.id DESC";
            $result = mysqli_query($conn, $sql);
            
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $foto = $row['foto_empresa'] ? $row['foto_empresa'] : 'https://via.placeholder.com/400x200?text=Sin+Foto';
                    
                    $esActiva = $row['estatus'] == 'Activa';
                    $hayCupos = $row['cupos'] > 0;
                    $yaPostulo = in_array($row['id'], $empresas_postuladas);
                    
                    $puedePostular = $esActiva && $hayCupos && !$yaPostulo;
                    
                    if ($yaPostulo) {
                        $btnTexto = 'Ya Postulado';
                    } else {
                        $btnTexto = $puedePostular ? 'Postularme' : ($esActiva ? 'Sin Cupos' : 'No Disponible');
                    }
                    
                    $claseExtra = '';
                    if (!$puedePostular) {
                        $claseExtra = ' empresa-inactiva';
                    }
                    
                    echo "<div class='empresa-card{$claseExtra}'>";
                    echo "<div class='empresa-img'><img src='{$foto}' alt='{$row['nombre_empresa']}'></div>";
                    echo "<div class='empresa-body'>";
                    echo "<div class='empresa-titulo'>{$row['nombre_empresa']}</div>";
                    echo "<div class='empresa-info'><strong>Carreras:</strong> " . ($row['carreras_nombres'] ? $row['carreras_nombres'] : 'Todas') . "</div>";
                    echo "<div class='empresa-info'><strong>Ubicacion:</strong> {$row['ubicacion']}</div>";
                    echo "<div class='empresa-info'><strong>Modalidad:</strong> {$row['modalidad']}</div>";
                    echo "<div class='empresa-info'><strong>Puesto:</strong> {$row['puesto_dirigido']}</div>";
                    echo "<div class='empresa-info'><strong>Contacto:</strong> {$row['nombre_contacto']}</div>";
                    echo "<div class='empresa-info'><strong>Telefono:</strong> {$row['telefono_empresa']}</div>";
                    echo "<div class='empresa-info'><strong>Correo:</strong> {$row['correo_empresa']}</div>";
                    echo "<div class='empresa-info'><strong>Cupos:</strong> {$row['cupos']}</div>";
                    echo "<div style='margin-top:15px;'><span class='badge " . ($esActiva ? 'badge-activa' : 'badge-inactiva') . "'>{$row['estatus']}</span></div>";
                    
                    if ($puedePostular) {
                        echo "<a href='postular.php?id_empresa={$row['id']}' style='display:block; margin-top:15px;'>";
                        echo "<button style='width:100%;'>{$btnTexto}</button></a>";
                    } else {
                        echo "<div style='margin-top:15px;'><button style='width:100%;' disabled>{$btnTexto}</button></div>";
                    }
                    
                    echo "</div></div>";
                }
            } else {
                echo "<div class='info-card' style='text-align:center; padding:50px;'>";
                echo "<h3>No hay empresas registradas aun</h3>";
                echo "</div>";
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if($seccion == 'postulaciones'): ?>
        <div class="info-card">
            <h3>Mis Postulaciones</h3>
        </div>

        <?php
        $sql = "SELECT p.*, e.nombre_empresa, c.nombre as carrera_nombre
                FROM postulaciones p 
                INNER JOIN empresas e ON p.id_empresa = e.id 
                LEFT JOIN carreras c ON p.id_carrera_postulacion = c.id
                WHERE p.id_alumno = {$_SESSION['id']} 
                ORDER BY p.fecha_postulacion DESC";
        
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
                
                echo "<div class='info-card'>";
                echo "<h4>{$row['nombre_empresa']} - <span class='badge {$badge}'>{$estadoTexto}</span></h4>";
                echo "<p><strong>Carrera Seleccionada:</strong> " . ($row['carrera_nombre'] ? $row['carrera_nombre'] : 'N/A') . "</p>";
                echo "<p><strong>Fecha:</strong> {$row['fecha_postulacion']}</p>";
                echo "<p><strong>Numero de Oficio:</strong> {$row['numero_oficio']}</p>";
                echo "<a href='ver_postulacion.php?id={$row['id']}'><button class='btn-info'>Ver Detalles</button></a>";
                echo "</div>";
            }
        } else {
            echo "<div class='info-card' style='text-align:center; padding:50px;'>";
            echo "<h3>No tienes postulaciones</h3>";
            echo "</div>";
        }
        ?>
        <?php endif; ?>

    </div>
</body>
</html>