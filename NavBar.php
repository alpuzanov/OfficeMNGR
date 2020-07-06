<nav>
  <ul>
      <li><a href="index.php">Мои бронирования</a></li>
      <li>Управление Офисом:
        <div>
          <ul>
            <li>Офисы</li>
            <li>Активы</li>
            <li>Разрешения</li>
          </ul>
        </div>
      </li>
      <li>Администрирование:
        <div>
          <ul>
            <li><a href="admin_companies.php">Компании</a></li>
            <li><a href="admin_users.php">Пользователи</a></li>
            <li><a href="admin_priv.php">Полномочия</a></li>
            <li><a href="admin_priv_tp.php">Типы полномочий</a></li>
          </ul>
        </div>
      </li>
      <li>Контакты</li>
      <li><?php echo ($_SESSION['username']);?></li>
      <div>
        <ul>
          <!-- Компания проставляется исходя из текущего значения в php SESSION, применяется таблица для использования стандартного механизма заполнения drop-down'а -->
          <li>Компания: <table class="No_style" id="CompTbl"><td class="Hide_Column"><?php echo(key($_SESSION['company']))?></td>
                        <td class="No_style td_Selectable"
                            data-select_provider_table_name = "companies"
                            data-select_provider_column_name = "comp_name"
                            data-select_provider_column_id = "comp_id"><?php echo (reset($_SESSION['company'])) ?></td></table></li>
          <li><a href="logout.php">Выйти</a></li>
        </ul>
      </div>
  </ul>
</nav>

<script type="text/javascript">
  $(document).ready(function()
  {

    $('#CompTbl').on('blur', '.inputActive', function(){
      // Записываем выбранную компанию в php SESSION, при этом ждем немного, чтобы успели отработать функции работы с drop-down'ами из myUtil.js
      setTimeout(function(){
          var comp_id = $('#CompTbl').find('td').get(0).innerHTML;
          var comp_name = $('#CompTbl').find('td').get(1).innerHTML;
          $.ajax({
              url: "change_company.php",
              type: "POST",
              data: JSON.stringify({comp_id:comp_id, comp_name:comp_name}),
              contentType: "application/json"
            }).always(function (data) {
              window.location.reload(false);
            });
      }, 50);
    });

  });
</script>
