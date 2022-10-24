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
    <title>Тестовое задание | Регистрация</title>
    <link href="../css/style.css" rel="stylesheet">

</head>

<body class="gray-bg">

<div class="middle-box loginscreen">
    <div>
        <h1 class="logo-name">Тестовое задание</h1>
    </div>
    <p>Страница регистрации</p>
    <div class="<?php if (!empty($_SESSION['flash'])) {if ($_SESSION['flash'][1]) echo "green"; else echo "red";}?>">
        <?php if (!empty($_SESSION['flash'])) echo $_SESSION['flash'][0];?>
    </div>
    <form class="mainform" role="form" method="POST" action="?mode=do_reg">
        <div class="form-group">
            <input type="email" name="email" class="form-control" placeholder="Email" required="">
        </div>
        <div class="form-group">
            <input type="password" name="password" class="form-control" placeholder="Пароль" required="">
        </div>
        <div class="form-group">
            <input type="password" name="password_2" class="form-control" placeholder="Повторите пароль" required="">
        </div>
        <button type="submit" name="do_reg" class="btn btn-primary block full-width">Создать аккаунт</button>
        <a class="btn btn-white block full-width pad15" href="/">Вернуться на главную</a>

    </form>
</div>

</body>

</html>
