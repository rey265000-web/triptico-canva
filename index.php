<?php
session_start();
include 'php/conexion.php';

$mensaje = "";
$tipo_mensaje = "";

if (isset($_POST['login'])) {
    $matricula = $_POST['matricula'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM administradores WHERE usuario='$matricula' AND password='$password'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['id'] = $row['id'];
        $_SESSION['nombre'] = $row['nombre'];
        $_SESSION['rol'] = 'admin';
        header("Location: admin.php");
        exit();
    }
    
    $sql = "SELECT * FROM alumnos WHERE matricula='$matricula' AND password='$password'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['id'] = $row['id'];
        $_SESSION['nombre_completo'] = $row['nombre_completo'];
        $_SESSION['apellido_completo'] = $row['apellido_completo'];
        $_SESSION['matricula'] = $row['matricula'];
        $_SESSION['carrera'] = $row['carrera'];
        $_SESSION['rol'] = 'alumno';
        header("Location: alumno.php");
        exit();
    }
    
    $mensaje = "Usuario o contraseña incorrectos";
    $tipo_mensaje = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - SGEP</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h2>SGEP</h2>
            <p>Sistema de Gestion de Estadías Profesionales</p>
            
            <?php if($mensaje != ""): ?>
            <div class="<?php echo $tipo_mensaje == 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="text" name="matricula" placeholder="Usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit" name="login">Iniciar Sesion</button>
            </form>
        </div>
    </div>
</body>
</html>