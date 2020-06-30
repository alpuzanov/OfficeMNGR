<?php
require_once "pdo.php";
require_once "MyUtil.php";


// Проверяем то, что пользователь залогинен и его права
if (!isset($_SESSION['user_id'])) {
  $_SESSION['$FlashMessages']->set('user_not_logged_in', 'warning', 'Пожалуйста представьтесь');
  header("Location: login.php");
  return;
}

// По кнопке CANCEL перенаправляем на admin_users.php
if ( isset($_POST['Cancel']) ) {
    header('Location: admin_users.php');
    return;
}

// Проверяем то, что передан ID пользователя
if (!isset($_GET['priv_manage_id']) || !is_numeric($_GET['priv_manage_id'])) {
  $_SESSION['$FlashMessages']->set('no_userid_for_Priv', 'error', 'Попытка администрирования с некорректным (или без) user_id');
  header("Location: admin_users.php");
  return;
}
else {
  $user_id = $_GET['priv_manage_id'];
}

//Читаем данные о присвоенных привелегиях из таблиц USERS, PRIVILEGES, ASSIGNED_PRIVILEGES, PRIVILEGE TYPES по id пользователя
$sql = "SELECT
        	us.user_id,
        	us.email,
          us.name,
          us.familyname,
          us.department,
        	pt.priv_tp_name,
          ap.assign_id,
        	p.priv_id,
        	p.priv_name,
        	IF(ap.priv_id is null, '0', '1') as assigned
        from users as us
        	cross join privileges as p
        	left join (select * from assigned_privileges where priv_id is not null) as ap on (p.priv_id = ap.priv_id and us.user_id = ap.user_id)
        	left join privilege_types as pt on p.priv_tp_id = pt.priv_tp_id
        where
        	us.user_id = :uid
        order by 1,6,9";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':uid' => $user_id));

//Считаем сколько записано строк
$Rows_num = $stmt -> rowCount();

if ($Rows_num == 0) {
  $_SESSION['$FlashMessages']->set('no_userid_for_Priv', 'error', 'Попытка администрирования с некорректным (или без) user_id');
  header("Location: admin_users.php");
  return;
}


//Забираем все строки
$tb_contents = $stmt -> fetchall(PDO::FETCH_ASSOC);


//Читаем данные о присвоенных Областях планирования из таблиц USERS, PLANNING_DOMAINS по id пользователя
$sql = "SELECT
        	us.user_id,
        	us.email,
          ap.assign_id,
        	pd.dom_id,
        	pd.dom_name,
        	IF(ap.dom_id is null, '0', '1') as assigned
        from users as us
        	cross join planning_domains as pd
        	left join (select * from assigned_privileges where dom_id is not null) as ap on (pd.dom_id = ap.dom_id and us.user_id = ap.user_id)
        where
        	us.user_id = :uid
        order by 3";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':uid' => $user_id));

//Считаем сколько записано строк
$Rows2_num = $stmt -> rowCount();

//Забираем все строки
$tb2_contents = $stmt -> fetchall(PDO::FETCH_ASSOC);



?>

<!DOCTYPE html>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>

<html>
  <link type="text/css" rel="stylesheet" href="MyStyle.css">
  <head>
    <title>Team Calculator - User's privileges</title>
  </head>

  <body>
    <?php
    echo('<pre>');
//    var_dump($tb_contents);
    echo('</pre>');
    $var ?>
    <div>
      <h4>Управление полномочиями для:</h4>
      <p id="uss_by"><?php echo($_SESSION['user_id']) ?></p>
      <label>ID:</label>
      <p id="uid"><?php echo($tb_contents[1]['user_id']) ?></p>
      <label>Имя:</label>
      <p><?php echo($tb_contents[1]['name'].' '.$tb_contents[1]['familyname']) ?></p>
      <label>Email:</label>
      <p><?php echo ($tb_contents[1]['email']) ?></p>
      <label>Подразделение:</label>
      <p><?php echo ($tb_contents[1]['department']) ?></p>

      <form method="post">
        <h4>Полномочия:</h4>
        <table id="TableRows" class="TableRows">
          <tr class="tr_heading">
            <th>ID</th>
            <th class="Hide_Column">assign_id</th>
            <th class="Hide_Column">mark_assign</th>
            <th class="Hide_Column">mark_edit</th>
            <th>Тип</th>
            <th>Полномочие</th>
            <th>Присвоено?</th>
          </tr>
          <?php
          for ($i = 0; $i < $Rows_num; $i++) {
            echo('<tr><td class="td_id">'.$tb_contents[$i]['priv_id'].'</td>');
            if (!is_null($tb_contents[$i]['assign_id'])) {
              echo('<td class="assign_id Hide_Column">'.$tb_contents[$i]['assign_id'].'</td>');
            }
            else {
              echo('<td class="assign_id Hide_Column"></td>');
            }
            if ($tb_contents[$i]['assigned'] == 1) {
              echo('<td class="mark_assign Hide_Column">1</td>');
            }
            else {
              echo('<td class="mark_assign Hide_Column">0</td>');
            }
            echo('<td class="mark_edit Hide_Column">0</td>');
            echo('<td>'.$tb_contents[$i]['priv_tp_name'].'</td>');
            echo('<td>'.$tb_contents[$i]['priv_name'].'</td>');
            if ($tb_contents[$i]['assigned'] == 1) {
              echo('<td align="center"><input type="checkbox" checked="checked" value = 1 class="assign_Checkbox"></td></tr>');
            }
            else {
              echo('<td align="center"><input type="checkbox" value = 0 class="assign_Checkbox"></td></tr>');
            }
          }
          ?>
        </table>

        <h4>Области планирования:</h4>
        <table id="TableRows2" class="TableRows">
          <tr class="tr_heading">
            <th>ID</th>
            <th class="Hide_Column">assign_id</th>
            <th class="Hide_Column">mark_assign</th>
            <th class="Hide_Column">mark_edit</th>
            <th>Область</th>
            <th>Присвоено?</th>
          </tr>
          <?php
          for ($i = 0; $i < $Rows2_num; $i++) {
            echo('<tr><td class="td_id">'.$tb2_contents[$i]['dom_id'].'</td>');
            if (!is_null($tb2_contents[$i]['assign_id'])) {
              echo('<td class="assign_id Hide_Column">'.$tb2_contents[$i]['assign_id'].'</td>');
            }
            else {
              echo('<td class="assign_id Hide_Column"></td>');
            }
            if ($tb2_contents[$i]['assigned'] == 1) {
              echo('<td class="mark_assign Hide_Column">1</td>');
            }
            else {
              echo('<td class="mark_assign Hide_Column">0</td>');
            }
            echo('<td class="mark_edit Hide_Column">0</td>');
            echo('<td>'.$tb2_contents[$i]['dom_name'].'</td>');
            if ($tb2_contents[$i]['assigned'] == 1) {
              echo('<td align="center"><input type="checkbox" checked="checked" value = 1 class="assign_Checkbox"></td></tr>');
            }
            else {
              echo('<td align="center"><input type="checkbox" value = 0 class="assign_Checkbox"></td></tr>');
            }
          }
          ?>
        </table>

        <input type="button" value="Сохранить" id="Save_btn">
        <input type="submit" name="Cancel" value="Отмена">
        <img id="load_spin" class="hidden" src="images/load.gif" height = "20" alt="Loading...">
      </form>
    </div>
  </body>

<script type="text/javascript">

  $(document).ready(function()
  {
    //Отключаем кеш для вызова
    $.ajaxSetup({cashe: false});

    //По выбору checkbox'а поле mark_assign для записи пометки о блокировке
    $('.TableRows').on('change', '.assign_Checkbox',function() {
      var val_after = (Number($(this).get(0).value)+1)%2;
      $(this).get(0).value = val_after;
      $(this).closest('tr').children('.mark_edit').html(1);
      $(this).closest('tr').children('.mark_assign').html(val_after);
    });

        // Обрабатываем клик на кнопку сохранения
        $('#Save_btn').on('click',function() {


          //1 - Сохраняем Полномочия


          //Забираем данные в массив tbl
          var tbl = $('table#TableRows tr:not(.tr_heading)').get().map(function(row) {
            return $(row).find('td').get().map(function(cell) {
              return $(cell).html();
            });
          });

          //Забираем tbl в массивы DELETE и ADDING:

          //Удаляемые строки (есть id строки присвоения, присвоение = 0)
          var arr_for_deletion = [];
          //Обновляемые строки (НЕ ИСПОЛЬЗУЕТСЯ? нужен для обращения к table_writer.php)
          var arr_for_merge = [];
          //Добавляемые строки (нет id строки присвоения, присвоение = 1)
          var arr_for_adding = [];

          var len = $('#TableRows').find('tr').length;

           for (i = 0; i < len-1; i++) {
              if ((tbl[i][1] !== '') && (Number(tbl[i][2]) == 0)) {
                var del_element = {};
                del_element.assign_id = tbl[i][1];
                arr_for_deletion.push(del_element);
              }
              else if ((tbl[i][1] == '') && (Number(tbl[i][2]) == 1)) {
                var add_element = {};
                add_element.user_id = Number($('#uid').get(0).innerHTML);
                add_element.assigned_by = Number($('#uss_by').get(0).innerHTML);
                add_element.priv_id = tbl[i][0];
                arr_for_adding.push(add_element);
              }
            }


            //2 - Сохраняем Области планирования


            //Забираем данные в массив tbl
            tbl = $('table#TableRows2 tr:not(.tr_heading)').get().map(function(row) {
              return $(row).find('td').get().map(function(cell) {
                return $(cell).html();
              });
            });

            //Забираем tbl в массивы DELETE и ADDING:

            len = $('#TableRows2').find('tr').length;

             for (i = 0; i < len-1; i++) {
                if ((tbl[i][1] !== '') && (Number(tbl[i][2]) == 0)) {
                  var del_element = {};
                  del_element.assign_id = tbl[i][1];
                  arr_for_deletion.push(del_element);
                }
                else if ((tbl[i][1] == '') && (Number(tbl[i][2]) == 1)) {
                  var add_element = {};
                  add_element.user_id = Number($('#uid').get(0).innerHTML);
                  add_element.assigned_by = Number($('#uss_by').get(0).innerHTML);
                  add_element.dom_id = tbl[i][0];
                  arr_for_adding.push(add_element);
                }
              }

              //Вносим изменения
                $.ajax({
                    url: "table_writer.php",
                    type: "POST",
                    data: JSON.stringify({table:'assigned_privileges', rows_add:arr_for_adding, rows_del:arr_for_deletion, rows_merge:arr_for_merge}),
                    contentType: "application/json"
                  }).always(function (data) {
                    window.location.href = '/teamcalc/admin_users.php';
                  });

        //Окончание скрипта по кнопке Сохранить
        });
  //Окончание скрипта по document.ready
  });

</script>
</html>
