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
      <li><a href="">Контакты</a></li>
      <li><?php echo ($_SESSION['username']);?></li>
      <div>
        <ul>
          <li>Компания: <?php echo (reset($_SESSION['company'])) ?></li>
          <li><a href="logout.php">Выйти</a></li>
        </ul>
      </div>
  </ul>
</nav>
