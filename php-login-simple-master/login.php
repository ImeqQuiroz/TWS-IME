<?php
session_start();

if (isset($_SESSION['user_id'])) {
  header('Location: /php-login');
  exit();
}
if (isset($_SESSION['countdown']) && $_SESSION['countdown'] > time()) {
  header('Location: blocked.php'); // Redirigir a la página de bloqueo si el usuario aún está bloqueado
  exit();
}

require_once 'database.php';
require_once 'log.php';

$max_attempts = 3; // número máximo de intentos fallidos
$message = '';

// Verificar si se ha enviado el formulario
if (!empty($_POST['email']) && !empty($_POST['password'])) {
  $email = $_POST['email'];
  $recaptcha_response = $_POST['g-recaptcha-response'];

  // Verificar reCAPTCHA
  $recaptcha_secret = '6LcbcmEmAAAAAJ5nKaq6vsftY0IhNKxVmXDHKwXF'; // Reemplaza con tu clave secreta (Secret Key) de reCAPTCHA

  $url = 'https://www.google.com/recaptcha/api/siteverify';
  $data = array(
    'secret' => $recaptcha_secret,
    'response' => $recaptcha_response
  );

  $options = array(
    'http' => array(
      'header' => "Content-type: application/x-www-form-urlencoded\r\n",
      'method' => 'POST',
      'content' => http_build_query($data)
    )
  );

  $context = stream_context_create($options);
  $recaptcha_result = file_get_contents($url, false, $context);
  $recaptcha_response_data = json_decode($recaptcha_result);

  if ($recaptcha_response_data->success) {
    // El reCAPTCHA es válido, continuar con la validación del inicio de sesión
    // Verificar si el usuario existe en la base de datos
    $check_user = $conn->prepare('SELECT id, email, password, attempts, last_attempt, rol FROM users WHERE email = :email');
    $check_user->bindParam(':email', $email);
    $check_user->execute();
    $results = $check_user->fetch(PDO::FETCH_ASSOC);

    if ($results && password_verify($_POST['password'], $results['password'])) {
      // El inicio de sesión es correcto, resetear los contadores de intentos fallidos
      $update_attempts = $conn->prepare('UPDATE users SET attempts=0, last_login=CURRENT_TIMESTAMP WHERE email = :email');
      $update_attempts->bindParam(':email', $results['email']);
      $update_attempts->execute();

      $_SESSION['user_id'] = $results['id'];
      $_SESSION['user']['rol'] = $results['rol']; // Establecer el rol del usuario en la sesión

      if ($results['rol'] === 'admin') {
        header("Location: admin.php");
        exit();
      } elseif ($results['rol'] === 'user') {
        header("Location: index.php");
        exit();
      } else {
        // Rol no reconocido, redirigir a una página de error o mostrar un mensaje apropiado
        $message = 'Rol de usuario no reconocido.';
      }
    } else {
      // el usuario no ha superado el número de intentos fallidos, actualizar el contador de intentos fallidos
      if ($results) {
        $attempts = $results['attempts'] + 1;

        if ($attempts >= $max_attempts) {
          // Redirigir al usuario a la página "blocked.php"
          header('Location: blocked.php');
          exit();
        }

        $update_attempts = $conn->prepare('UPDATE users SET attempts=:attempts, last_attempt=CURRENT_TIMESTAMP WHERE email = :email');
        $update_attempts->bindParam(':attempts', $attempts);
        $update_attempts->bindParam(':email', $email);
        $update_attempts->execute();
      }

      $message = 'El correo electrónico o la contraseña que ingresó son incorrectos.';
      guardar_registro($email, false, false); // Guardar el correo en el registro
    }
  } else {
    // El reCAPTCHA no es válido, mostrar un mensaje de error
    $message = 'Por favor, completa la verificación reCAPTCHA.';
  }
}
?>


<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Ingresar</title>
  <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

<body>

<?php require 'partials/header.php' ?>

<?php if (!empty($message)): ?>
  <p><?= $message ?></p>
<?php endif; ?>

<h1>Ingresar</h1>
<span>o <a href="signup.php">Crear Cuenta</a></span>

  <form action="login.php" method="POST">
      <input name="email" type="text" placeholder="Ingrese correo electrónico" required <?php if (isset($remainingTime) && $remainingTime > 0) echo 'disabled'; ?>>
      <input name="password" type="password" placeholder="Ingrese su contraseña" required <?php if (isset($remainingTime) && $remainingTime > 0) echo 'disabled'; ?>>
      
      <div class="mb-3" id="UNO">
        <div class="g-recaptcha" id="DOS" data-sitekey="6LcbcmEmAAAAAMTZ-5KZjmjzrFUMUs3yQw5tkJmb"> 
  
         </div>

      </div>  
      <input type="submit" value="Enviar" <?php if (isset($remainingTime) && $remainingTime > 0) echo 'disabled'; ?>>
  
  </form>
      
</body>
</html>


