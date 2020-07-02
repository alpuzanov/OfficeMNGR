<?php
    header('Content-Type: application/json; charset=utf-8');
    session_start();

/////  !!!! ДОПИСАТЬ ПРОВЕРКУ ПОЛНОМОЧИЙ !!!!


  // Получаем данные из тела запроса
    $data = json_decode(file_get_contents('php://input'));

    $comp_id = (int) $data->comp_id;
    $comp_name = $data->comp_name;

    $_SESSION['company'] = array($comp_id=>$comp_name);

    return;
?>
