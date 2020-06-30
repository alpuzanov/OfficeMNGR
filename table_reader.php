<?php
    session_start();
    require_once "pdo.php";
    header('Content-Type: application/json; charset=utf-8');

    //Чистим входящие параметры от SQL-инъекций. Принимаем только фиксированный набор имен таблиц
    switch($_REQUEST['table'])
    {
      case 'Privilege_types':
            $tbl = 'Privilege_types';
            break;
      case 'Privileges':
            $tbl = 'Privileges';
            break;
      case 'Resource_types':
            $tbl = 'Resource_types';
            break;
      case 'Org_units_all':
            $tbl = 'Org_units_all';
            break;
      case 'Org_unit_types':
            $tbl = 'Org_unit_types';
            break;
      case 'Resource_roles':
            $tbl = 'Resource_roles';
            break;
      case 'Resource_specializations':
            $tbl = 'Resource_specializations';
            break;
      case 'Resource_levels':
            $tbl = 'Resource_levels';
            break;
      case 'Resource_locations':
            $tbl = 'Resource_locations';
            break;


      // ДОПИСАТЬ возврат ошибки в случае, если не введена таблица
    }

    //Забираем содержание таблицы
    $sql = "SELECT * FROM $tbl";
    $tbl_contents_stmt = $pdo->query($sql);

    //Количество колонок
    $colcount = $tbl_contents_stmt->columnCount();

    //Забираем имена колонок (работает только с MySQL)
    $sql = "SHOW columns FROM ".$tbl;
    $tbl_headers_stmt = $pdo->query($sql);

    $tbl_headers = array();
    while ( $row = $tbl_headers_stmt -> fetch(PDO::FETCH_NUM) )
    {
      $tbl_headers[] = $row[0];
    }


    $retval = array();

    $i=0;
    while ( $row = $tbl_contents_stmt->fetch(PDO::FETCH_NUM) )
    {
      for ($j=0; $j < $colcount;$j++){
        $retval[$i][$tbl_headers[$j]] = htmlentities($row[$j]);
      }
      $i++;
    }

    echo(json_encode($retval, JSON_PRETTY_PRINT));


?>
