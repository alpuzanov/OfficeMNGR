<?php
require_once "pdo.php";
require_once "MyUtil.php";


// Проверяем то, что пользователь залогинен и его права
if (!isset($_SESSION['user_id'])) {
  $_SESSION['$FlashMessages']->set('user_not_logged_in', 'warning', 'Пожалуйста представьтесь');
  header("Location: login.php");
  return;
}

// По кнопке CANCEL перенаправляем на index.php
if ( isset($_POST['Cancel']) ) {
    header('Location: index.php');
    return;
}

// Проверяем то, что передан ID этажа
if (!isset($_GET['floor_id']) || !is_numeric($_GET['floor_id'])) {
  $_SESSION['$FlashMessages']->set('no_floor_id', 'error', 'Попытка просмотра этажа с некорректным (или без) floor_id');
  header("Location: index.php");
  return;
}
else {
    $floor_id = $_GET['floor_id'];
}

//Читаем данные об этаже
$sql = "SELECT
          f.floor_id,
          f.office_id,
        	o.office_name,
        	o.office_description,
        	f.floor_name,
        	f.floor_description,
        	f.floor_map,
        	f.floor_x_max,
        	f.floor_y_max
        from floors as f
        left join offices as o on f.office_id = o.office_id
        where f.floor_id = :fid
        ";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':fid' => $floor_id));

//Считаем сколько записано строк
$Rows_num = $stmt -> rowCount();

//Если нет строк, значит нет такого этажа - выдаем ошибку
if ($Rows_num == 0) {
  $_SESSION['$FlashMessages']->set('no_floor_id', 'error', 'Попытка просмотра этажа с некорректным (или без) floor_id');
  header("Location: index.php");
  return;
}

//Забираем все строки
$tb_Floor_contents = $stmt -> fetchall(PDO::FETCH_ASSOC);


//Читаем данные о разметке этажа
$sql = "SELECT
        	z.zone_id,
        	z.zone_name,
        	z.zone_description,
        	a.asset_id,
        	a.asset_type,
        	at.at_name,
        	at.at_description,
        	a.asset_name,
        	a.asset_description,
        	a.asset_capacity,
        	a.asset_on_map,
        	a.coordinate_x,
        	a.coordinate_y
        from floors as f
        left join zones as z on z.floor_id = f.floor_id
        left join assets as a on a.zone_id = z.zone_id
        left join asset_types as at on a.asset_type = at.at_id
        where f.floor_id = :fid
        ";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':fid' => $floor_id));

//Считаем сколько записано строк
$Rows2_num = $stmt -> rowCount();

//Забираем все строки
$tb_Assets_contents = $stmt -> fetchall(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/js/bootstrap.min.js" integrity="sha384-oesi62hOLfzrys4LxRF63OJCXdXDipiYWBnvTl9Y9/TRlw5xlKIEHpNyvvDShgf/" crossorigin="anonymous"></script>
<link type="text/css" rel="stylesheet" href="MyStyle.css">
<script src = "MyUtil.js"></script>

<html>
  <head>
    <title>Office Manager - Floor view</title>
  </head>

  <body id="PageBody">
    <?php
   //  echo('<pre>');
   // var_dump($tb_Floor_contents);
   //  echo('</pre>');
    $var ?>
    <div>
      <h4>Просмотр этажа:</h4>
      <label>ID:</label>
      <p id="fid"><?php echo($tb_Floor_contents[0]['floor_id']) ?></p>
      <label>Этаж:</label>
      <p><?php echo ($tb_Floor_contents[0]['floor_name'])?></p>
      <label>Офис:</label>
      <p><?php echo ($tb_Floor_contents[0]['office_id'].': '.$tb_Floor_contents[0]['office_name']) ?></p>
      <label>Описание:</label>
      <p><?php echo ($tb_Floor_contents[0]['office_description']) ?></p>

      <form method="post">
        <h4>План этажа:</h4>
          <div style="position: relative">
              <img id="FloorPlan" style="position: absolute; opacity:0;" class="FloorPlanImage" src="data:image;charset=utf8;base64,<?php echo(base64_encode($tb_Floor_contents[0]['floor_map'])); ?>" width = "1500" usemap="#Assets" alt="План этажа"/>
              <map id="FloorPlanMap" name="Assets">
                 <?php
                 for ($i = 0; $i < $Rows2_num; $i++) {
                   echo('<area class="asset" shape="circle" coords="'.$tb_Assets_contents[$i]['coordinate_x'].','.$tb_Assets_contents[$i]['coordinate_y'].',10" href="#" >');
                 }
                 ?>
              </map>
              <canvas id="FloorPlanCanvas" "position: relative;" width="1500" usemap="#Assets"></canvas>
            </div>
          </div>
        <input type="button" value="Сохранить" id="Save_btn">
        <input type="submit" name="Cancel" value="Отмена">
        <!-- <img id="load_spin" class="hidden" src="images/load.gif" height = "20" alt="Loading..."> -->
      </form>
    </div>
  </body>

<script type="text/javascript">


  $(document).ready(function myReady ()
  {


    var canvas = document.getElementById('FloorPlanCanvas');
    var background = document.getElementById("FloorPlan");
    var ctx = canvas.getContext('2d');
    canvas.width = background.width;
    canvas.height = background.height;
    ctx.drawImage(background, 0, 0, background.width, background.height);


    function drawAsset(x_coord, y_coord, style){
      style.prop1
      ctx.beginPath()
      ctx.fillStyle = style.fillStyle;
      ctx.arc(x_coord, y_coord, 10, 0, Math.PI * 2, true);
      ctx.stroke();
      ctx.fill();
      ctx.closePath();
    }

    $('area').each(function(){
      var coords = $(this).attr('coords');
      var x = parseInt(coords.slice(0,coords.indexOf(',')));
      var y = parseInt(coords.slice(coords.indexOf(',')+1,coords.lastIndexOf(',')));
      drawAsset(x,y,{fillStyle:'rgba(9, 237, 41, 0.5)'});
    });

    $('#FloorPlan').click(function(e){
      var posX = Math.floor(e.pageX - $(this).offset().left);
      var posY = Math.floor(e.pageY - $(this).offset().top);
      $('#FloorPlanMap').append('<area class="NewAsset" shape="circle" coords="'+posX+','+posY+',10" href="#" data-maphilight='+'\'{"fillColor":"1180ca"}\'>');
      drawAsset(posX,posY,{fillStyle:'rgba(11, 133, 214, 0.5)'});
    });

    //     // Обрабатываем клик на кнопку сохранения
        $('#Save_btn').on('click',function() {

          // var newAssets =
          console.log($('.NewAsset').get(0).coords);
          //console.log($('.NewAsset'));


        //Окончание скрипта по кнопке Сохранить
        });
  //Окончание скрипта по document.ready
  });

</script>
</html>
