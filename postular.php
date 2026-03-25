<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'alumno') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id_empresa'])) {
    header("Location: alumno.php");
    exit();
}

$id_empresa = $_GET['id_empresa'];

// Verificar si ya se postuló a esta empresa
$check = mysqli_query($conn, "SELECT * FROM postulaciones WHERE id_alumno={$_SESSION['id']} AND id_empresa=$id_empresa");
if (mysqli_num_rows($check) > 0) {
    header("Location: alumno.php");
    exit();
}

$empresa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM empresas WHERE id=$id_empresa"));

if (!$empresa || $empresa['estatus'] != 'Activa' || $empresa['cupos'] <= 0) {
    header("Location: alumno.php");
    exit();
}

// Obtener carreras de la empresa
$carreras_empresa = mysqli_query($conn, "SELECT ec.id_carrera, c.nombre FROM empresas_carreras ec 
                                          INNER JOIN carreras c ON ec.id_carrera = c.id 
                                          WHERE ec.id_empresa = $id_empresa");
$carreras_disponibles = [];
while ($c = mysqli_fetch_assoc($carreras_empresa)) {
    $carreras_disponibles[] = $c;
}

$num_carreras = count($carreras_disponibles);

if (isset($_POST['postular'])) {
    $numero_oficio = $_POST['numero_oficio'];
    $nss = $_POST['nss'];
    $nivel = $_POST['nivel'];
    $grupo = $_POST['grupo'];
    $telefono = $_POST['telefono'];
    $correo_personal = $_POST['correo_personal'];
    $correo_institucional = $_POST['correo_institucional'];
    $id_carrera_postulacion = $_POST['id_carrera_postulacion'];
    
    $sql = "INSERT INTO postulaciones (id_alumno, id_empresa, id_carrera_postulacion, numero_oficio, nss, nivel, grupo, telefono, correo_personal, correo_institucional) 
            VALUES ({$_SESSION['id']}, $id_empresa, $id_carrera_postulacion, '$numero_oficio', '$nss', '$nivel', '$grupo', '$telefono', '$correo_personal', '$correo_institucional')";
    
    if (mysqli_query($conn, $sql)) {
        mysqli_query($conn, "UPDATE empresas SET cupos = cupos - 1 WHERE id = $id_empresa");
        header("Location: alumno.php?seccion=postulaciones");
        exit();
    }
}

$nombre_completo = $_SESSION['nombre_completo'] . ' ' . $_SESSION['apellido_completo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Postular - SGEP</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav>
        <h1>Formulario de Postulacion</h1>
        <a href="alumno.php?seccion=empresas"><button class="btn-danger">Volver</button></a>
    </nav>

    <div class="container">
        <form method="POST">
            <div class="card">
                <h3>Datos del Alumno</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" value="<?php echo $nombre_completo; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Matricula</label>
                        <input type="text" value="<?php echo $_SESSION['matricula']; ?>" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Numero de Oficio *</label>
                        <input type="text" name="numero_oficio" required>
                    </div>
                    <div class="form-group">
                        <label>NSS *</label>
                        <input type="text" name="nss" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nivel *</label>
                        <select name="nivel" required>
                            <option value="">Seleccionar</option>
                            <option value="Licenciatura">Licenciatura</option>
                            <option value="Ingenieria">Ingenieria</option>
                            <option value="Tecnico">Tecnico</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Grupo *</label>
                        <input type="text" name="grupo" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Telefono *</label>
                        <input type="text" name="telefono" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Personal *</label>
                        <input type="email" name="correo_personal" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Institucional *</label>
                        <input type="email" name="correo_institucional" required>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>Datos de la Empresa</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Empresa</label>
                        <input type="text" value="<?php echo $empresa['nombre_empresa']; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Modalidad</label>
                        <input type="text" value="<?php echo $empresa['modalidad']; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Ubicacion</label>
                        <input type="text" value="<?php echo $empresa['ubicacion']; ?>" readonly>
                    </div>
                </div>
                
                <?php if($num_carreras > 1): ?>
                <div class="form-group">
                    <label>Selecciona una Carrera *</label>
                    <select name="id_carrera_postulacion" required>
                        <option value="">Seleccionar carrera</option>
                        <?php foreach($carreras_disponibles as $carrera): ?>
                        <option value="<?php echo $carrera['id_carrera']; ?>"><?php echo $carrera['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>Carrera</label>
                    <input type="text" value="<?php echo $carreras_disponibles[0]['nombre']; ?>" readonly>
                    <input type="hidden" name="id_carrera_postulacion" value="<?php echo $carreras_disponibles[0]['id_carrera']; ?>">
                </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <button type="submit" name="postular" class="btn-success">Enviar Postulacion</button>
                <a href="alumno.php?seccion=empresas"><button type="button" class="btn-danger">Cancelar</button></a>
            </div>
        </form>
    </div>
</body>
</html>