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
                  d.priv_name,
                	b.comp_id,
                  c.comp_name
                FROM (SELECT * FROM users WHERE email = :em AND password = :pw) AS a
                LEFT JOIN (SELECT * FROM assigned_privileges WHERE comp_id IS NOT NULL) AS b
                ON a.user_id = b.user_id
                LEFT JOIN companies AS c
                ON b.comp_id = c.comp_id
                LEFT JOIN privileges as d
                ON b.priv_id = d.priv_id
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
                  if ($usr_contents[$i]['priv_id'] == 9) {
                    $_SESSION['company'] = array($usr_contents[$i]['comp_id']=>$usr_contents[$i]['comp_name']);
                  }
                  if (is_null($usr_contents[$i]['comp_id'])) {
                    $_SESSION['sys_priv'][] = $usr_contents[$i]['priv_id'];
                    //$usr_contents[$i]['dom_id']
                  }
                  if (!is_null($usr_contents[$i]['comp_id'])) {
                    $_SESSION['comp_priv'][] = array("comp_id"=>$usr_contents[$i]['comp_id'],"priv_id"=>$usr_contents[$i]['priv_id']);
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
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<script src = "MyUtil.js"></script>

<head>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
  <link href="login_floating_labels.css" rel="stylesheet">
  <link type="text/css" rel="stylesheet" href="MyStyle.css">
  <title>Office Manager Login</title>
</head>

  <body id="PageBody">
    <?php
      $_SESSION['$FlashMessages']->show('login fail');
      $_SESSION['$FlashMessages']->show('bad email/pwd');
      $_SESSION['$FlashMessages']->show('user blocked');
      $_SESSION['$FlashMessages']->show('user_not_logged_in');
      $_SESSION['$FlashMessages']->show('no_register_token');
      $_SESSION['$FlashMessages']->show('unvalid_token');
    ?>

    <form method="POST" class="form-signin">

      <div class="text-center mb-4">
        <img class="mb-4" src="assets/brand/avocado.png" alt="Avocado-Office" width="100" height="100">
        <h1 class="h3 mb-3 font-weight-normal">Представьтесь, пожалуйста</h1>
      </div>

      <div class="form-label-group">
        <input type="email" id="email" name="email" class="form-control" placeholder="Email" required autofocus>
        <label for="inputEmail">Email</label>
      </div>

      <?php
        // $_SESSION['$FlashMessages']->show('bad email format');
      ?>

      <div class="form-label-group">
        <input type="password" name="pass" id="pass" class="form-control" placeholder="Пароль" required>
        <label for="inputPassword">Пароль</label>
      </div>

      <button class="btn btn-lg btn-dark btn-block" type="submit"  value="Войти">Войти</button>
      <button class="btn btn-lg btn-secondary btn-block" type="submit" value="Отмена" name="cancel">Отмена</button>
      <p class="mt-5 mb-3 text-muted text-center">&copy; 2020, Avocado Soft</p>


        <!-- <input type="submit" name="cancel" value="Отмена"> -->
    </form>
  </body>
</html>
