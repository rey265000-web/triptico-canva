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

if (isset($_POST['agregar_empresa'])) {
    $nombre = $_POST['nombre_empresa'];
    $estatus = $_POST['estatus'];
    $ubicacion = $_POST['ubicacion'];
    $modalidad = $_POST['modalidad'];
    $cupos = $_POST['cupos'];
    $descripcion = $_POST['descripcion'];
    $nombre_dirigido = $_POST['nombre_dirigido'];
    $grado_dirigido = $_POST['grado_dirigido'];
    $puesto_dirigido = $_POST['puesto_dirigido'];
    $nombre_contacto = $_POST['nombre_contacto'];
    $puesto_contacto = $_POST['puesto_contacto'];
    $telefono = $_POST['telefono_empresa'];
    $correo = $_POST['correo_empresa'];
    $carreras = isset($_POST['carreras']) ? $_POST['carreras'] : [];
    
    if ($estatus == 'Inactiva') {
        $cupos = 0;
    }
    
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
    
    $sql = "INSERT INTO empresas (nombre_empresa, estatus, ubicacion, modalidad, cupos, descripcion, 
            nombre_dirigido, grado_dirigido, puesto_dirigido, nombre_contacto, puesto_contacto, 
            telefono_empresa, correo_empresa, foto_empresa) 
            VALUES ('$nombre', '$estatus', '$ubicacion', '$modalidad', '$cupos', '$descripcion',
            '$nombre_dirigido', '$grado_dirigido', '$puesto_dirigido', '$nombre_contacto', '$puesto_contacto',
            '$telefono', '$correo', '$foto')";
    
    if (mysqli_query($conn, $sql)) {
        $id_empresa = mysqli_insert_id($conn);
        
        // Insertar carreras seleccionadas
        foreach ($carreras as $id_carrera) {
            mysqli_query($conn, "INSERT INTO empresas_carreras (id_empresa, id_carrera) VALUES ($id_empresa, $id_carrera)");
        }
        
        $mensaje = "Empresa agregada correctamente";
        $tipo_mensaje = "exito";
    }
}

if (isset($_POST['editar_empresa'])) {
    $id = $_POST['id_empresa'];
    $nombre = $_POST['nombre_empresa'];
    $estatus = $_POST['estatus'];
    $ubicacion = $_POST['ubicacion'];
    $modalidad = $_POST['modalidad'];
    $cupos = $_POST['cupos'];
    $descripcion = $_POST['descripcion'];
    $carreras = isset($_POST['carreras']) ? $_POST['carreras'] : [];
    
    if ($estatus == 'Inactiva') {
        $cupos = 0;
    }
    
    $sql = "UPDATE empresas SET nombre_empresa='$nombre', estatus='$estatus', ubicacion='$ubicacion', 
            modalidad='$modalidad', cupos='$cupos', descripcion='$descripcion'
            WHERE id=$id";
    
    if (mysqli_query($conn, $sql)) {
        // Eliminar carreras anteriores
        mysqli_query($conn, "DELETE FROM empresas_carreras WHERE id_empresa=$id");
        
        // Insertar nuevas carreras
        foreach ($carreras as $id_carrera) {
            mysqli_query($conn, "INSERT INTO empresas_carreras (id_empresa, id_carrera) VALUES ($id, $id_carrera)");
        }
        
        $mensaje = "Empresa actualizada correctamente";
        $tipo_mensaje = "exito";
    }
}

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    mysqli_query($conn, "DELETE FROM empresas WHERE id=$id");
    header("Location: admin.php?seccion=empresas");
}

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

$empresa_editar = null;
$carreras_empresa = [];
if (isset($_GET['editar'])) {
    $id_editar = $_GET['editar'];
    $result = mysqli_query($conn, "SELECT * FROM empresas WHERE id=$id_editar");
    $empresa_editar = mysqli_fetch_assoc($result);
    
    $result = mysqli_query($conn, "SELECT id_carrera FROM empresas_carreras WHERE id_empresa=$id_editar");
    while ($row = mysqli_fetch_assoc($result)) {
        $carreras_empresa[] = $row['id_carrera'];
    }
}

// Obtener todas las carreras
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
                            <select name="estatus" id="estatus" required onchange="cambiarCupos()">
                                <option value="Activa" <?php echo ($empresa_editar && $empresa_editar['estatus'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                                <option value="Inactiva" <?php echo ($empresa_editar && $empresa_editar['estatus'] == 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ubicacion *</label>
                            <input type="text" name="ubicacion" value="<?php echo $empresa_editar ? $empresa_editar['ubicacion'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Carreras *</label>
                            <div class="carreras-checkbox">
                                <?php 
                                mysqli_data_seek($carreras_disponibles, 0);
                                while ($carrera = mysqli_fetch_assoc($carreras_disponibles)): 
                                    $checked = in_array($carrera['id'], $carreras_empresa) ? 'checked' : '';
                                ?>
                                <label>
                                    <input type="checkbox" name="carreras[]" value="<?php echo $carrera['id']; ?>" <?php echo $checked; ?>>
                                    <?php echo $carrera['nombre']; ?>
                                </label>
                                <?php endwhile; ?>
                            </div>
                        </div>
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
                            <label>Cupos Disponibles *</label>
                            <input type="number" name="cupos" id="cupos" min="0" value="<?php echo $empresa_editar ? $empresa_editar['cupos'] : '5'; ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripcion del Proyecto</label>
                        <textarea name="descripcion" rows="3"><?php echo $empresa_editar ? $empresa_editar['descripcion'] : ''; ?></textarea>
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
                            <label>Telefono del Contacto *</label>
                            <input type="text" name="telefono_empresa" value="<?php echo $empresa_editar ? $empresa_editar['telefono_empresa'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Correo del Contacto *</label>
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
                    <th>Carreras</th>
                    <th>Modalidad</th>
                    <th>Cupos</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM empresas ORDER BY id DESC");
                while ($row = mysqli_fetch_assoc($result)) {
                    $badge = $row['estatus'] == 'Activa' ? 'badge-activa' : 'badge-inactiva';
                    $cuposColor = $row['cupos'] > 0 ? '#2ed573' : '#ff4757';
                    
                    // Obtener carreras de la empresa
                    $carreras_text = "";
                    $result_carreras = mysqli_query($conn, "SELECT c.nombre FROM empresas_carreras ec 
                                                            INNER JOIN carreras c ON ec.id_carrera = c.id 
                                                            WHERE ec.id_empresa = {$row['id']}");
                    while ($c = mysqli_fetch_assoc($result_carreras)) {
                        $carreras_text .= $c['nombre'] . ", ";
                    }
                    $carreras_text = rtrim($carreras_text, ", ");
                    
                    echo "<tr>";
                    echo "<td>{$row['id']}</td>";
                    echo "<td><strong>{$row['nombre_empresa']}</strong></td>";
                    echo "<td>{$carreras_text}</td>";
                    echo "<td>{$row['modalidad']}</td>";
                    echo "<td style='color:{$cuposColor}; font-weight:bold;'>{$row['cupos']}</td>";
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

        <?php if($seccion == 'postulaciones'): ?>
        <div class="info-card">
            <h3>Postulaciones de Alumnos</h3>
        </div>

        

<?php
$result = mysqli_query($conn, "SELECT p.*, e.nombre_empresa, a.nombre_completo, a.matricula, c.nombre as carrera_nombre
                               FROM postulaciones p 
                               INNER JOIN empresas e ON p.id_empresa = e.id 
                               INNER JOIN alumnos a ON p.id_alumno = a.id 
                               LEFT JOIN carreras c ON p.id_carrera_postulacion = c.id
                               ORDER BY p.id DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $badge = 'badge-' . $row['estado'];
    echo "<div class='info-card'>";
    echo "<h4>{$row['nombre_empresa']} - <span class='badge $badge'>{$row['estado']}</span></h4>";
    echo "<p><strong>Alumno:</strong> {$row['nombre_completo']} ({$row['matricula']})</p>";
    echo "<p><strong>Carrera Seleccionada:</strong> " . ($row['carrera_nombre'] ? $row['carrera_nombre'] : 'N/A') . "</p>";
    echo "<p><strong>Fecha:</strong> {$row['fecha_postulacion']}</p>";
    echo "<p><strong>Oficio:</strong> {$row['numero_oficio']}</p>";
    
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
    echo "</div>";
}
?>
        <?php endif; ?>

    </div>

    <script>
        function cambiarCupos() {
            var estatus = document.getElementById('estatus').value;
            var cuposInput = document.getElementById('cupos');
            
            if (estatus === 'Inactiva') {
                cuposInput.value = 0;
            } else {
                if (cuposInput.value == 0) {
                    cuposInput.value = 5;
                }
            }
        }
    </script>
</body>
</html>