<?php
  session_start();

  require 'database.php';
  require 'log.php';

  $user = null;
  if (isset($_SESSION['countdown']) && $_SESSION['countdown'] > time()) {
    header('Location: blocked.php'); // Redirigir a la página de bloqueo si el usuario aún está bloqueado
    exit();
  }//PARA LO DE REDIRECCION DE BLOQUEO BANDAA
  

  if (isset($_SESSION['user_id'])) {
    $records = $conn->prepare('SELECT name, email, password, rol FROM users WHERE id = :id');
    $records->bindParam(':id', $_SESSION['user_id']);
    $records->execute();
    $results = $records->fetch(PDO::FETCH_ASSOC);
    
  
    if (is_array($results) && count($results) > 0) {
      $user = $results;
      

    }
}

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Bienvenido</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
  </head>
  <body>

  <?php require 'partials/header.php' ?>

<?php if (!empty($user)): ?>
  <?php if ($user['rol'] === 'admin'): ?>
    <img src="../php-login-simple-master/no.png" alt="">
    <h1> ¿ Intentando acceder a otra cuenta ?</h1>
    <h3> Lamentablemente no puedes acceder a otra cuenta
        mientras tengas la sesion de admin iniciada, primero cierra sesion.
    </h3>
    <a href="admin.php">Ir al panel de administración</a>
   
  <?php else: ?>
    <h1>Bienvenido <?php echo $user['name']; ?></h1>
    <p>Ha iniciado sesión correctamente.</p>
    <a href="logout.php">Cerrar sesión</a>
  <?php endif; ?>
<?php else: ?>
  <h1>Seleccione una opción</h1>
  <a href="login.php">Ingresar</a> o
  <a href="signup.php">Registrarse</a>
<?php endif; ?>
    
  </body>
</html>