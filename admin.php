<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";
$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'empresas';

// AGREGAR EMPRESA
if (isset($_POST['agregar_empresa'])) {
    $nombre = $_POST['nombre_empresa'];
    $estatus = $_POST['estatus'];
    $ciudad = $_POST['ciudad'];
    $estado = $_POST['estado'];
    $cp = $_POST['cp'];
    $calle = $_POST['calle'];
    $num_ext = $_POST['num_ext'];
    $num_int = $_POST['num_int'];
    $colonia = $_POST['colonia'];
    $modalidad = $_POST['modalidad'];
    $descripcion = $_POST['descripcion'];
    $nombre_dirigido = $_POST['nombre_dirigido'];
    $grado_dirigido = $_POST['grado_dirigido'];
    $puesto_dirigido = $_POST['puesto_dirigido'];
    $nombre_contacto = $_POST['nombre_contacto'];
    $puesto_contacto = $_POST['puesto_contacto'];
    $telefono = $_POST['telefono_empresa'];
    $correo = $_POST['correo_empresa'];
    $carreras = isset($_POST['carreras']) ? $_POST['carreras'] : [];
    $lugares = isset($_POST['lugares']) ? $_POST['lugares'] : [];
    
    $foto = "";
    if (isset($_FILES['foto_empresa']) && $_FILES['foto_empresa']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_empresa']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            move_uploaded_file($_FILES['foto_empresa']['tmp_name'], $upload_path);
            $foto = $upload_path;
        }
    }
    
    $sql = "INSERT INTO empresas (nombre_empresa, estatus, ciudad, estado, cp, calle, num_ext, num_int, colonia, 
            modalidad, descripcion, nombre_dirigido, grado_dirigido, puesto_dirigido, nombre_contacto, 
            puesto_contacto, telefono_empresa, correo_empresa, foto_empresa) 
            VALUES ('$nombre', '$estatus', '$ciudad', '$estado', '$cp', '$calle', '$num_ext', '$num_int', '$colonia',
            '$modalidad', '$descripcion', '$nombre_dirigido', '$grado_dirigido', '$puesto_dirigido', '$nombre_contacto', 
            '$puesto_contacto', '$telefono', '$correo', '$foto')";
    
    if (mysqli_query($conn, $sql)) {
        $id_empresa = mysqli_insert_id($conn);
        
        foreach ($carreras as $id_carrera) {
            $lugares_carrera = isset($lugares[$id_carrera]) ? (int)$lugares[$id_carrera] : 0;
            if ($lugares_carrera >= 0) {
                mysqli_query($conn, "INSERT INTO empresas_carreras_lugares (id_empresa, id_carrera, lugares, lugares_ocupados) 
                                    VALUES ($id_empresa, $id_carrera, $lugares_carrera, 0)");
            }
        }
        
        $mensaje = "Empresa agregada correctamente";
        $tipo_mensaje = "exito";
    }
}

// EDITAR EMPRESA
if (isset($_POST['editar_empresa'])) {
    $id = $_POST['id_empresa'];
    $nombre = $_POST['nombre_empresa'];
    $estatus = $_POST['estatus'];
    $ciudad = $_POST['ciudad'];
    $estado = $_POST['estado'];
    $cp = $_POST['cp'];
    $calle = $_POST['calle'];
    $num_ext = $_POST['num_ext'];
    $num_int = $_POST['num_int'];
    $colonia = $_POST['colonia'];
    $modalidad = $_POST['modalidad'];
    $descripcion = $_POST['descripcion'];
    $carreras = isset($_POST['carreras']) ? $_POST['carreras'] : [];
    $lugares = isset($_POST['lugares']) ? $_POST['lugares'] : [];
    
    $sql = "UPDATE empresas SET nombre_empresa='$nombre', estatus='$estatus', ciudad='$ciudad', estado='$estado', 
            cp='$cp', calle='$calle', num_ext='$num_ext', num_int='$num_int', colonia='$colonia',
            modalidad='$modalidad', descripcion='$descripcion'
            WHERE id=$id";
    
    if (mysqli_query($conn, $sql)) {
        mysqli_query($conn, "DELETE FROM empresas_carreras_lugares WHERE id_empresa=$id");
        
        foreach ($carreras as $id_carrera) {
            $lugares_carrera = isset($lugares[$id_carrera]) ? (int)$lugares[$id_carrera] : 0;
            if ($lugares_carrera >= 0) {
                mysqli_query($conn, "INSERT INTO empresas_carreras_lugares (id_empresa, id_carrera, lugares, lugares_ocupados) 
                                    VALUES ($id, $id_carrera, $lugares_carrera, 0)");
            }
        }
        
        $mensaje = "Empresa actualizada correctamente";
        $tipo_mensaje = "exito";
    }
}

// ELIMINAR EMPRESA
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    mysqli_query($conn, "DELETE FROM empresas WHERE id=$id");
    header("Location: admin.php?seccion=empresas");
}

// PROCESAR ACEPTAR/RECHAZAR POSTULACIÓN
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

$empresa_editar = null;
$carreras_empresa = [];
if (isset($_GET['editar'])) {
    $id_editar = $_GET['editar'];
    $result = mysqli_query($conn, "SELECT * FROM empresas WHERE id=$id_editar");
    $empresa_editar = mysqli_fetch_assoc($result);
    
    $result = mysqli_query($conn, "SELECT * FROM empresas_carreras_lugares WHERE id_empresa=$id_editar");
    while ($row = mysqli_fetch_assoc($result)) {
        $carreras_empresa[$row['id_carrera']] = $row['lugares'];
    }
}

$carreras_disponibles = mysqli_query($conn, "SELECT * FROM carreras ORDER BY id");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Direccion - SGEP</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav>
        <h1>Direccion Academica</h1>
        <div class="user-info">
            <span>Bienvenido, <?php echo $_SESSION['nombre']; ?></span>
            <a href="logout.php"><button class="btn-danger">Cerrar Sesion</button></a>
        </div>
    </nav>

    <div class="container">
        <div class="bienvenida">
            <h2>Bienvenido, <?php echo $_SESSION['nombre']; ?></h2>
        </div>

        <div class="menu-horizontal">
            <button onclick="location.href='admin.php?seccion=empresas'" class="<?php echo $seccion == 'empresas' ? 'activo' : ''; ?>">
                Empresas
            </button>
            <button onclick="location.href='admin.php?seccion=postulaciones'" class="<?php echo $seccion == 'postulaciones' ? 'activo' : ''; ?>">
                Postulaciones
            </button>
        </div>

        <?php if($mensaje != ""): ?>
        <div class="<?php echo $tipo_mensaje == 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <!-- SECCIÓN EMPRESAS -->
        <?php if($seccion == 'empresas'): ?>
        <div class="info-card">
            <h3>Gestion de Empresas</h3>
        </div>

        <div class="card">
            <button onclick="document.getElementById('formEmpresa').style.display='block'" style="width:100%; font-size:18px; padding:20px;">
                AGREGAR EMPRESAS
            </button>
        </div>

        <div class="card" id="formEmpresa" style="display:<?php echo $empresa_editar ? 'block' : 'none'; ?>;">
            <h3><?php echo $empresa_editar ? 'Editar Empresa' : 'Registrar Nueva Empresa'; ?></h3>
            <form method="POST" enctype="multipart/form-data">
                
                <?php if($empresa_editar): ?>
                <input type="hidden" name="id_empresa" value="<?php echo $empresa_editar['id']; ?>">
                <?php endif; ?>
                
                <div class="form-section">
                    <h3>Informacion General</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre de la Empresa *</label>
                            <input type="text" name="nombre_empresa" value="<?php echo $empresa_editar ? $empresa_editar['nombre_empresa'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Estatus *</label>
                            <select name="estatus" required>
                                <option value="Activa" <?php echo ($empresa_editar && $empresa_editar['estatus'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                                <option value="Inactiva" <?php echo ($empresa_editar && $empresa_editar['estatus'] == 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Direccion</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Calle *</label>
                            <input type="text" name="calle" value="<?php echo $empresa_editar ? $empresa_editar['calle'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Numero Exterior *</label>
                            <input type="text" name="num_ext" value="<?php echo $empresa_editar ? $empresa_editar['num_ext'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Numero Interior</label>
                            <input type="text" name="num_int" value="<?php echo $empresa_editar ? $empresa_editar['num_int'] : ''; ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Colonia *</label>
                            <input type="text" name="colonia" value="<?php echo $empresa_editar ? $empresa_editar['colonia'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Ciudad*</label>
                            <input type="text" name="ciudad" value="<?php echo $empresa_editar ? $empresa_editar['ciudad'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Estado *</label>
                            <input type="text" name="cp" value="<?php echo $empresa_editar ? $empresa_editar['cp'] : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Informacion Adicional</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Modalidad *</label>
                            <select name="modalidad" required>
                                <option value="">Seleccionar modalidad</option>
                                <option value="Presencial" <?php echo ($empresa_editar && $empresa_editar['modalidad'] == 'Presencial') ? 'selected' : ''; ?>>Presencial</option>
                                <option value="Mixta" <?php echo ($empresa_editar && $empresa_editar['modalidad'] == 'Mixta') ? 'selected' : ''; ?>>Mixta</option>
                                <option value="En linea" <?php echo ($empresa_editar && $empresa_editar['modalidad'] == 'En linea') ? 'selected' : ''; ?>>En linea</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estado de la Empresa *</label>
                            <input type="text" name="estado" value="<?php echo $empresa_editar ? $empresa_editar['estado'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Descripcion del Proyecto</label>
                            <textarea name="descripcion" rows="3"><?php echo $empresa_editar ? $empresa_editar['descripcion'] : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Carreras y Lugares</h3>
                    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Selecciona las carreras y asigna lugares disponibles para cada una</p>
                    <div class="carreras-checkbox">
                        <?php 
                        mysqli_data_seek($carreras_disponibles, 0);
                        while ($carrera = mysqli_fetch_assoc($carreras_disponibles)): 
                            $lugares_actuales = isset($carreras_empresa[$carrera['id']]) ? $carreras_empresa[$carrera['id']] : 0;
                            $checked = $lugares_actuales > 0 ? 'checked' : '';
                        ?>
                        <div class="lugares-carrera">
                            <label>
                                <input type="checkbox" name="carreras[]" value="<?php echo $carrera['id']; ?>" <?php echo $checked; ?> onchange="toggleLugares(this, <?php echo $carrera['id']; ?>)">
                                <?php echo $carrera['nombre']; ?>
                            </label>
                            <div id="lugares-<?php echo $carrera['id']; ?>" style="<?php echo $lugares_actuales > 0 ? 'display:block;' : 'display:none;'; ?> margin-top: 10px;">
                                <div class="lugares-input">
                                    <label style="margin:0;">Lugares:</label>
                                    <input type="number" name="lugares[<?php echo $carrera['id']; ?>]" value="<?php echo $lugares_actuales; ?>" min="0" max="50">
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Datos para Carta de Presentacion</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre de la Persona *</label>
                            <input type="text" name="nombre_dirigido" value="<?php echo $empresa_editar ? $empresa_editar['nombre_dirigido'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Grado de Estudios *</label>
                            <select name="grado_dirigido" required>
                                <option value="LIC." <?php echo ($empresa_editar && $empresa_editar['grado_dirigido'] == 'LIC.') ? 'selected' : ''; ?>>LIC.</option>
                                <option value="ING." <?php echo ($empresa_editar && $empresa_editar['grado_dirigido'] == 'ING.') ? 'selected' : ''; ?>>ING.</option>
                                <option value="C." <?php echo ($empresa_editar && $empresa_editar['grado_dirigido'] == 'C.') ? 'selected' : ''; ?>>C.</option>
                                <option value="DR." <?php echo ($empresa_editar && $empresa_editar['grado_dirigido'] == 'DR.') ? 'selected' : ''; ?>>DR.</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Puesto de la Persona *</label>
                            <input type="text" name="puesto_dirigido" value="<?php echo $empresa_editar ? $empresa_editar['puesto_dirigido'] : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Datos de Contacto</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Contacto *</label>
                            <input type="text" name="nombre_contacto" value="<?php echo $empresa_editar ? $empresa_editar['nombre_contacto'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Puesto del Contacto *</label>
                            <input type="text" name="puesto_contacto" value="<?php echo $empresa_editar ? $empresa_editar['puesto_contacto'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Telefono de Empresa *</label>
                            <input type="text" name="telefono_empresa" value="<?php echo $empresa_editar ? $empresa_editar['telefono_empresa'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Correo de Empresa *</label>
                            <input type="email" name="correo_empresa" value="<?php echo $empresa_editar ? $empresa_editar['correo_empresa'] : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <?php if(!$empresa_editar): ?>
                <div class="form-group">
                    <label>Foto de la Empresa</label>
                    <input type="file" name="foto_empresa" accept="image/*">
                </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <button type="submit" name="<?php echo $empresa_editar ? 'editar_empresa' : 'agregar_empresa'; ?>" class="btn-success">
                        <?php echo $empresa_editar ? 'Actualizar' : 'Guardar'; ?>
                    </button>
                    <button type="button" onclick="document.getElementById('formEmpresa').style.display='none'; window.location='admin.php?seccion=empresas'" class="btn-danger">Cancelar</button>
                </div>
            </form>
        </div>

        <div class="info-card">
            <h3>Empresas Registradas</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empresa</th>
                    <th>Direccion</th>
                    <th>Modalidad</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM empresas ORDER BY id DESC");
                while ($row = mysqli_fetch_assoc($result)) {
                    $badge = $row['estatus'] == 'Activa' ? 'badge-activa' : 'badge-inactiva';
                    
                    $direccion = $row['calle'];
                    if ($row['num_ext']) $direccion .= " #" . $row['num_ext'];
                    if ($row['num_int']) $direccion .= " Int. " . $row['num_int'];
                    $direccion .= ", " . $row['colonia'];
                    $direccion .= ", " . $row['ciudad'];
                    $direccion .= ", " . $row['estado'];
                    $direccion .= " C.P. " . $row['cp'];
                    
                    echo "<tr>";
                    echo "<td>{$row['id']}</td>";
                    echo "<td><strong>{$row['nombre_empresa']}</strong></td>";
                    echo "<td style='font-size:12px;'>{$direccion}</td>";
                    echo "<td>{$row['modalidad']}</td>";
                    echo "<td><span class='badge {$badge}'>{$row['estatus']}</span></td>";
                    echo "<td>";
                    echo "<a href='admin.php?seccion=empresas&editar={$row['id']}' class='btn-warning' style='color:white; padding:8px 15px; text-decoration:none; border-radius:5px; font-size:13px; margin-right:5px;'>Editar</a>";
                    echo "<a href='admin.php?seccion=empresas&eliminar={$row['id']}' class='btn-danger' style='color:white; padding:8px 15px; text-decoration:none; border-radius:5px; font-size:13px;'>Eliminar</a>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- SECCIÓN POSTULACIONES -->
        <?php if($seccion == 'postulaciones'): ?>
        <div class="info-card">
            <h3>Postulaciones Pendientes</h3>
        </div>

        <?php
        $result = mysqli_query($conn, "SELECT p.*, e.nombre_empresa, c.nombre as carrera_nombre, 
                                               a.nombre_completo, a.matricula
                                        FROM postulaciones p 
                                        INNER JOIN empresas e ON p.id_empresa = e.id 
                                        LEFT JOIN carreras c ON p.id_carrera_postulacion = c.id 
                                        INNER JOIN alumnos a ON p.id_alumno = a.id 
                                        WHERE p.estado = 'pendiente'
                                        ORDER BY p.fecha_postulacion DESC");
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<div class='card'>";
                echo "<h4>{$row['nombre_empresa']} - <span class='badge badge-pendiente'>Pendiente</span></h4>";
                echo "<p><strong>Alumno:</strong> {$row['nombre_completo']} ({$row['matricula']})</p>";
                echo "<p><strong>Carrera:</strong> " . ($row['carrera_nombre'] ? $row['carrera_nombre'] : 'N/A') . "</p>";
                echo "<p><strong>Fecha:</strong> {$row['fecha_postulacion']}</p>";
                echo "<p><strong>Oficio:</strong> {$row['numero_oficio']}</p>";
                
                echo "<form method='POST' style='margin-top:15px;'>";
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
                
                echo "<a href='ver_postulacion.php?id={$row['id']}'><button class='btn-info' style='margin-top:10px;'>Ver Detalles</button></a>";
                echo "</div>";
            }
        } else {
            echo "<div class='card' style='text-align:center; padding:50px;'>";
            echo "<h3>No hay postulaciones pendientes</h3>";
            echo "</div>";
        }
        ?>
        <?php endif; ?>

    </div>

    <script>
        function toggleLugares(checkbox, idCarrera) {
            var divLugares = document.getElementById('lugares-' + idCarrera);
            var inputLugares = divLugares.querySelector('input[type="number"]');
            
            if (checkbox.checked) {
                divLugares.style.display = 'block';
                inputLugares.value = 5;
            } else {
                divLugares.style.display = 'none';
                inputLugares.value = 0;
            }
        }
    </script>
</body>
</html>