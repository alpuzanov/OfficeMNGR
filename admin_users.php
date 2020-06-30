<?php
require_once "pdo.php";
require_once "MyUtil.php";

// Проверяем то, что пользователь залогинен и его права
if (!isset($_SESSION['user_id'])) {
  $_SESSION['$FlashMessages']->set('user_not_logged_in', 'warning', 'Пожалуйста представьтесь');
  header("Location: login.php");
  return;
}

//Читаем данные из таблицы USERS
$tb_contents = $pdo -> query("
                              SELECT
                              	  a.user_id,
                                  a.name,
                                  a.familyname,
                                  a.department,
                                  a.email,
                                  a.password,
                                  IF(a.invitation is null, '0', '1') as invitation,
                                  a.markdel
                              FROM
                              	users as a
                              ");
//Считаем сколько записано строк
$Rows_num = $tb_contents -> rowCount();
//Забираем все строки
$tb_contents = $tb_contents -> fetchall(PDO::FETCH_ASSOC);


//Читаем данные из таблицы PRIVILEGES - полномочия
$tb2_contents = $pdo -> query("
                                SELECT
                                	us.user_id,
                                	pt.priv_tp_name,
                                	p.priv_name
                                FROM users AS us
                                	left join (select * from assigned_privileges where priv_id is not null) as ap on (us.user_id = ap.user_id)
                                	left join privileges as p on ap.priv_id = p.priv_id
                                	left join privilege_types as pt on p.priv_tp_id = pt.priv_tp_id
                                WHERE p.priv_id IS NOT NULL
                                ORDER BY 2
                              ");

//Считаем сколько записано строк
$Rows2_num = $tb2_contents -> rowCount();
//Забираем все строки
$tb2_contents = $tb2_contents -> fetchall(PDO::FETCH_ASSOC);



//Читаем данные из таблицы PRIVILEGES - области планирования
$tb3_contents = $pdo -> query("
                                select
                                	us.user_id,
                                	pd.dom_name
                                from users as us
                                	left join (select * from assigned_privileges where dom_id is not null) as ap on (us.user_id = ap.user_id)
                                	left join planning_domains as pd on ap.dom_id = pd.dom_id
                                where pd.dom_id is not null
                                order by 2
                              ");

//Считаем сколько записано строк
$Rows3_num = $tb3_contents -> rowCount();
//Забираем все строки
$tb3_contents = $tb3_contents -> fetchall(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<link type="text/css" rel="stylesheet" href="MyStyle.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src = "MyUtil.js"></script>


  <head>
    <title>Team Calculator - Edit users</title>
  </head>

<body>

<?php
  require_once "NavBar.php";
  $_SESSION['$FlashMessages']->show('bad_table_name');
  $_SESSION['$FlashMessages']->show('rows_added');
  $_SESSION['$FlashMessages']->show('rows_updated');
  $_SESSION['$FlashMessages']->show('rows_deleted');
  $_SESSION['$FlashMessages']->show('pass_dropped');
  $_SESSION['$FlashMessages']->show('pass_not_dropped');
  $_SESSION['$FlashMessages']->show('inv_sent');
  $_SESSION['$FlashMessages']->show('inv_not_sent');
  $_SESSION['$FlashMessages']->show('no_userid_for_Priv');
?>
<div>
  <h3>Пользователи системы:</h3>
  <form method="post">
    <input type="button" value="Добавить" id="Add_row">
    <input type="button" value="Скрыть заблокированных" id="Toggle_show_blocked" class="Show_hidden">
      <table id="TableRows">
        <tr class="tr_heading">
          <th>ID</th>
          <th class="Hide_Column">mark_del</th>
          <th class="Hide_Column">mark_edit</th>
          <th>E-mail</th>
          <th>Имя</th>
          <th>Фамилия</th>
          <th>Подразделение</th>
          <th>Статус пароля</th>
          <th></th>
          <th>Полномочия</th>
          <th>Области планирования</th>
          <th></th>
          <th>Заблокирован</th>
        </tr>
        <?php
        for ($i = 0; $i < $Rows_num; $i++) {

          if ($tb_contents[$i]['markdel'] == 1) {
            echo('<tr class = "blocked">');
          }
          else{
            echo('<tr>');
          }
          echo('<td class="td_id">'.$tb_contents[$i]['user_id'].'</td>');

          if ($tb_contents[$i]['markdel'] == 1) {
            echo('<td class="mark_del Hide_Column">1</td>');
          }
          else {
            echo('<td class="mark_del Hide_Column">0</td>');
          }

          echo('<td class="mark_edit Hide_Column">0</td>');
          echo('<td class="td_Editable">'.$tb_contents[$i]['email'].'</td>');
          echo('<td class="td_Editable">'.$tb_contents[$i]['name'].'</td>');
          echo('<td class="td_Editable">'.$tb_contents[$i]['familyname'].'</td>');
          echo('<td class="td_Editable">'.$tb_contents[$i]['department'].'</td>');

          if ($tb_contents[$i]['password'] != '') {
            echo('<td>Установлен</td>');
            echo('<td><input type="button" value="Сбросить" class="drop_pass_invite"></td>');
          }
          else if ($tb_contents[$i]['password'] == '' && $tb_contents[$i]['invitation'] == 0) {
            echo('<td>Не установлен</td>');
            echo('<td><input type="button" value="Отправить приглашение" class="drop_pass_invite"></td>');
          }
          else {
            echo('<td>Приглашен</td>');
            echo('<td><input type="button" value="Отправить повторно" class="drop_pass_invite"></td>');
          }

          $Assigned_Priv = '';
          for ($j=0; $j < $Rows2_num ; $j++) {
            if ($tb2_contents[$j]['user_id'] == $tb_contents[$i]['user_id']) {
              $Assigned_Priv = $Assigned_Priv.$tb2_contents[$j]['priv_name'].', ';
            }
          }
          $Assigned_Priv = substr($Assigned_Priv, 0, strlen($Assigned_Priv)-2);
          if ($Assigned_Priv == '') {
            $Assigned_Priv = 'Нет полномочий';
          }
          echo('<td>'.$Assigned_Priv.'</td>');

          $Assigned_Dom = '';
          for ($j=0; $j < $Rows3_num ; $j++) {
            if ($tb3_contents[$j]['user_id'] == $tb_contents[$i]['user_id']) {
              $Assigned_Dom = $Assigned_Dom.$tb3_contents[$j]['dom_name'].', ';
            }
          }
          $Assigned_Dom = substr($Assigned_Dom, 0, strlen($Assigned_Dom)-2);
          if ($Assigned_Dom == '') {
            $Assigned_Dom = 'Нет областей';
          }
          echo('<td>'.$Assigned_Dom.'</td>');


          echo('<td><input type="button" value="Изменить" class="priv_manage"></td>');



          if ($tb_contents[$i]['markdel'] == 1) {
            echo('<td align="center"><input type="checkbox" checked="checked" value = 1 class = "block_Checkbox"></td></tr>');
          }
          else {
            echo('<td align="center"><input type="checkbox" value = 0 class = "block_Checkbox"></td></tr>');
          }
        }
        ?>
      </table>

    <input type="button" value="Сохранить" id="Save_btn">
    <img id="load_spin" class="hidden" src="images/load.gif" height = "20" alt="Loading...">
  </form>

</div>
</body>


<script type="text/javascript">

  $(document).ready(function()
  {
    //Отключаем кеш для вызова
    $.ajaxSetup({cashe: false});

    //По клику на кнопку Показываем / скрываем заблокированных и меняем текст кнопки
    $('#Toggle_show_blocked').on('click',function() {
      if ($('#Toggle_show_blocked').hasClass('Show_hidden')) {
          $('#Toggle_show_blocked').attr('value', 'Показать заблокированных');
      }
      else{
        $('#Toggle_show_blocked').attr('value', 'Скрыть заблокированных');
      }
      $('.blocked').toggleClass('Hide_row');
      $('#Toggle_show_blocked').toggleClass('Show_hidden');
    });

    //По клику на кнопку сбрасываем пароль и отправляем приглашение пользователю
    $('.drop_pass_invite').on('click',function() {
      //Работаем только если вся таблица заполнена корректно
      if (check_table_notempty('TableRows', [3,4,5,6]) == true){
        var id_pass_drop = Number($(this).closest('tr').children().get(0).innerHTML);

        $.ajax({
            url: "pass_drop.php",
            type: "POST",
            data: JSON.stringify({id_pass_drop:id_pass_drop}),
            contentType: "application/json"
          }).always(function (data) {
            window.location.reload(false);
          });

      }
    });

    //По клику на кнопку переходим к редактированию присвоенных полномочий и областей планирования
    $('.priv_manage').on('click',function() {
      var id_priv_manage = Number($(this).closest('tr').children().get(0).innerHTML);
      //Если в таблицу вносились изменения или добавлялись строки - предупреждаем, что все сбросится и только после этого переходим
      var changes = $('.td_id:empty').length + $(".mark_edit:contains('1')").length;
      if (changes != 0) {
        // Формируем предупреждение пользователю
        var del_warn = 'При переходе все изменения в таблице будут потеряны!\n Вы действительно хотите отменить изменения и перейти к редактированию полномочий пользователя?';
        //Выводим сообщение пользователю на подтверждение
        if (confirm(del_warn)) {
          window.location.href = '/teamcalc/admin_assigned_priv.php?priv_manage_id='+id_priv_manage;
        }
      }
      else {
        window.location.href = '/teamcalc/admin_assigned_priv.php?priv_manage_id='+id_priv_manage;
      };
    });


    //По выбору checkbox'а в зависимости от настройки "скрыть/показать заблокированных" проставляем классы, отвечающие за отображение заблокированных и поле mark_del для записи пометки о блокировке
    $('#TableRows').on('change', '.block_Checkbox',function() {
      var val_after = (Number($(this).get(0).value)+1)%2;
      $(this).get(0).value = val_after;

      if ($('#Toggle_show_blocked').hasClass('Show_hidden')) {
        $(this).closest('tr').toggleClass('blocked');
      }
      else {
        $(this).closest('tr').toggleClass('blocked Hide_row');
      }

      $(this).closest('tr').children('.mark_edit').html(1);
      $(this).closest('tr').children('.mark_del').html(val_after);
      //Подсвечиваем незаполненные
      check_table_notempty ('TableRows', [3,4,5,6]);
    });


    //По клику меняем содержание ячейки с классом td_Editable на поле ввода с содержимым ячейки
    $('#TableRows').on('click', '.td_Editable',function() {
      contentBeforeEdit = $(this).text();
      $(this).removeClass('td_Editable');
      $(this).html('<input type="text" value="'+contentBeforeEdit+'" class="inputActive">');
      $(this).children('.inputActive').select();
    });

    //В момент выхода из редактирования - заменяем поле ввода/выпадающий список на текст с содержимым элемента
    $('#TableRows').on('blur', '.inputActive', function(){
      $(this).removeClass('inputActive');

      //Устанавливаем необходимый класс в зависимости от типа элемента
      if ($(this).is('select')){
        var contentAfterEdit = $('option:selected',this).text();
        $(this).parent().attr('class','td_Selectable');
        // устанавливаем необходимое значение ID из связанной таблицы в соотв-е поля
        $(this).parent().prev().html($(this).val());
        // устанавливаем флаг mark_edit на строке, если было изменение в значении
        if ($(this).val() != contentBeforeEdit) {
          $(this).closest('tr').children('.mark_edit').html(1);
        }
      }
      else if ($(this).is('input')) {
        var contentAfterEdit = $(this).get(0).value;
          $(this).parent().attr('class','td_Editable');
          // устанавливаем флаг mark_edit на строке, если было изменение в значении
          if (contentAfterEdit != contentBeforeEdit) {
            $(this).closest('tr').children('.mark_edit').html(1);
          }
      }
      //Устанавливаем выбранное/введенное значение
      $(this).parent().html(contentAfterEdit);

      //подсвечиваем незаполненные
      check_table_notempty ('TableRows', [3,4,5,6]);
    });

    //Добавляем строку
    $('#Add_row').on('click',function() {
      $('#TableRows').append( '<tr><td class="td_id"></td>'+
                              '<td class="mark_del Hide_Column">0</td>'+
                              '<td class="mark_edit Hide_Column">0</td>'+
                              '<td class="td_Editable"></td>'+
                              '<td class="td_Editable"></td>'+
                              '<td class="td_Editable"></td>'+
                              '<td class="td_Editable"></td>'+
                              '<td>Пользователь не создан</td>'+
                              '<td></td>'+
                              '<td>Пользователь не создан</td>'+
                              '<td>Пользователь не создан</td>'+
                              '<td></td>'+
                              '<td align="center"><input type="checkbox" value=0 class="block_Checkbox"></td></tr>');
      //подсвечиваем незаполненные
      check_table_notempty ('TableRows', [3,4,5,6]);
    });


// Обрабатываем клик на кнопку сохранения
    $('#Save_btn').on('click',function() {

// Работаем только если заполнены все обязательные поля
    if (check_table_notempty ('TableRows', [3,4,5,6]) == true){

      //Включаем индикатор загрузки
      $('#load_spin').removeClass('hidden');

      //Забираем данные в массив tbl
      var tbl = $('table#TableRows tr:not(.tr_heading)').get().map(function(row) {
        return $(row).find('td').get().map(function(cell) {
          return $(cell).html();
        });
      });

//Делим tbl на 2 массива (Пользователей через интерфейс не удаляем):

      //Удаляемые строки (НЕ ИСПОЛЬЗУЕТСЯ? нужен для обращения к table_writer.php)
      var arr_for_deletion = [];
      //Обновляемые строки (есть id, есть пометка о изменении)
      var arr_for_merge = [];
      //Добавляемые строки (нет id)
      var arr_for_adding = [];

      var len = $('#TableRows').find('tr').length;

       for (i = 0; i < len-1; i++) {
          if ((tbl[i][0] !== '') && (Number(tbl[i][2]) == 1)) {
            var merge_element = {};
            merge_element.user_id = tbl[i][0];
            merge_element.name = tbl[i][4];
            merge_element.familyname = tbl[i][5];
            merge_element.department = tbl[i][6];
            merge_element.email = tbl[i][3];
            merge_element.markdel = tbl[i][1];
            arr_for_merge.push(merge_element);
          }
          else if (tbl[i][0] == '') {
            var add_element = {};
            add_element.name = tbl[i][4];
            add_element.familyname = tbl[i][5];
            add_element.department = tbl[i][6];
            add_element.email = tbl[i][3];
            add_element.markdel = tbl[i][1];
            arr_for_adding.push(add_element);
          }
        }

        // Отладка массивов
        // console.log("Удаление:");
        // console.log(arr_for_deletion);
        // console.log("Обновление:");
        // console.log(arr_for_merge);
        // console.log("Добавление:");
        // console.log(arr_for_adding);


      //Вносим изменения
        $.ajax({
            url: "table_writer.php",
            type: "POST",
            data: JSON.stringify({table:'users', rows_add:arr_for_adding, rows_del:arr_for_deletion, rows_merge:arr_for_merge}),
            contentType: "application/json"
          }).always(function (data) {
            window.location.reload(false);
          });
        }
      //Окончание обработки кнопки сохранения
      });
    //Окончание скрипта по document.ready
      });

</script>

</html>
