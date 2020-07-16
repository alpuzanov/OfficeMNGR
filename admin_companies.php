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
                              	  comp_id,
                                  comp_name,
                                  comp_description
                              FROM
                              	companies
                              ");

//Считаем сколько записано строк
$Rows_num = $tb_contents -> rowCount();

//Забираем все строки
$tb_contents = $tb_contents -> fetchall();

?>

<!doctype html>
<html>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>


<script src = "MyUtil.js"></script>
  <head>
    <!-- Стили -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

    <link type="text/css" rel="stylesheet" href="MyStyle.css">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Office Manager - Manage Companies</title>
  </head>

<body id="PageBody">
<?php
  require_once "NavBar.php";
  $_SESSION['$FlashMessages']->show('bad_table_name');
  $_SESSION['$FlashMessages']->show('rows_added');
  $_SESSION['$FlashMessages']->show('rows_deleted');
  $_SESSION['$FlashMessages']->show('rows_updated');
?>
<div class="container py-4">
  <h4 class="py-2">Компании-партнеры:</h4>
  <form method="post">
    <div class="btn-group py-2">
      <input type="button" class="btn btn-secondary" value="Добавить" id="Add_row">
      <input type="button" class="btn btn-dark" value="Сохранить" id="Save_btn">
    </div>

      <table id="TableRows" class="table table-striped">
        <thead>
          <tr class="tr_heading">
            <th scope="col" style="width: 3%">ID</th>
            <th scope="col" class="Hide_Column">mark_del</th>
            <th scope="col" class="Hide_Column">mark_edit</th>
            <th scope="col" style="width: 46%">Компания</th>
            <th scope="col" style="width: 46%">Описание</th>
            <th scope="col" style="width: 5%">Удалить</th>
          </tr>
        </thead>
        <tbody>
          <?php
          for ($i = 0; $i < $Rows_num; $i++) {
            echo('<tr><th scope="row" class="td_id">'.$tb_contents[$i]['comp_id'].'</th>');
            echo('<td class="mark_del Hide_Column">0</td>');
            echo('<td class="mark_edit Hide_Column">0</td>');
            echo('<td class="td_Editable">'.$tb_contents[$i]['comp_name'].'</td>');
            echo('<td class="td_Editable">'.$tb_contents[$i]['comp_description'].'</td>');
            echo('<td align="center"><input type="checkbox" class="del_Checkbox"></td></tr>');
          }
          ?>
        </tbody>
      </table>


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
    $('#TableRows').data('mandatory_columns',[3,4]);
    //Индекс колонки пометки на удаление
    $('#TableRows').data('mark_del_column',1);

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
        if (check_table_notempty ('TableRows') == true){

          //Включаем индикатор загрузки
          $('#load_spin').removeClass('hidden');

            //Забираем данные в массив tbl
            var tbl = $('table#TableRows tr:not(.tr_heading)').get().map(function(row) {
              return $(row).find('td, th').get().map(function(cell) {
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

        console.log(tbl);

        for (i = 0; i < len-1; i++) {
          if ((Number(tbl[i][1]) == 1) && (tbl[i][0] !== '')) {
            var del_element = {};
            del_element.comp_id = tbl[i][0];
            del_element.comp_name = tbl[i][3];
            arr_for_deletion.push(del_element);
          }
          else if ((Number(tbl[i][1]) == 0) && (tbl[i][0] !== '') && (Number(tbl[i][2]) == 1)) {
            var merge_element = {};
            merge_element.comp_id = tbl[i][0];
            merge_element.comp_name = tbl[i][3];
            merge_element.comp_description = tbl[i][4];
            arr_for_merge.push(merge_element);
          }
          else if ((Number(tbl[i][1]) == 0) && (tbl[i][0] == '')) {
            var add_element = {};
            add_element.comp_name = tbl[i][3];
            add_element.comp_description = tbl[i][4];
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
          var del_warn = '!!!ВНИМАНИЕ!!!\nБудут удалены следующие Компании:\n';
          for (var i = 0; i < arr_for_deletion.length; i++) {
              del_warn = del_warn +'  \u2022 '+arr_for_deletion[i]['comp_id']+' - '+arr_for_deletion[i]['comp_name']+'\n';
              delete arr_for_deletion[i]['comp_name'];
          }

          //!!!!! (Возможно переделать) Не выводим сообщение о связанных элементах т.к. связанных таблиц много - просто напоминаем об опасности
          del_warn = del_warn +'\n!!!ВНИМАНИЕ!!!\nУдаление компаний приведет к удалению ВСЕЙ связанной информации\nПодтверждая действие вы должны давать себе отчет на что это повлияет!';
            //Выводим сообщение пользователю на подтверждение
            if (confirm(del_warn)) {
              $.ajax({
                  url: "table_writer.php",
                  type: "POST",
                  data: JSON.stringify({table:'companies', rows_del:arr_for_deletion, rows_merge:arr_for_merge, rows_add:arr_for_adding}),
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
              data: JSON.stringify({table:'companies', rows_del:arr_for_deletion, rows_merge:arr_for_merge, rows_add:arr_for_adding}),
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
