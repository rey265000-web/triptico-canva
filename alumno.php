<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'alumno') {
    header("Location: index.php");
    exit();
}

$nombre_completo = $_SESSION['nombre_completo'] . ' ' . $_SESSION['apellido_completo'];
$matricula = $_SESSION['matricula'];
$id_alumno = $_SESSION['id'];
$id_carrera_alumno = isset($_SESSION['id_carrera']) ? $_SESSION['id_carrera'] : null;

if (!$id_carrera_alumno) {
    header("Location: index.php");
    exit();
}

$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'empresas';

// Obtener TODAS las postulaciones del alumno (cualquier estado)
$postulaciones = mysqli_query($conn, "SELECT id_empresa, estado FROM postulaciones WHERE id_alumno=$id_alumno");
$empresas_postuladas = [];
$tiene_postulacion = false;
while ($row = mysqli_fetch_assoc($postulaciones)) {
    $empresas_postuladas[] = $row['id_empresa'];
    $tiene_postulacion = true;
}

// Obtener carreras bloqueadas para este alumno
$carreras_bloqueadas = [];
$result_bloqueadas = mysqli_query($conn, "SELECT id_empresa, id_carrera FROM empresas_carreras_bloqueadas WHERE id_alumno=$id_alumno");
while ($row = mysqli_fetch_assoc($result_bloqueadas)) {
    if (!isset($carreras_bloqueadas[$row['id_empresa']])) {
        $carreras_bloqueadas[$row['id_empresa']] = [];
    }
    $carreras_bloqueadas[$row['id_empresa']][] = $row['id_carrera'];
}

$notificaciones_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM notificaciones WHERE id_alumno=$id_alumno AND leido=0"))['total'];
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
            <div class="notificaciones-btn" onclick="toggleNotificaciones()">
                🔔
                <?php if($notificaciones_count > 0): ?>
                <span class="notificaciones-badge"><?php echo $notificaciones_count; ?></span>
                <?php endif; ?>
                <div class="notificaciones-panel" id="notificacionesPanel">
                    <h4 style="padding:15px; border-bottom:1px solid #eee;">Notificaciones</h4>
                    <?php
                    $notificaciones = mysqli_query($conn, "SELECT * FROM notificaciones WHERE id_alumno=$id_alumno ORDER BY fecha DESC");
                    while ($notif = mysqli_fetch_assoc($notificaciones)): 
                        mysqli_query($conn, "UPDATE notificaciones SET leido=1 WHERE id={$notif['id']}");
                    ?>
                    <div class="notificacion-item <?php echo $notif['tipo']; ?>">
                        <h4><?php echo $notif['tipo'] == 'aceptado' ? '✅' : '❌'; ?> <?php echo $notif['tipo'] == 'aceptado' ? 'Aceptado' : 'Rechazado'; ?></h4>
                        <p><?php echo $notif['mensaje']; ?></p>
                        <small><?php echo $notif['fecha']; ?></small>
                    </div>
                    <?php endwhile; ?>
                    <?php if($notificaciones_count == 0): ?>
                    <p style="padding:15px; color:#999;">No hay notificaciones</p>
                    <?php endif; ?>
                </div>
            </div>
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
            <?php if($tiene_postulacion): ?>
            <p style="color: #ffa502; margin-top: 10px;">⚠️ Ya te has postulado a una empresa. No puedes postularte a más empresas hasta que tu postulación sea aceptada o rechazada.</p>
            <?php endif; ?>
        </div>

        <div class="grid-empresas">
            <?php
            $sql = "SELECT e.* FROM empresas e WHERE e.estatus = 'Activa' ORDER BY e.id DESC";
            $result = mysqli_query($conn, $sql);
            
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $foto = $row['foto_empresa'] ? $row['foto_empresa'] : 'https://via.placeholder.com/400x200?text=Sin+Foto';
                    
                    $carreras_info = mysqli_query($conn, "SELECT ecl.id_carrera, c.nombre, ecl.lugares, ecl.lugares_ocupados,
                                                          (ecl.lugares - ecl.lugares_ocupados) as lugares_disponibles
                                                          FROM empresas_carreras_lugares ecl
                                                          INNER JOIN carreras c ON ecl.id_carrera = c.id
                                                          WHERE ecl.id_empresa = {$row['id']}");
                    
                    $carreras_array = [];
                    while ($c = mysqli_fetch_assoc($carreras_info)) {
                        $carreras_array[] = $c;
                    }
                    
                    $yaPostulo = in_array($row['id'], $empresas_postuladas);
                    $estaBloqueada = isset($carreras_bloqueadas[$row['id']]);
                    
                    // SI YA SE POSTULÓ A CUALQUIER EMPRESA, BLOQUEAR TODAS LAS DEMÁS
                    if ($tiene_postulacion && !$yaPostulo) {
                        $puedePostular = false;
                    } else {
                        $carreras_disponibles_para_alumno = [];
                        foreach ($carreras_array as $carrera) {
                            $esta_bloqueada = $estaBloqueada && in_array($carrera['id_carrera'], $carreras_bloqueadas[$row['id']]);
                            $tiene_lugares = $carrera['lugares_disponibles'] > 0;
                            
                            if (!$esta_bloqueada && $tiene_lugares) {
                                $carreras_disponibles_para_alumno[] = $carrera;
                            }
                        }
                        $puedePostular = !$yaPostulo && count($carreras_disponibles_para_alumno) > 0;
                    }
                    
                    if ($yaPostulo) {
                        $btnTexto = 'Ya Postulado';
                    } elseif ($tiene_postulacion) {
                        $btnTexto = 'Bloqueado';
                    } elseif (count($carreras_disponibles_para_alumno) == 0) {
                        $btnTexto = 'Sin Carreras Disponibles';
                    } else {
                        $btnTexto = 'Postularme';
                    }
                    
                    $claseExtra = '';
                    if (!$puedePostular) {
                        $claseExtra = ' empresa-inactiva';
                    }
                    
                    $carreras_texto = "";
                    foreach ($carreras_array as $carrera) {
                        $carreras_texto .= $carrera['nombre'] . " (" . $carrera['lugares_disponibles'] . "), ";
                    }
                    $carreras_texto = rtrim($carreras_texto, ", ");
                    
                    $direccion_completa = $row['calle'];
                    if ($row['num_ext']) $direccion_completa .= " #" . $row['num_ext'];
                    if ($row['num_int']) $direccion_completa .= " Int. " . $row['num_int'];
                    $direccion_completa .= ", " . $row['colonia'];
                    $direccion_completa .= ", " . $row['ciudad'] . ", " . $row['estado'];
                    
                    echo "<div class='empresa-card{$claseExtra}'>";
                    echo "<div class='empresa-img'><img src='{$foto}' alt='{$row['nombre_empresa']}'></div>";
                    echo "<div class='empresa-body'>";
                    echo "<div class='empresa-titulo'>{$row['nombre_empresa']}</div>";
                    echo "<div class='empresa-info'><strong>Carreras:</strong> " . ($carreras_texto ? $carreras_texto : 'N/A') . "</div>";
                    echo "<div class='empresa-info'><strong>Direccion:</strong> {$direccion_completa}</div>";
                    echo "<div class='empresa-info'><strong>Modalidad:</strong> {$row['modalidad']}</div>";
                    echo "<div class='empresa-info'><strong>Puesto:</strong> {$row['puesto_dirigido']}</div>";
                    echo "<div class='empresa-info'><strong>Contacto:</strong> {$row['nombre_contacto']}</div>";
                    echo "<div class='empresa-info'><strong>Telefono:</strong> {$row['telefono_empresa']}</div>";
                    echo "<div class='empresa-info'><strong>Correo:</strong> {$row['correo_empresa']}</div>";
                    echo "<div style='margin-top:15px;'><span class='badge badge-activa'>Activa</span></div>";
                    
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
                WHERE p.id_alumno = $id_alumno
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

    <script>
        function toggleNotificaciones() {
            var panel = document.getElementById('notificacionesPanel');
            panel.classList.toggle('activo');
        }
    </script>
</body>
</html>