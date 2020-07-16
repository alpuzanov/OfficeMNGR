<?php
    require_once "pdo.php";
    require_once "MyUtil.php";
    header('Content-Type: application/json; charset=utf-8');

    // Получаем данные из тела запроса
    $data = json_decode(file_get_contents('php://input'));

    // Чистим таблицу от SQL-инъекций. Принимаем только фиксированный набор имен таблиц
    switch($data->table)
    {
      case 'privileges':
            $tbl = 'privileges';
            break;
      case 'privilege_types':
            $tbl = 'privilege_types';
            break;
      case 'companies':
            $tbl = 'companies';
            break;
      case 'users':
            $tbl = 'users';
            break;
      case 'assigned_privileges':
            $tbl = 'assigned_privileges';
            break;
      default:
            $_SESSION['$FlashMessages']->set('bad_table_name', 'error', 'Попытка ввода неопределенной таблицы!');
            return;
    }



//ОБЩЕЕ

    //Забираем содержание таблицы
    $sql = "SELECT * FROM $tbl";
    $tbl_contents_stmt = $pdo->query($sql);

    //Количество колонок
    $colcount = $tbl_contents_stmt->columnCount();

    //Забираем имена колонок (работает только с MySQL, в дальнейшем нужно переписать на стандартную работу через SCHEMA)
    $sql = "SHOW columns FROM ".$tbl;
    $tbl_headers_stmt = $pdo->query($sql);

    $tbl_headers = array();
    while ( $row = $tbl_headers_stmt -> fetch(PDO::FETCH_NUM) )
    {
      $tbl_headers[] = $row[0];
    }



// 1 - Если есть элементы в массиве rows_add, добавляем их
// ДОПИСАТЬ!!!!
    // 1 - Проверку на корректность данных и обязательные поля (для этого нужно как-то передавать в модуль какие пояля обязательные)

    if (count($data->rows_add) > 0)
    {

      //Получаем массив имен переданных колонок
      $add_headers = array();
      foreach ($data->rows_add as $key => $row) {
        $arr_add = array_keys((array) $row);
        foreach ($arr_add as $header)
        {
          if (!in_array($header,$add_headers))
          {
            $add_headers[] = $header;
          }
        }
      }

      //Получаем количество переданных колонок
      $add_colcount = count((array) $add_headers);

      //Делаем SQL-выражение INSERT с нужным количеством строк
        $sql = "INSERT INTO ".$tbl." (";
        for ($i = 0; $i < $add_colcount; $i++) {
          $sql = $sql.$add_headers[$i].", ";
        }
        $sql = substr($sql, 0, strlen($sql)-2).") VALUES (";

        for ($i = 0; $i < $add_colcount; $i++) {
          $a = $add_headers[$i];
          $sql = $sql.':'.$a.', ';
        }

      //Появился SQL формата "INSERT INTO Privileges (priv_name, priv_description, priv_tp_id) VALUES (:priv_name, :priv_description, :priv_tp_id)", подготавливаем его
        $sql = substr($sql, 0, strlen($sql)-2).")";
        $stmt = $pdo->prepare($sql);

      var_dump($add_headers);
      var_dump($data->rows_add);


      foreach ($data->rows_add as $row_key => $row) {
        foreach ($add_headers as $header_key => $header) {
          if (!property_exists ($row , $header)) {
            $row->$header = null;
          }
        }
      }

      var_dump($data->rows_add);




      //Вставляем данные из массива для добавления в подготовленное SQL выражение
        for ($i = 0; $i < count($data->rows_add); $i++) {
          $data_arr = (array) $data->rows_add[$i];

          $stmt->execute($data_arr);
        }

        $_SESSION['$FlashMessages']->set('rows_added', 'info', 'Добавлено '.count($data->rows_add).' стр.');
    }

// 2 - Если есть элементы в массиве rows_del, удаляем по id
// ДОПИСАТЬ!!!!
    // 1 - Проверку на наличие id в таблице

    if (count($data->rows_del) > 0)
    {
      $del_headers = array_keys((array) $data->rows_del[0]);

      //Готовим SQL формата "DELETE from Privileges WHERE priv_id = :id"
      $sql = "DELETE from ".$tbl." WHERE ".$del_headers[0]." = :".$del_headers[0];
      $stmt = $pdo->prepare($sql);

      //Вставляем данные из массива для добавления в подготовленное SQL выражение
        for ($i = 0; $i < count($data->rows_del); $i++) {
          $data_arr = (array) $data->rows_del[$i];
          $stmt->execute($data_arr);
        }

      $_SESSION['$FlashMessages']->set('rows_deleted', 'info', 'Удалено '.count($data->rows_del).' стр.');
    }

// 3 - Если есть элементы в массиве rows_merge, обновляе значения по id
// ДОПИСАТЬ!!!!
    // 1 - Проверку на наличие id в таблице

    if (count($data->rows_merge) > 0)
    {
      //Получаем количество переданных колонок
      $merge_colcount = count((array) $data->rows_merge[0]);
      $merge_headers = array_keys((array) $data->rows_merge[0]);

      //Готовим SQL формата "UPDATE Privileges SET priv_name = :priv_name, priv_description = :priv_description, priv_tp_id = :priv_tp_id WHERE priv_id = :priv_id"
      $sql = "UPDATE ".$tbl." SET ";
      for ($i = 0; $i < $merge_colcount; $i++) {
        $sql = $sql.$merge_headers[$i]." = :".$merge_headers[$i].", ";
      }
      $sql = substr($sql, 0, strlen($sql)-2)." WHERE ".$merge_headers[0]." = :".$merge_headers[0];

      $stmt = $pdo->prepare($sql);

      //Вставляем данные из массива для добавления в подготовленное SQL выражение
        for ($i = 0; $i < count($data->rows_merge); $i++) {
          $data_arr = (array) $data->rows_merge[$i];
          $stmt->execute($data_arr);
        }
      $_SESSION['$FlashMessages']->set('rows_updated', 'info', 'Обновлено '.count($data->rows_merge).' стр.');
    }


?>
