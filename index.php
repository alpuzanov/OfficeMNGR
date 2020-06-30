
<?php
require_once "pdo.php";
require_once "MyUtil.php";

// Demand a SESSION "login" parameter
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  return;
}
?>


<!DOCTYPE html>
<html>
<link type="text/css" rel="stylesheet" href="MyStyle.css">
  <head>
    <title>Office Manager</title>
  </head>

<body>
  <?php
  require_once "NavBar.php";
  echo('<pre>');
  var_dump($_SESSION['comp_priv']);
  echo('</pre>');
  ?>
<div>
  <h2>Добро пожаловать, <?php echo ($_SESSION['username']);?></h2>
  <?php
    $_SESSION['$FlashMessages']->show('login ok');
  ?>
</div>
</body>
</html>
