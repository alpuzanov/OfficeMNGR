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
