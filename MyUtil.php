<?php

  $salt = 'S@lt469*_';

  //Функция возвращает строку - полный путь к подразделению из таблицы ORG_UNITS по ID
  function org_fullPath($Org_un_id)
  {
    global $pdo;
    $sql = "SELECT
            	org_un_mother,
            	org_un_name
            FROM
            	org_units
            WHERE
            	org_un_id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':id' => $Org_un_id));
    $cur_str_contents = $stmt -> fetchall();
    if (count($cur_str_contents) == 0) return "";
    return org_fullPath($cur_str_contents[0]['org_un_mother'])." \ ".$cur_str_contents[0]['org_un_name'];
  }


  //Класс, реализующий хранение и вывод "Flash messages" - Однократных сообщений пользователю
  class FlashMessages {
    // Массив, в котором храняться сообщения
    public $Messages = array();

    // Функция добавляет новое сообщение в массив
    // handler - имя сообщения
    // type - тип сообщения. Значения: error, warning, success
    // text - текст, который будет отображаться
    function set($handler, $type, $text) {
      $this->Messages [strval($handler)] =  array('type'=>$type, 'text'=>$text);
    }


    // Функция отображает сообщение из массива, после этого удаляет его
    // handler - имя сообщения
    // Цвет выводимого сообщения зависит от типа
    function show($handler) {
      if (array_key_exists(strval($handler), $this->Messages)) {
        switch ($this->Messages[strval($handler)]['type']) {
            case 'error':
              $msg_color = 'red';
              break;
            case 'warning':
              $msg_color = 'orange';
              break;
            case 'success':
              $msg_color = 'green';
              break;
          }
        echo '<p style="color: '.$msg_color.'">'.$this->Messages[strval($handler)]['text'].'</p>';
        unset($this->Messages[strval($handler)]);
      }
    }
  }

  //Cтартуем сессию
  session_start();

  //Создаем объект FlashMessages в SESSION, если его там нет
  if ( !isset($_SESSION['$FlashMessages']) ) {
    $_SESSION['$FlashMessages'] = new FlashMessages ();
  }

?>
