<?php
//Ключ защиты
if(!defined('SITE_KEY'))
{
    header("HTTP/1.1 404 Not Found");
    exit(file_get_contents('./404.html'));
}
//Разрешаем только запросы к корню домена
if (isset($_SERVER['REQUEST_URI']) && !in_array($_SERVER['REQUEST_URI'], ['/','/?mode=reg', '/?mode=do_login', '/?mode=do_logout', '/?mode=do_reg', '/?mode=do_file_send', '/?mode=change_password', '/?mode=do_change_password'])) {
    header("HTTP/1.1 404 Not Found");
    exit(file_get_contents('./views/404.html'));
}

//Подключаем конфигурационный файл
$config = include './Config.php';

//подключаем вспомогательный класс Helper
include './Helper.php';
$helper = new Helper($config);
//Проверяем БД на подключение
if (!$helper->isConnectDb()) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Ошибка подключения к ДБ');
}

//Запускаем сессию
session_start();

//Устанавливаем кодировку
header('Content-Type: text/html; charset=UTF8');

//Маршруты. Обрабатываем Get запрос mode.
if (isset($_SESSION['work']) && isset($_GET['mode'])) {
    switch ($_GET['mode']) {
        //Обработка входа
        case 'do_login':
            if (!$_SESSION['work']) {
                //Проверяем на существование данных в бд users
                $result = $helper->isExistsCredentials($_POST['email'], $_POST['password']);
                //Если данных нет - устанавливаем ошибку.
                if (!$result[0]) $helper->flash($result[1]);
                else {
                    $_SESSION['email'] = $result[1];
                    $_SESSION['work'] = true;
                    //Если поставлена галочка на "Запомнить на 10 дней" - устанавливаем куки
                    if ($_POST['memory']) {
                        // Генерируем случайные данные и шифруем их
                        $hash = md5($helper->generateCode(10));
                        //Обновляем хэш в таблице users
                        $helper->updateHash($result[2], $hash);
                        // Ставим куки
                        setcookie("id", $result[2], time()+60*60*24*10, "/");
                        setcookie("hash", $hash, time()+60*60*24*10, "/", null, null, true);
                    }
                }
            }
            header('Location: /');
            exit();
        //Обработка выхода
        case 'do_logout':
            //Очищаем все параметры, связанные с авторизацией.
            if ($_SESSION['work']) $_SESSION['work'] = false;
            unset($_SESSION['email']);
            unset($_COOKIE['hash']);
            unset($_COOKIE['id']);
            setcookie('id', null, -1, '/');
            setcookie('hash', null, -1, '/');
            header('Location: /');
            exit();
        //Обработка регистрации
        case 'do_reg':
            if (!$_SESSION['work']) {
                //Создаем пользователя в БД
                $result = $helper->createUser($_POST['email'], $_POST['password'], $_POST['password_2']);
                if ($result[0]) {
                    $helper->flash('Пользователь ' . $_POST['email'] . ' успешно создан. Теперь Вы можете авторизоваться.', true);
                    header('Location: /');
                } else {
                    $helper->flash($result[1]);
                    header('Location: /?mode=reg');
                }
            } else header('Location: /');
            exit();
        //Форма регистрации
        case 'reg':
            if (!$_SESSION['work']) {
                //Подключаем шаблон Регистрации
                include './views/Reg.php';
                unset($_SESSION['flash']);
            } else header('Location: /');
            exit();
        //Обработка изменения пароля
        case 'do_change_password':
            if ($_SESSION['work']) {
                //Изменяем пароль пользователя
                $result = $helper->changePassword($_SESSION['email'],$_POST['password_old'],$_POST['password'],$_POST['password_2']);
                if ($result[0]) {
                    $helper->flash('Пароль успешно изменён. Пожалуйста авторизуйтесь повторно.', true);
                    //Очищаем все параметры, связанные с авторизацией.
                    $_SESSION['work'] = false;
                    unset($_SESSION['email']);
                    unset($_COOKIE['hash']);
                    unset($_COOKIE['id']);
                    setcookie('id', null, -1, '/');
                    setcookie('hash', null, -1, '/');
                    header('Location: /');
                    exit();
                } else {
                    $helper->flash($result[1]);
                    header('Location: /?mode=change_password');
                }
            } else header('Location: /');
            exit();
        //Форма изменения пароля
        case 'change_password':
            if ($_SESSION['work']) {
                //Подключаем шаблон Изменения пароля
                include './views/ChangePassword.php';
            } else header('Location: /');
            exit();
        //обработка загрузки файла
        case 'do_file_send':
            if ($_SESSION['work']) {
                $result = $helper->sendXmlFile($_FILES['filename']['tmp_name']);
                if ($result[0]) {
                    $helper->flash($result[1], true);
                } else $helper->flash($result[1]);
            }
            header('Location: /');
            exit();
    }
}
// Проверяем куки авторизации
if (isset($_COOKIE['id']) && isset($_COOKIE['hash'])) {
    $result = $helper->selectHash($_COOKIE['id']);
    if ($_COOKIE['hash'] == $result[1]) {
        $_SESSION['email'] = $result[0];
        $_SESSION['work'] = true;
    }
}
// Если авторизация осуществлена - открываем рабочий шаблон, если нет - страницу логина.
if (isset($_SESSION['work']) && $_SESSION['work']) {
    //Работаем после успешной авторизации
    static $data = "";
    //Проверяем на импортирование данных с XML Файла, если все успешно - выводим список людей с питомцами старше 3 лет. Если питомцу равно 3 года - человека не выводим.
    if (!empty($_SESSION['flash']) && $_SESSION['flash'][1]) {
        $result = $helper->selectUser(3);
        $data .= '<div class="work">Список участников, у которых есть питомцы старше 3 лет:</div>';
        if (!isset($result) || count($result) == 0) $data .= '<div class="work">В импортируемой таблице отсутствуют записи с подходящим условием.</div>';
        else {
            $data .= '<ul style="padding-left: 10px;">';
            $i = 1;
            foreach ($result as $row)
                $data .= '<li>'.$i++.'. '.$row['name'].'</li>';
            $data .= '</ul>';
        }
    }
    //Подключаем рабочий шаблон
    include './views/Work.php';
    echo $data.'</body></html>';
}
else {
    //Подключаем шаблон Логина
    include './views/Login.php';
    $_SESSION['work'] = false;
}
//Убираем переменную для отображение информации о результатах работы.
unset($_SESSION['flash']);