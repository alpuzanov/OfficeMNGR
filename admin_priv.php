<?php
require_once "pdo.php";
require_once "MyUtil.php";

// Проверяем то, что пользователь залогинен и его права
if (!isset($_SESSION['user_id'])) {
  $_SESSION['$FlashMessages']->set('user_not_logged_in', 'warning', 'Пожалуйста представьтесь');
  header("Location: login.php");
  return;
}

//Читаем данные из таблицы PRIVILEGES
$tb_contents = $pdo -> query("
                              SELECT
                              	  a.priv_id,
                                  a.priv_name,
                                  a.priv_description,
                                  b.priv_tp_id,
                                  b.priv_tp_name,
                                  b.priv_tp_description
                              FROM
                              	privileges as a
                              LEFT JOIN
                              	privilege_types as b
                              ON a.priv_tp_id = b.priv_tp_id
                              ");

//Считаем сколько записано строк
$Rows_num = $tb_contents -> rowCount();

//Забираем все строки
$tb_contents = $tb_contents -> fetchall();

?>

<!DOCTYPE html>
<html>
<link type="text/css" rel="stylesheet" href="MyStyle.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src = "MyUtil.js"></script>


  <head>
    <title>Office Manager - Edit priviliges</title>
  </head>

<body id="PageBody">
<?php
  require_once "NavBar.php";
  $_SESSION['$FlashMessages']->show('bad_table_name');
  $_SESSION['$FlashMessages']->show('rows_added');
  $_SESSION['$FlashMessages']->show('rows_deleted');
  $_SESSION['$FlashMessages']->show('rows_updated');
?>
<div>
  <h3>Системные полномочия:</h3>
  <form method="post">
    <input type="button" value="Добавить" id="Add_row">
      <table id="TableRows">
        <tr class="tr_heading">
          <th>ID</th>
          <th class="Hide_Column">mark_del</th>
          <th class="Hide_Column">mark_edit</th>
          <th>Полномочие</th>
          <th class="Hide_Column">priv_tp_id</th>
          <th>Тип</th>
          <th>Описание</th>
          <th>Удалить</th>
        </tr>
        <?php
        for ($i = 0; $i < $Rows_num; $i++) {
          echo('<tr><td class="td_id">'.$tb_contents[$i]['priv_id'].'</td>');
          echo('<td class="mark_del Hide_Column">0</td>');
          echo('<td class="mark_edit Hide_Column">0</td>');
          echo('<td class="td_Editable">'.$tb_contents[$i]['priv_name'].'</td>');
          echo('<td class="Hide_Column">'.$tb_contents[$i]['priv_tp_id'].'</td>');
          echo('<td class="td_Selectable">'.$tb_contents[$i]['priv_tp_name'].'</td>');
          echo('<td class="td_Editable">'.$tb_contents[$i]['priv_description'].'</td>');
          echo('<td align="center"><input type="checkbox" class="del_Checkbox"></td></tr>');
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

    //Вводим данные о структуре таблицы #TableRows
    //Обязательные колонки
    $('#TableRows').data('mandatory_columns',[3,5,6]);
    //Индекс колонки пометки на удаление
    $('#TableRows').data('mark_del_column',1);

    //Данные первой колонки с выпадающими списками
    $('#TableRows tr td:nth-child(6)').data('select_provider_table_name','Privilege_types')
    $('#TableRows tr td:nth-child(6)').data('select_provider_column_name','priv_tp_name')
    $('#TableRows tr td:nth-child(6)').data('select_provider_column_id','priv_tp_id')

    //Обработка добавления строки
    $('#Add_row').on('click',function() {
      $('#TableRows').append( '<tr><td class="td_id"></td>'+
                              '<td class="mark_del Hide_Column">0</td>'+
                              '<td class="mark_edit Hide_Column">0</td>'+
                              '<td class="td_Editable"></td>'+
                              '<td class="Hide_Column"></td>'+
                              '<td class="td_Selectable"'+
                                  //Для выпадающего меню - добавляем данные в тег
                                  'data-select_provider_table_name = "Privilege_types"'+
                                  'data-select_provider_column_name = "priv_tp_name"'+
                                  'data-select_provider_column_id = "priv_tp_id"></td>'+
                              '<td class="td_Editable"></td>'+
                              '<td align="center"><input type="checkbox" class="del_Checkbox"></td></tr>');

      //подсвечиваем незаполненные
      check_table_notempty ('TableRows');


    });


// Обрабатываем клик на кнопку сохранения
    $('#Save_btn').on('click',function() {

// Работаем только если заполнены все обязательные поля
    if (check_table_notempty ('TableRows', [3,5,6], 1) == true){

      //Включаем индикатор загрузки
      $('#load_spin').removeClass('hidden');

      //Забираем данные в массив tbl
      var tbl = $('table#TableRows tr:not(.tr_heading)').get().map(function(row) {
        return $(row).find('td').get().map(function(cell) {
          return $(cell).html();
        });
      });

//Делим tbl на 3 массива:
      //Удаляемые строки (проставлена отметка на удаление, есть id)
      var arr_for_deletion = [];
      //Обновляемые строки (не проставлена отметка на удаление, есть id)
      var arr_for_merge = [];
      //Добавляемые строки (не проставлена отметка на удаление, нет id)
      var arr_for_adding = [];

      var len = $('#TableRows').find('tr').length;

       for (i = 0; i < len-1; i++) {
          if ((Number(tbl[i][1]) == 1) && (tbl[i][0] !== '')) {
            var del_element = {};
            del_element.priv_id = tbl[i][0];
            del_element.priv_name = tbl[i][3];
            arr_for_deletion.push(del_element);
          }
          else if ((Number(tbl[i][1]) == 0) && (tbl[i][0] !== '') && (Number(tbl[i][2]) == 1)) {
            var merge_element = {};
            merge_element.priv_id = tbl[i][0];
            merge_element.priv_name = tbl[i][3];
            merge_element.priv_description = tbl[i][6];
            merge_element.priv_tp_id = tbl[i][4];
            arr_for_merge.push(merge_element);
          }
          else if ((Number(tbl[i][1]) == 0) && (tbl[i][0] == '')) {
            var add_element = {};
            add_element.priv_name = tbl[i][3];
            add_element.priv_description = tbl[i][6];
            add_element.priv_tp_id = tbl[i][4];
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

//Если есть элементы в массиве на удаление - предупреждаем о последствиях удаления
        if (arr_for_deletion.length > 0) {
          // Формируем предупреждение пользователю
          var del_warn = 'Будут удалены следующие полномочия:\n';
          for (var i = 0; i < arr_for_deletion.length; i++) {
              del_warn = del_warn +'  \u2022 '+arr_for_deletion[i]['priv_id']+' - '+arr_for_deletion[i]['priv_name']+'\n';
              delete arr_for_deletion[i]['priv_name'];
          }

        //Не выводим сообщение о связанных элементах т.к. связанные элементы относятся к промежуточной таблице - пользователь о них не знает

        //Выводим сообщение пользователю на подтверждение
        if (confirm(del_warn)) {
          $.ajax({
              url: "table_writer.php",
              type: "POST",
              data: JSON.stringify({table:'privileges', rows_del:arr_for_deletion, rows_merge:arr_for_merge, rows_add:arr_for_adding}),
              contentType: "application/json"

            }).always(function (data) {
              window.location.reload(false);
            });
          }

        }
        // Если массив на удаление пустой - просто вносим изменения
        else {
          $.ajax({
              url: "table_writer.php",
              type: "POST",
              data: JSON.stringify({table:'privileges', rows_del:arr_for_deletion, rows_merge:arr_for_merge, rows_add:arr_for_adding}),
              contentType: "application/json"

            }).always(function (data) {
              window.location.reload(false);
            });
        }
        //Выключаем индикатор загрузки
        $('#load_spin').attr('class','hidden');
      //Окончание проверки на ввод обязательных полей
      }
      //Окончание обработки кнопки сохранения
      });
    //Окончание скрипта по document.ready
    });

</script>

</html>
