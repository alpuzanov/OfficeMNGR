<?php
require_once "pdo.php";
require_once "MyUtil.php";

// Проверяем то, что пользователь залогинен и его права
if (!isset($_SESSION['user_id'])) {
  $_SESSION['$FlashMessages']->set('user_not_logged_in', 'warning', 'Пожалуйста представьтесь');
  header("Location: login.php");
  return;
}

//Читаем данные из таблицы PRIVILEGE_TYPES
$tb_contents = $pdo -> query("
                              SELECT
                              	  priv_tp_id,
                                  priv_tp_name,
                                  priv_tp_description
                              FROM
                              	privilege_types
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
    <title>Office Manager - Edit privilige types</title>
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
  <h3>Типы системных полномочий:</h3>
  <form method="post">
    <input type="button" value="Добавить" id="Add_row">
      <table id="TableRows">
        <tr class="tr_heading">
          <th>ID</th>
          <th class="Hide_Column">mark_del</th>
          <th class="Hide_Column">mark_edit</th>
          <th>Тип полномочия</th>
          <th>Описание</th>
          <th>Удалить</th>
        </tr>
        <?php
        for ($i = 0; $i < $Rows_num; $i++) {
          echo('<tr><td class="td_id">'.$tb_contents[$i]['priv_tp_id'].'</td>');
          echo('<td class="mark_del Hide_Column">0</td>');
          echo('<td class="mark_edit Hide_Column">0</td>');
          echo('<td class="td_Editable">'.$tb_contents[$i]['priv_tp_name'].'</td>');
          echo('<td class="td_Editable">'.$tb_contents[$i]['priv_tp_description'].'</td>');
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

    //Записываем данные о таблицах

    $('#TableRows').data('mandatory_columns',[3,4]);
    $('#TableRows').data('mark_del_column',1);


    mandatory_columns = [3,4];
    mark_del_column = 1;


    //В момент выхода из редактирования - заменяем поле ввода/выпадающий список на текст с содержимым элемента
    $('#PageBody').on('blur', '.inputActive', function(){

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


      var TableId = $(this).closest('table').get(0).id;
      console.log(TableId);

      //Устанавливаем выбранное/введенное значение
      $(this).parent().html(contentAfterEdit);

      console.log($("#"+TableId).data("mandatory_columns"));

      //подсвечиваем незаполненные
      check_table_notempty (TableId, mandatory_columns, mark_del_column);
    });

    //Добавляем строку
    $('#Add_row').on('click',function() {
      $('#TableRows').append( '<tr><td class="td_id"></td>'+
                              '<td class="mark_del Hide_Column">0</td>'+
                              '<td class="mark_edit Hide_Column">0</td>'+
                              '<td class="td_Editable"></td>'+
                              '<td class="td_Editable"></td>'+
                              '<td align="center"><input type="checkbox" class="del_Checkbox"></td></tr>');
      //подсвечиваем незаполненные
      check_table_notempty ('TableRows', [3,4], 1);
    });


// Обрабатываем клик на кнопку сохранения
    $('#Save_btn').on('click',function() {

// Работаем только если заполнены все обязательные поля
        if (check_table_notempty ('TableRows', [3,4], 1) == true){

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
        //Обновляемые строки (не проставлена отметка на удаление, проставлена отметка изменений, есть id)
        var arr_for_merge = [];
        //Добавляемые строки (не проставлена отметка на удаление, нет id)
        var arr_for_adding = [];

        var len = $('#TableRows').find('tr').length;

        for (i = 0; i < len-1; i++) {
          if ((Number(tbl[i][1]) == 1) && (tbl[i][0] !== '')) {
            var del_element = {};
            del_element.priv_tp_id = tbl[i][0];
            del_element.priv_tp_name = tbl[i][3];
            arr_for_deletion.push(del_element);
          }
          else if ((Number(tbl[i][1]) == 0) && (tbl[i][0] !== '') && (Number(tbl[i][2]) == 1)) {
            var merge_element = {};
            merge_element.priv_tp_id = tbl[i][0];
            merge_element.priv_tp_name = tbl[i][3];
            merge_element.priv_tp_description = tbl[i][4];
            arr_for_merge.push(merge_element);
          }
          else if ((Number(tbl[i][1]) == 0) && (tbl[i][0] == '')) {
            var add_element = {};
            add_element.priv_tp_name = tbl[i][3];
            add_element.priv_tp_description = tbl[i][4];
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

//Если есть элементы в массиве на удаление - предупреждаем о последствиях удаления (запрашиваем строки в таблице Privileges, к которым привязаны удаляемые типы)
        if (arr_for_deletion.length > 0) {
          // Формируем предупреждение пользователю
          var del_warn = 'Будут удалены следующие типы полномочий:\n';
          for (var i = 0; i < arr_for_deletion.length; i++) {
              del_warn = del_warn +'  \u2022 '+arr_for_deletion[i]['priv_tp_id']+' - '+arr_for_deletion[i]['priv_tp_name']+'\n';
              delete arr_for_deletion[i]['priv_tp_name'];
          }

          //Запрашиваем связанную таблицу Privileges, фильтруем ответ по тем элементам, которые связаны с элементами из массива на удаление
          var response = $.getJSON("table_reader.php", {table: 'Privileges'}, function(){})
            .done(function() {
              var contents = response.responseJSON;
              var arr_for_deletion_flat = arr_for_deletion.map(function (e) {return e.priv_tp_id});
              var contents_filtered = contents.filter(function(obj){
                if (arr_for_deletion_flat.indexOf(obj.priv_tp_id) != -1) {return true}
              });

            //Если есть связанные элементы, которые будут удалены - добавляем их в сообщение пользователю
            if (contents_filtered.length > 0) {
              del_warn = del_warn +'Также будут удалены связанные полномочия:\n';
              for (var i = 0; i < contents_filtered.length; i++) {
                  del_warn = del_warn+'  \u2022 '+contents_filtered[i]['priv_id']+' - '+contents_filtered[i]['priv_name']+'\n';
              }
            }

            //Выводим сообщение пользователю на подтверждение
            if (confirm(del_warn)) {
              $.ajax({
                  url: "table_writer.php",
                  type: "POST",
                  data: JSON.stringify({table:'privilege_types', rows_del:arr_for_deletion, rows_merge:arr_for_merge, rows_add:arr_for_adding}),
                  contentType: "application/json"

              }).always(function (data) {
                  window.location.reload(false);
                });
            }
          })

        }
        // Если массив на удаление пустой - просто вносим изменения
        else {
          $.ajax({
              url: "table_writer.php",
              type: "POST",
              data: JSON.stringify({table:'privilege_types', rows_del:arr_for_deletion, rows_merge:arr_for_merge, rows_add:arr_for_adding}),
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
