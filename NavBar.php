<?php
//Читаем данные о доступных пользователю компаниях
$sql = "SELECT DISTINCT
          ap.comp_id,
          c.comp_name
        FROM assigned_privileges AS ap
        LEFT JOIN companies AS c ON ap.comp_id = c.comp_id
        WHERE ap.user_id = :uid AND ap.comp_id IS NOT NULL";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':uid' => $_SESSION['user_id']));

//Считаем сколько записано строк
$AvailComp_Rows_num = $stmt -> rowCount();

//Забираем все строки
$tb_AvailCompanies_contents = $stmt -> fetchall(PDO::FETCH_ASSOC);

?>



<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand py-2" href="#">
    <img src="assets/brand/avocado.png" width="53" height="53" class="d-inline-block"  alt="" loading="lazy">
    Office
  </a>
  <button class="navbar-toggler px-2 mx-2" type="button" data-toggle="collapse" data-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="flex-wrap justify-content-between collapse navbar-collapse m-1" id="navbarMain">
    <ul class="navbar-nav">

      <li class="nav-item">
        <a class="nav-link" aria-current="page" href="index.php">Бронирования</a>
      </li>


      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="NavDropdown01" data-toggle="dropdown" aria-expanded="false">Офис</a>
        <ul class="dropdown-menu" aria-labelledby="NavDropdown01">
          <li><a class="dropdown-item" href="#">Офисы</a></li>
          <li><a class="dropdown-item" href="#">Активы</a></li>
          <li><a class="dropdown-item" href="#">Разрешения</a></li>
        </ul>
      </li>


      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="NavDropdown02" data-toggle="dropdown" aria-expanded="false">Администрирование</a>
        <ul class="dropdown-menu" aria-labelledby="NavDropdown02">
          <li><a class="dropdown-item" href="admin_companies.php">Компании</a></li>
          <li><a class="dropdown-item" href="admin_users.php">Пользователи</a></li>
          <li><a class="dropdown-item" href="admin_priv.php">Полномочия</a></li>
          <li><a class="dropdown-item" href="admin_priv_tp.php">Типы полномочий</a></li>
        </ul>
      </li>

      </ul>

      <ul class="navbar-nav align-items-lg-center">
        <li id="CompanyDropdown" class="nav-item dropdown" style="display: none;">
          <a class="nav-link dropdown-toggle" id="NavDropdown03" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#">
            <svg width="45" height="45" viewBox="-4.5 -4.5 25 25" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
              <circle cx="8" cy="8" r="12" fill="none" />
              <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022zM6 8.694L1 10.36V15h5V8.694zM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15z"/>
              <path d="M2 11h1v1H2v-1zm2 0h1v1H4v-1zm-2 2h1v1H2v-1zm2 0h1v1H4v-1zm4-4h1v1H8V9zm2 0h1v1h-1V9zm-2 2h1v1H8v-1zm2 0h1v1h-1v-1zm2-2h1v1h-1V9zm0 2h1v1h-1v-1zM8 7h1v1H8V7zm2 0h1v1h-1V7zm2 0h1v1h-1V7zM8 5h1v1H8V5zm2 0h1v1h-1V5zm2 0h1v1h-1V5zm0-2h1v1h-1V3z"/>
            </svg>
            <?php echo(current($_SESSION['company']))?>
          </a>
          <ul id="CompanySelect" class="dropdown-menu dropdown-menu-right" aria-labelledby="NavDropdown03">
            <?php
            for ($i = 0; $i < $AvailComp_Rows_num; $i++) {
              echo('<li><a class="dropdown-item" href="#" data-CompId="'.$tb_AvailCompanies_contents[$i]['comp_id'].'">'.$tb_AvailCompanies_contents[$i]['comp_name'].'</a></li>');
            }
            ?>
        </ul>
      </li>

      <li class="nav-item dropdown" >
        <a class="nav-link dropdown-toggle" id="NavDropdown04" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#">
          <img src="assets/pics/avatar_AP.jpg" class="rounded-circle" alt="avatar" width="45" height="45">
          <?php echo ($_SESSION['username']);?>
        </a>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="NavDropdown03">
          <li><a class="dropdown-item" href="#">Настройки</a></li>
          <li><a class="dropdown-item" href="logout.php">Выйти</a></li>
        </ul>
      </li>
      </ul>
    </div>
  </nav>



<script type="text/javascript">

  $(document).ready(function()
  {
    //Показываем выбор компании только в том случе, если у пользователя есть доступ к 2м или более компаниям
    if ($('#CompanyDropdown').find('a').length > 2) {
      $('#CompanyDropdown').show();
    }

    // Записываем выбранную компанию на сервер в php SESSION
    $('#CompanySelect').on('click', 'a', function(){
          var comp_id = Number($(this).attr('data-CompId'));
          var comp_name = $(this).get(0).innerHTML;
          $.ajax({
              url: "change_company.php",
              type: "POST",
              data: JSON.stringify({comp_id:comp_id, comp_name:comp_name}),
              contentType: "application/json"
            }).always(function (data) {
              window.location.reload(false);
            });
    });
  });
</script>
