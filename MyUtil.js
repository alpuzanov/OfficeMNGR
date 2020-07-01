/* Функция ValidateEmailPass проверяет Пароль и Email на корректность заполнения
id_pass - id поля с паролем
id_email - id поля с email */
function ValidateEmailPass(id_pass, id_email) {
    try {
      var pw = document.getElementById(id_pass).value;
      var email = document.getElementById(id_email).value;
      if (pw == null || pw == "" || email == null || email == "")
        {
          alert('Поля "E-mail" и "Пароль" не должны быть пустыми');
          return false;
        }
      else if (email.indexOf("@")<0 || email.indexOf(".", email.indexOf("@")) <= 1)
      {
        alert('Неверный формат E-mail');
        return false;
      }
      return true;
    }
    catch (e) {
      return false;
    }
  return false;
}


/* Функция check_table_notempty устанавливает класс "bad_data" для всех пустых ячеек указанной таблицы кроме строк с установленной пометкой на удаление (снимает, для не пустых)
  Также функция проставляет "bad_data" ячеек с нечисловыми значениями, для ечеек с классом ".td_Numeric"

ПАРАМЕТРЫ:
  table - строка, id проверяемой таблицы, например 'TableRows'
  cols_to_check - массив колонок, которые должны быть не пустыми, например [1,4]
  mark_del_column - индекс колонки с пометкой на удаление

ВОЗВРАЩАЕТ:
  true, если все обязательные поля заполнены, нет ошибок в числовых
  false, если есть хоть одно незаполненное обязательное поле или хотябы одна ошибка в числовых ячейках
*/
  function check_table_notempty (table, cols_to_check, mark_del_column){
    if (typeof(mark_del_column)==='undefined') mark_del_column = 'NONE';
    var len = $('#'+table).find('tr').length;
    var num_empty = 0;

    $('#'+table).find('.bad_data').removeClass('bad_data');

    for (i=1; i<len; i++){

      for (j=0; j < cols_to_check.length; j++){
        if (mark_del_column === 'NONE') {

          //Если не передан индекс колонки на удаление
          if (document.getElementById(table).rows[i].cells[cols_to_check[j]].innerHTML=='')
          {
            document.getElementById(table).rows[i].cells[cols_to_check[j]].classList.add('bad_data');
            num_empty = num_empty+1;
          }
        } else {
          //Если передан индекс колонки на удаление
          if (document.getElementById(table).rows[i].cells[cols_to_check[j]].innerHTML=='' && document.getElementById(table).rows[i].cells[mark_del_column].innerHTML != 1)
          {
            document.getElementById(table).rows[i].cells[cols_to_check[j]].classList.add('bad_data');
            num_empty = num_empty+1;
          }
        }
      }

    }

    var num_nonnumeric = 0;
    $('.td_Numeric').each(function (num_element) {
      if (!$.isNumeric($(this).html())) {
        $(this).addClass('bad_data');
        num_nonnumeric = num_nonnumeric+1;
      }
    });

    if ( (num_empty + num_nonnumeric) != 0) return false
    else return true;
  }


  function mark_tree_openers (table, id_column, tree_column, mother_key){

    var tb_len = $('#'+table).find('tr').length;
    for (i=1; i<tb_len; i++)
    {
        var row_id = document.getElementById('TableRows').rows[i].cells[id_column].innerHTML;
        var row_children = organisation.filter(obj => {
            return obj[mother_key] == row_id;
        })
        if (row_children.length > 0 && document.getElementById('TableRows').rows[i].cells[tree_column].classList.contains('tree_new')) {
            document.getElementById('TableRows').rows[i].cells[tree_column].innerHTML='<a href="#">+</a>';
            document.getElementById('TableRows').rows[i].cells[tree_column].classList.add('tree_closed');
            document.getElementById('TableRows').rows[i].cells[tree_column].classList.remove('tree_new');
        }
    }
  }


  // Функция удаляет строки в таблице table, с id (колонка id_column) входящими в массив MasToDel
    function delete_rows(table, id_column, MasToDel) {

      var tb_len = $('#'+table).find('tr').length;
      var rows_for_del = new Array;

      for (i=1; i<tb_len; i++)
        {
          var row_id = Number(document.getElementById('TableRows').rows[i].cells[id_column].innerHTML);
          if( MasToDel.indexOf(row_id) !== -1) {
            rows_for_del.push(document.getElementById('TableRows').rows[i]);
          }

        }

      rows_for_del.forEach(function (row) {
        row.remove();
      });

    }



    $(document).ready(function()
    {

    //Набор функций, обеспечивающих работу с редактируемыми и выпадающими элементами таблиц

      //По клику меняем содержание ячейки с классом td_Editable на поле ввода с содержимым ячейки
      $('#PageBody').on('click', '.td_Editable',function() {
        contentBeforeEdit = $(this).text();
        $(this).removeClass('td_Editable');
        $(this).html('<input type="text" value="'+contentBeforeEdit+'" class="inputActive">');
        $(this).children('.inputActive').select();
      });

      //По клику меняем содержание ячейки с классом td_Selectable на лист выбора с содержимым ячейки
      $('#PageBody').on('click', '.td_Selectable', function() {
          contentBeforeEdit = Number($(this).prev().text());

          $(this).removeClass('td_Selectable');
          $(this).html('<select class="inputActive"></select>');
          var response = $.getJSON("table_reader.php", {table: 'Privilege_types'}, function(){})
          .done(function() {
              var len = response.responseJSON.length;
              for (i = 0; i < len; i++) {
                  $('.inputActive').append('<option value="'+response.responseJSON[i]['priv_tp_id']+'">'+response.responseJSON[i]['priv_tp_name']+'</option>');
                }
              $('.inputActive').val(contentBeforeEdit);
              $('.inputActive').focus();
          })
      });

      // //В момент выхода из редактирования Input или Select - заменяем поле ввода/выпадающий список на текст с содержимым элемента
      // $('#PageBody').on('blur', '.inputActive', function(){
      //   $(this).removeClass('inputActive');
      //
      //   //Устанавливаем необходимый класс в зависимости от типа элемента
      //   if ($(this).is('select')){
      //     var contentAfterEdit = $('option:selected',this).text();
      //     $(this).parent().attr('class','td_Selectable');
      //     // устанавливаем необходимое значение ID из связанной таблицы в соотв-е поля
      //     $(this).parent().prev().html($(this).val());
      //     // устанавливаем флаг mark_edit на строке, если было изменение в значении
      //     if ($(this).val() != contentBeforeEdit) {
      //       $(this).closest('tr').children('.mark_edit').html(1);
      //     }
      //   }
      //   else if ($(this).is('input')) {
      //     var contentAfterEdit = $(this).get(0).value;
      //       $(this).parent().attr('class','td_Editable');
      //       // устанавливаем флаг mark_edit на строке, если было изменение в значении
      //       if (contentAfterEdit != contentBeforeEdit) {
      //         $(this).closest('tr').children('.mark_edit').html(1);
      //       }
      //   }
      //
      //   var TableId = $(this).closest('table').get(0).id;
      //
      //   //Устанавливаем выбранное/введенное значение
      //   $(this).parent().html(contentAfterEdit);
      //
      //   //подсвечиваем незаполненные
      //   check_table_notempty (TableId, mandatory_columns, mark_del_column);
      // });

      //По выбору checkbox'а об удалении - проставляем в колонку с классом "mark_del" пометку на удаление, по снятию - убираем
      $('#PageBody').on('change', '.del_Checkbox',function() {
        var mark_del = Number($(this).closest('tr').children('.mark_del').html());
        mark_del = (mark_del+1) % 2;

        $(this).closest('tr').toggleClass('mark_del');
        $(this).closest('tr').children('.mark_del').html(mark_del);

        //Подсвечиваем незаполненные
        check_table_notempty ($(this).closest('table').get(0).id, mandatory_columns, mark_del_column);
      });

    });
