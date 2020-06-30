<?php
    require_once "pdo.php";
    require_once "MyUtil.php";
    header('Content-Type: application/json; charset=utf-8');

/////  !!!! ДОПИСАТЬ ПРОВЕРКУ ПОЛНОМОЧИЙ !!!!


  // Получаем данные из тела запроса
    $data = json_decode(file_get_contents('php://input'));
    $id = (int) $data->id_pass_drop;

  // Получаем email
    $sql = "SELECT email FROM users WHERE user_id=$id";
    $tbl_contents_stmt = $pdo->query($sql);
    $row = $tbl_contents_stmt->fetch(PDO::FETCH_NUM);

    $to = $row[0];

  // Генерируем приглашение
    $invitation = bin2hex(random_bytes(10));

  // Сбрасываем пароль и записываем приглашение
    $sql = "UPDATE users SET password = NULL, invitation = '$invitation' WHERE user_id=$id";
    $result = $pdo->query($sql);
    var_dump($result);

    if ($result !== FALSE){
      $_SESSION['$FlashMessages']->set('pass_dropped', 'success', 'Пароль сброшен для пользователя '.$to);
    }
    else {
      $_SESSION['$FlashMessages']->set('pass_not_dropped', 'error', 'Ошибка при сбросе пароля для пользователя '.$to);
      return;
    }

    $subject = "Учетная запись TeamCalc";
    $message = "<p>Для вас создана учетная запись в системе TeamCalc </p><p>Для окончания регистрации пожалуйста пройдите по ссылке:</p>";
    $message = $message.'<a href="http://localhost/teamcalc/register.php?invitation='.$invitation.' '.'">Зарегистрироваться</a>';

    $headers = array(
        'From' => 'mailer.AP@yandex.ru',
        'Content-type' => 'text/html'
    );

    if (mail($to, $subject, $message, $headers)) {
      $_SESSION['$FlashMessages']->set('inv_sent', 'success', 'Выслано приглашение для пользователя '.$to);
    }
    else {
      $_SESSION['$FlashMessages']->set('inv_not_sent', 'error', 'Ошибка при высылке приглашения для пользователя '.$to);
      return;
    }

?>
