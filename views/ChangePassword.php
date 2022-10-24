<?php
//Ключ защиты
if(!defined('SITE_KEY'))
{
    header("HTTP/1.1 404 Not Found");
    exit(file_get_contents('./404.html'));
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестовое задание | Изменение пароля</title>
    <link href="../css/style.css" rel="stylesheet">

</head>

<body class="gray-bg">

<div class="middle-box loginscreen">
    <div>
        <h1 class="logo-name">Тестовое задание</h1>
    </div>
    <p>Страница изменения пароля</p>
    <div class="<?php if (!empty($_SESSION['flash'])) {if ($_SESSION['flash'][1]) echo "green"; else echo "red";}?>">
        <?php if (!empty($_SESSION['flash'])) echo $_SESSION['flash'][0];?>
    </div>
    <form class="mainform" role="form" method="POST" action="?mode=do_change_password">
        <div class="form-group">
            <input type="password" name="password_old" class="form-control" placeholder="Существующий пароль" autocomplete="new-password" required="">
        </div>
        <div class="form-group">
            <input type="password" name="password" class="form-control" placeholder="Новый пароль" required="">
        </div>
        <div class="form-group">
            <input type="password" name="password_2" class="form-control" placeholder="Повторите новый пароль" required="">
        </div>
        <button type="submit" name="do_reg" class="btn btn-primary block full-width">Изменить пароль</button>
        <a class="btn btn-white block full-width pad15" href="/">Вернуться на главную</a>

    </form>
</div>

</body>

</html>

