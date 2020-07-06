<?php
require_once "pdo.php";
require_once "MyUtil.php";

// По кнопке CANCEL перенаправляем на logout.php
if ( isset($_POST['Cancel']) ) {
    header('Location: logout.php');
    return;
}

// Обрабатываем кнопку SAVE
if ( isset($_POST['Save']) ) {
  // Проверяем совпадение и непустоту введенных паролей
  if ($_POST['pass'] == '') {
    $_SESSION['$FlashMessages']->set('pass_empty', 'error', 'Пароль не должен быть пустым');
    header('Location: register.php?invitation='.$_SESSION['invitation']);
    return;
  }
  elseif ($_POST['pass'] != $_POST['repeat_pass']) {
    $_SESSION['$FlashMessages']->set('pass_not_equal', 'error', 'Введенные пароли не совпадают');
    header('Location: register.php?invitation='.$_SESSION['invitation']);
    return;
  }
  else {
  // Пишем в базу
  $stored_pwd = hash('md5', $salt.$_POST['pass']);
  $sql = "UPDATE users SET password = :pwd, invitation = NULL WHERE user_id = :uid";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(array(
                  ':pwd' => $stored_pwd,
                  ':uid' => $_SESSION['user_id']
                ));

  // Перенаправляем на login.php
  $_SESSION['$FlashMessages']->set('pass_set', 'sucess', 'Пароль сохранен');
  header('Location: logout.php');
  return;
  }
}


// Проверяем то, что передан код приглашения, пишем его в сессию
if (!isset($_GET['invitation'])) {
///!!!!!!! ДОПИСАТЬ логирование
  $_SESSION['$FlashMessages']->set('no_register_token', 'error', 'Попытка регистрации без "приглашения"');
  header("Location: logout.php");
  return;
}
else {
  $_SESSION['invitation'] = $_GET['invitation'];
}


//Читаем данные из таблицы USERS по предоставленному приглашению
$sql = "SELECT
          a.user_id,
          a.name,
          a.familyname,
          a.description,
          a.email,
          a.invitation,
          a.markdel
        FROM
        	users as a
        WHERE
          a.invitation = :inv";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':inv' => $_GET['invitation']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);

//Если токена не нашлось - генерится ошибка
if ( $row === false )
  {
    $_SESSION['$FlashMessages']->set('unvalid_token', 'error', 'Предоставленное "приглашение" не зарегистрировано');
    header("Location: logout.php");
    return;
  }

$_SESSION['user_id'] = $row['user_id'];

?>

<!DOCTYPE html>
<html>
  <link type="text/css" rel="stylesheet" href="MyStyle.css">
  <head>
    <title>Office Manager - Register user</title>
  </head>

  <body id="PageBody">
    <div>
      <h4>Для вас создан профиль со следующей информацией:</h4>
      <label>ID:</label>
      <p><?php echo($row['user_id']) ?></p>
      <label>Имя:</label>
      <p><?php echo($row['name'].' '.$row['familyname']) ?></p>
      <label>Email:</label>
      <p><?php echo ($row['email']) ?></p>
      <label>Описание:</label>
      <p><?php echo ($row['description']) ?></p>
      <h4>Для активации учетной записи, создайте пароль:</h4>
      <?php
        $_SESSION['$FlashMessages']->show('pass_empty');
        $_SESSION['$FlashMessages']->show('pass_not_equal');
      ?>
      <form method="post">
        <label>Введите пароль:</label>
        <p><input type="text" name="pass" size="20"></p>
        <label>Повторите пароль:</label>
        <p><input type="text" name="repeat_pass" size="20"></p>
        <input type="submit" name="Save" value="Сохранить">
        <input type="submit" name="Cancel" value="Отмена">
      </form>
    </div>
  </body>
</html>
