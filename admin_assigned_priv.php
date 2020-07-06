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

//Читаем данные о присвоенных системных полномочиях (не относящихся к компаниям-партнерам) по id пользователя
$sql = "SELECT
	        us.user_id,
        	us.email,
        	us.name,
        	us.familyname,
        	us.description,
        	p.priv_id,
        	p.priv_name,
        	p.priv_description,
        	IF(ap.priv_id is null, 0, 1) as assigned,
        	ap.assign_id
        FROM (SELECT * FROM privileges WHERE priv_tp_id = 1) as p
        CROSS JOIN users as us
        LEFT JOIN assigned_privileges as ap ON (us.user_id = ap.user_id and p.priv_id = ap.priv_id)
        WHERE
         	us.user_id = :uid
        ORDER BY 6
";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':uid' => $user_id));

//Считаем сколько записано строк
$Rows_num = $stmt -> rowCount();

//Если нет строк, значит нет такого пользователя - выдаем ошибку
if ($Rows_num == 0) {
  $_SESSION['$FlashMessages']->set('no_userid_for_Priv', 'error', 'Попытка администрирования с некорректным (или без) user_id');
  header("Location: admin_users.php");
  return;
}

//Забираем все строки
$tb_SysPriv_contents = $stmt -> fetchall(PDO::FETCH_ASSOC);






//Читаем данные о присвоенных полномочиях по отношению к Компаниям

$sql = "SELECT distinct
        	c.comp_id,
        	c.comp_name,
        	IF(ap.priv_id is not null or ap3.priv_id is not NULL, 1, 0) as comp_assigned,
        	IF(ap3.priv_id is null, 0, 1) as default_company,
        	ap3.assign_id as default_assign_id,
        	p.priv_id,
        	p.priv_name,
        	IF(ap2.priv_id is null, 0, 1) as priv_assigned,
        	ap2.assign_id
        from companies as c
        left join (select * from assigned_privileges where (user_id = :uid and priv_id <> 9)) as ap on c.comp_id = ap.comp_id
        cross join (select * from privileges where (priv_tp_id <> 1 and priv_id <> 9)) as p
        left join (select * from assigned_privileges where (user_id = :uid and priv_id <> 9)) as ap2 on (c.comp_id = ap2.comp_id and p.priv_id = ap2.priv_id)
        left join (select * from assigned_privileges where (user_id = :uid and priv_id = 9)) as ap3 on (c.comp_id = ap3.comp_id)
        order by 1,6";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':uid' => $user_id));

//Считаем сколько записано строк
$Rows2_num = $stmt -> rowCount();

//Забираем все строки
$tb_CompPriv_contents = $stmt -> fetchall(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<link type="text/css" rel="stylesheet" href="MyStyle.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src = "MyUtil.js"></script>

<html>
  <head>
    <title>Office Manager - User's privileges</title>
  </head>

  <body id="PageBody">
    <?php
//     echo('<pre>');
//    var_dump($tb_SysPriv_contents);
//     echo('</pre>');
    $var ?>
    <div>
      <h4>Управление полномочиями для:</h4>
      <p id="uss_by"><?php echo($_SESSION['user_id']) ?></p>
      <label>ID:</label>
      <p id="uid"><?php echo($tb_SysPriv_contents[1]['user_id']) ?></p>
      <label>Имя:</label>
      <p><?php echo($tb_SysPriv_contents[1]['name'].' '.$tb_SysPriv_contents[1]['familyname']) ?></p>
      <label>Email:</label>
      <p><?php echo ($tb_SysPriv_contents[1]['email']) ?></p>
      <label>Описание:</label>
      <p><?php echo ($tb_SysPriv_contents[1]['description']) ?></p>

      <form method="post">
        <h4>Системные Полномочия:</h4>
        <table id="TableRows" class="TableRows">
          <tr class="tr_heading">
            <th>ID</th>
            <th class="Hide_Column">assign_id</th>
            <th class="Hide_Column">mark_assign</th>
            <th class="Hide_Column">mark_edit</th>
            <th>Полномочие</th>
            <th>Описание полномочия</th>
            <th>Присвоено?</th>
          </tr>
          <?php
          for ($i = 0; $i < $Rows_num; $i++) {
            echo('<tr><td class="td_id">'.$tb_SysPriv_contents[$i]['priv_id'].'</td>');
            if (!is_null($tb_SysPriv_contents[$i]['assign_id'])) {
              echo('<td class="assign_id Hide_Column">'.$tb_SysPriv_contents[$i]['assign_id'].'</td>');
            }
            else {
              echo('<td class="assign_id Hide_Column"></td>');
            }
            if ($tb_SysPriv_contents[$i]['assigned'] == 1) {
              echo('<td class="mark_assign Hide_Column">1</td>');
            }
            else {
              echo('<td class="mark_assign Hide_Column">0</td>');
            }
            echo('<td class="mark_edit Hide_Column">0</td>');
            echo('<td>'.$tb_SysPriv_contents[$i]['priv_name'].'</td>');
            echo('<td>'.$tb_SysPriv_contents[$i]['priv_description'].'</td>');
            if ($tb_SysPriv_contents[$i]['assigned'] == 1) {
              echo('<td align="center"><input type="checkbox" checked="checked" value = 1 class="assign_Checkbox"></td></tr>');
            }
            else {
              echo('<td align="center"><input type="checkbox" value = 0 class="assign_Checkbox"></td></tr>');
            }
          }
          ?>
        </table>

        <h4>Полномочия для Компаний:</h4>

        <table id="TableRows2" class="TableRows">
          <tr class="tr_heading">
            <th>ID</th>
            <th class="Hide_Column">comp_id</th>
            <th class="Hide_Column">assign_id</th>
            <th class="Hide_Column">mark_assign</th>
            <th class="Hide_Column">mark_edit</th>
            <th>Компания</th>
            <th>Полномочие</th>
            <th>Присвоено?</th>
          </tr>
          <?php
          $comp_id = 0;
          for ($i = 0; $i < $Rows2_num; $i++) {
            if ($tb_CompPriv_contents[$i]['comp_id'] <> $comp_id) {
              $comp_id = $tb_CompPriv_contents[$i]['comp_id'];
              echo('<tr class="tr_company"><td class="td_id">9</td>');
              echo('<td class="comp_id Hide_Column">'.$comp_id.'</td>');
              echo('<td class="assign_id Hide_Column">'.$tb_CompPriv_contents[$i]['default_assign_id'].'</td>');
              echo('<td class="mark_assign_radio Hide_Column">'.$tb_CompPriv_contents[$i]['default_company'].'</td>');
              echo('<td class="mark_edit_radio Hide_Column">0</td>');
              echo('<td>'.$tb_CompPriv_contents[$i]['comp_name'].'</td>');
              echo('<td>Выбрать по умолчанию -></td>');
              if ($tb_CompPriv_contents[$i]['default_company'] == 1) {
                echo('<td align="center"><input name="DefaultCompany" type="radio" checked="checked" value = 1 class="assign_Radio"></td></tr>');
              }
              else {
                echo('<td align="center"><input name="DefaultCompany" type="radio" value = 0 class="assign_Radio"></td></tr>');
              }
            }
            echo('<tr><td class="td_id">'.$tb_CompPriv_contents[$i]['priv_id'].'</td>');
            echo('<td class="comp_id Hide_Column">'.$comp_id.'</td>');
            echo('<td class="assign_id Hide_Column">'.$tb_CompPriv_contents[$i]['assign_id'].'</td>');
            echo('<td class="mark_assign Hide_Column">'.$tb_CompPriv_contents[$i]['priv_assigned'].'</td>');
            echo('<td class="mark_edit Hide_Column">0</td>');
            echo('<td></td>');
            echo('<td style="padding-left: 25px;">'.$tb_CompPriv_contents[$i]['priv_name'].'</td>');
            if ($tb_CompPriv_contents[$i]['priv_assigned'] == 1) {
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

    //По выбору checkbox'аправим поле mark_assign для записи пометки о присвоении
    $('.TableRows').on('change', '.assign_Checkbox',function() {
      var val_after = (Number($(this).get(0).value)+1)%2;
      $(this).get(0).value = val_after;
      $(this).closest('tr').children('.mark_edit').html(1);
      $(this).closest('tr').children('.mark_assign').html(val_after);
    });

    //По выбору RadioButton'а правим поле mark_assign_radio для записи пометки о присвоении
    $('.TableRows').on('change', '.assign_Radio',function() {
      //var val_after = (Number($(this).get(0).value)+1)%2;

      $('#TableRows2').find('.mark_edit_radio').html(1);
      $('#TableRows2').find('.mark_assign_radio').html(0);
      $(this).closest('tr').children('.mark_assign_radio').html(1);
      $(this).get(0).value = 1;
    });

        // Обрабатываем клик на кнопку сохранения
        $('#Save_btn').on('click',function() {


          //1 - Сохраняем Полномочия по отношению к Компаниям


          //Забираем данные в массив tbl
          var tbl = $('table#TableRows2 tr:not(.tr_heading)').get().map(function(row) {
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

          var len = $('#TableRows2').find('tr').length;

           for (i = 0; i < len-1; i++) {
              if ((tbl[i][2] !== '') && (Number(tbl[i][3]) == 0)) {
                var del_element = {};
                del_element.assign_id = tbl[i][2];
                arr_for_deletion.push(del_element);
              }
              else if ((tbl[i][2] == '') && (Number(tbl[i][3]) == 1)) {
                var add_element = {};
                add_element.user_id = Number($('#uid').get(0).innerHTML);
                add_element.assigned_by = Number($('#uss_by').get(0).innerHTML);
                add_element.comp_id = tbl[i][1];
                add_element.priv_id = tbl[i][0];
                arr_for_adding.push(add_element);
              }
            }

            //2 - Сохраняем Системные полномочия


            //Забираем данные в массив tbl
            tbl = $('table#TableRows tr:not(.tr_heading)').get().map(function(row) {
              return $(row).find('td').get().map(function(cell) {
                return $(cell).html();
              });
            });

            //Забираем tbl в массивы DELETE и ADDING:

            len = $('#TableRows').find('tr').length;

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

              //Вносим изменения
                $.ajax({
                    url: "table_writer.php",
                    type: "POST",
                    data: JSON.stringify({table:'assigned_privileges', rows_add:arr_for_adding, rows_del:arr_for_deletion, rows_merge:arr_for_merge}),
                    contentType: "application/json"
                  }).always(function (data) {
                    // window.location.reload(false);
                    window.location.href = '/teamcalc/admin_users.php';
                  });

        //Окончание скрипта по кнопке Сохранить
        });
  //Окончание скрипта по document.ready
  });

</script>
</html>
