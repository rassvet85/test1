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
    <title>Тестовое задание | Логин</title>
    <link href="../css/style.css" rel="stylesheet">

</head>

<body class="white-bg">
    <div class="row border-bottom">
        <ul class="navbar navbar-right">
            <li>
                <span class="m-r-sm">Добро пожаловать, <?php echo $_SESSION['email']; ?></span>
            </li>
            <li>
                <a class="btn btn-white block" href="?mode=change_password">Изменить пароль</a>
            </li>
            <li>
                <a class="btn btn-red block" href="?mode=do_logout">Выход</a>
            </li>
        </ul>
        <div style="clear: both; padding-top: 10px;"></div>
    </div>
    <div class="row border-center">
        <div class="flash <?php if (!empty($_SESSION['flash'])) {if ($_SESSION['flash'][1]) echo "green"; else echo "red";}?>">
            <?php if (!empty($_SESSION['flash'])) echo $_SESSION['flash'][0];?>
        </div>
        <ul class="navbar navbar-left">
            <li>
                <div class="box">
                    <form method="POST" enctype="multipart/form-data"  action="?mode=do_file_send">
                        <input type="file" name="filename" id="file" class="inputfile" onchange="form.submit()"/>
                        <label for="file"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="17" viewBox="0 0 20 17"><path d="M10 0l-5.2 4.9h3.3v5.1h3.8v-5.1h3.3l-5.2-4.9zm9.3 11.5l-3.2-2.1h-2l3.4 2.6h-3.5c-.1 0-.2.1-.2.1l-.8 2.3h-6l-.8-2.2c-.1-.1-.1-.2-.2-.2h-3.6l3.4-2.6h-2l-3.2 2.1c-.4.3-.7 1-.6 1.5l.6 3.1c.1.5.7.9 1.2.9h16.3c.6 0 1.1-.4 1.3-.9l.6-3.1c.1-.5-.2-1.2-.7-1.5z"/></svg> <span>Импортировать XML</span></label>
                    </form>
                </div>
            </li>
        </ul>
    </div>
