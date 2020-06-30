
<script type = "text/javascript" src = "MyUtil.js"></script>
<?php
require_once "pdo.php";
require_once "MyUtil.php";

// Если пользователь залогинен - отправить его на index.php
if (isset($_SESSION['user_id'])) {
  header("Location: index.php");
  return;
}

// Если нажата кнопка "Отмена" - перенаправить на index.php
if ( isset($_POST['cancel'] ) ) {
    header("Location: index.php");
    return;
}

// Прверяем, если в массиве POST есть корректные данные, если все нормально - пытаемся войти в систему
if ( isset($_POST['email']) && isset($_POST['pass']) )
{
    if ( strlen($_POST['email']) < 1 || strlen($_POST['pass']) < 1 )
      {
        $_SESSION['$FlashMessages']->set('bad email/pwd', 'error', 'Поля "E-mail" и "Пароль" не должны быть пустыми');
        header("Location: login.php");
        return;
      }
    elseif ( (strpos ($_POST['email'],"@")<1) or (strpos($_POST['email'], ".",strpos($_POST['email'], "@")) < strpos($_POST['email'], "@")))
      {
        $_SESSION['$FlashMessages']->set('bad email format', 'error', 'Неверный формат E-mail');
        header("Location: login.php");
        return;
      }
    else {
        $check = hash('md5', $salt.$_POST['pass']);
        $sql = "
                SELECT
                	a.user_id,
                	a.name,
                	a.familyname,
                	a.markdel,
                	b.priv_id,
                	b.comp_id
                FROM (SELECT * FROM users WHERE email = :em AND password = :pw) AS a
                LEFT JOIN (SELECT * FROM assigned_privileges WHERE comp_id IS NOT NULL) AS b
                ON a.user_id = b.user_id
                ";

        $stmt = $pdo -> prepare($sql);
        $stmt -> execute(array(':em' => $_POST['email'],
                               ':pw' => $check));

        // Если мы получили что-то в запросе выше, значит есть такой пользователь и пароль совпадает, если нет (count = 0) - что-то не так.
        // Забираем в SESSION присвоенные привелегии и права на домены в массивы sys_priv (системные полномочия) и comp_priv (Полномочия в отношении Компании)

        $usr_contents = $stmt -> fetchall();
        if (count($usr_contents) == 0)
        {
            $_SESSION['$FlashMessages']->set('login fail', 'error', 'Некорректный e-mail или пароль');
            error_log("Login fail ".$_POST['email']." $check");
            header("Location: login.php");
            return;
        }
        else
        {
          if ($usr_contents[0]['markDel'] == TRUE) {
            $_SESSION['$FlashMessages']->set('user blocked', 'error', 'Ваша учетная запись заблокирована, пожалуйста обратитесь к администратору');
          }
          else {
                $_SESSION['user_id'] = $usr_contents[0]['user_id'];
                $_SESSION['username'] = $usr_contents[0]['name'].' '.$usr_contents[0]['familyname'];
                $_SESSION['$FlashMessages']->set('login ok', 'success', 'Вы успешно вошли в систему');
                $_SESSION['sys_priv'] = array();
                $_SESSION['comp_priv'] = array();
                for ($i=0; $i < count($usr_contents); $i++) {
                  if (is_null($usr_contents[$i]['comp_id'])) {
                    $_SESSION['sys_priv'][] = $usr_contents[$i]['priv_id'];
                    //$usr_contents[$i]['dom_id']
                  }
                  if (!is_null($usr_contents[$i]['comp_id'])) {
                    $_SESSION['comp_priv'][] = [$usr_contents[$i]['comp_id']]['comp_name'];
                  }
                }
                header("Location: index.php");
                return;
          }
        }
    }
}

?>


<!DOCTYPE html>
<html>
<link type="text/css" rel="stylesheet" href="MyStyle.css">
<head>
<title>Team Calc Login</title>
</head>
<body>
<div class="container">
<h1>Представьтесь, пожалуйста</h1>
<?php
  $_SESSION['$FlashMessages']->show('login fail');
  $_SESSION['$FlashMessages']->show('bad email/pwd');
  $_SESSION['$FlashMessages']->show('user blocked');
  $_SESSION['$FlashMessages']->show('user_not_logged_in');
  $_SESSION['$FlashMessages']->show('no_register_token');
  $_SESSION['$FlashMessages']->show('unvalid_token');
?>
<form method="POST">
    <label for="email">Email:</label></br>
    <input type="text" name="email" id="email" ></br>
    <?php
      $_SESSION['$FlashMessages']->show('bad email format');
    ?>
    <label for="pass">Пароль:</label></br>
    <input type="password" name="pass" id="pass"></br></br>
    <input type="submit" onclick="return ValidateEmailPass('pass', 'email');" value="Войти">
    <input type="submit" name="cancel" value="Отмена">
</form>
</div>
</body>
</html>