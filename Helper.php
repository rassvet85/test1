<?php

//Ключ защиты
if(!defined('SITE_KEY'))
{
    header("HTTP/1.1 404 Not Found");
    exit(file_get_contents('./views/404.html'));
}

class Helper
{
    private false|PgSql\Connection $dbconn;
    //Инициализация БД
    public function __construct($config)
    {
        # Создаем соединение с базой PostgreSQL
        $this->dbconn = pg_connect('host='.$config['db_host'].' port='.$config['db_port'].' dbname='.$config['db_name'].' user='.$config['db_user'].' password='.$config['db_pass']);
    }
    //Проверка на подключение к БД
    public function isConnectDb(): bool
    {
        return (bool)$this->dbconn;
    }
    //Проверка Email и пароля в БД users
    public function isExistsCredentials($email, $password, $onlyMail = false): array
    {
        if(empty($email)) return [false, 'Не введён Email'];
        if(empty($password)) return [false, 'Не введён пароль'];
        //Проверяем на SQL инъекцию
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Email имеет неверный формат'];
        $result = pg_query($this->dbconn, "SELECT * FROM users WHERE email = '".$email."'");
        if (!$result || pg_num_rows($result) == 0) return [false, 'Пользователь с такими Email не зарегистрирован'];
        if ($onlyMail) return [true];
        $row = pg_fetch_assoc($result);
        //Сравниваем пароли
        if (!password_verify($password,$row['password'])) return [false, 'Неправильный пароль'];
        //Если никаких ошибок нет - возвращаем положительную авторизацию
        return [true, $row['email'], $row['id']];
    }
    //Создание пользователя в БД и проверка вводимых параметров
    public function createUser($email, $password, $password2): array
    {
        if(empty($email)) return [false, 'Не введён Email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Email имеет неверный формат'];
        if(empty($password)) return [false, 'Не введён пароль'];
        if($password != $password2) return [false, 'Пароли не совпадают'];
        //Проверяем на существование email
        if ($this->isExistsCredentials($email, $password, true)[0]) return [false, 'Пользователь с таким Email существует'];
        $result = pg_query($this->dbconn, "INSERT INTO users (email, password) VALUES ('".$email."', '".password_hash($password,PASSWORD_DEFAULT)."')");
        if (!$result) return [false, 'Ошибка создания пользователя в БД'];
        return [true];
    }
    //Изменение пароля пользователя
    public function changePassword($email, $password_old, $password, $password2): array
    {
        if(empty($password_old)) return [false, 'Не введён старый пароль'];
        if(empty($password)) return [false, 'Не введён новый пароль'];
        if($password != $password2) return [false, 'Новые пароли не совпадают'];
        //Запрашиваем данные пользователя
        $result = pg_query($this->dbconn, "SELECT * FROM users WHERE email = '".$email."'");
        $row = pg_fetch_assoc($result);
        //Сравниваем существующий пароль
        if (!password_verify($password_old,$row['password'])) return [false, 'Старый пароль не верен'];
        $result = pg_update($this->dbconn, 'users', ['password' => password_hash($password,PASSWORD_DEFAULT)], ['email' => $email] );
        if (!$result) return [false, 'Ошибка изменения пароля пользователя в БД'];
        return [true];
    }
    //Обновление Хэша для доступа по кукам
    public function updateHash($id, $hash): void
    {
        pg_update($this->dbconn, 'users', ['user_hash' => $hash], ['id' => $id] );
    }
    //Запрос хэша для проверки Куков
    public function selectHash($id): array
    {
        $result = pg_fetch_all(pg_query($this->dbconn, "SELECT email, user_hash FROM users WHERE id = ".$id));
        return [$result[0]['email'], $result[0]['user_hash']];
    }
    //Функция запроса людей с питомцами старше $year лет.
    public function selectUser($year): array|null
    {
        $result = pg_query($this->dbconn, "SELECT CONCAT(people.username,' (', STRING_AGG(CONCAT(pets.pet_type,' ',pets.pet_nickname,' возраст: ',pets.pet_age), ', ' ORDER BY pets.pet_nickname), ')') AS name 
                                                FROM people
                                                JOIN pets ON pets.id_people = people.id AND pets.pet_age > ".$year."
                                                GROUP BY people.username
                                                ORDER BY people.username");
        return pg_fetch_all($result);
    }
    //Работа с импортируемым файлом
    public function sendXmlFile($filePath): array
    {
        $xml = simplexml_load_file($filePath);
        if (!$xml) return [false, 'Данные в файле имеют неверную структуру XML'];
        $increment = 1;
        $finalTablePet = array();
        $finalTableUser = array();
        //Считываем данные из XML файла
        foreach ($xml as $user) {
            if (empty($user->attributes()->Name)) return [false, 'Ошибка парсера в'.($increment == 2?'о':' ').$increment.' элементе User файла XML. Отсутствует имя участника, либо ошибка в XML файле в параметре "User Name'];
            if (mb_strlen($user->attributes()->Name) > 255) return [false, 'Ошибка парсера в'.($increment == 2?'о':' ').$increment.' элементе User файла XML. Отсутствует имя участника, либо ошибка в XML файле в параметре "User Name'];
            $finalTableUser[] = ['id' => $increment, 'username' => (string)$user->attributes()->Name];
            //Проверяем наличие поля Pets
            if (isset($user->Pets)) {
                foreach ($user->Pets->children() as $pet) {
                    $table = $this->createArray();
                    if (isset($pet->attributes()->Code)) $table['pet_code'] = (string)$pet->attributes()->Code;
                    $table['id_people'] = $increment;
                    if (isset($pet->attributes()->Type)) $table['pet_type'] = (string)$pet->attributes()->Type;
                    if (isset($pet->attributes()->Gender)) $table['pet_gender'] = (string)$pet->attributes()->Gender;
                    if (isset($pet->attributes()->Age) && is_numeric((string)$pet->attributes()->Age)) $table['pet_age'] = (float)$pet->attributes()->Age;
                    if (isset($pet->Nickname)) $table['pet_nickname'] = (string)$pet->Nickname->attributes()->Value;
                    if (isset($pet->Breed)) $table['pet_breed'] = (string)$pet->Breed->attributes()->Name;
                    if (isset($pet->Rewards)) {
                        $tableRewards = array();
                        foreach ($pet->Rewards->children() as $reward) {
                            if (isset($reward->attributes()->Name)) $tableRewards[] = (string)$reward->attributes()->Name;
                        }
                        if (count($tableRewards) > 0) $table['pet_rewards'] = $this->toPgArray($tableRewards);
                    }
                    if (isset($pet->Parents)) {
                        $tableParent = array();
                        foreach ($pet->Parents->children() as $parent) {
                            if (isset($parent->attributes()->Code)) $tableParent[] = (string)$parent->attributes()->Code;
                        }
                        if (count($tableParent) > 0) $table['pet_parents'] = $this->toPgArray($tableParent);
                    }
                    $verify = $this->verifyArray($table);
                    //Проверяем данные, полученные с парсера. Проверяются только Имя участника, код питомца, тип питомца, возраст питомца и кличка питомца. Если есть проблема - возвращаем сразу ошибку. Остальные параметры могут быть NULL.
                    if (!$verify[0]) return [false, 'Ошибка парсера в'.($increment == 2?'о':' ').$increment.' элементе User файла XML. '.$verify[1]];
                    $finalTablePet[] = $table;
                }
            }
            $increment++;
        }
        //Если есть наличие верифицированных записей - отправляем их в БД, если нет - возвращаем ошибку.
        if ($increment > 1) {
            $result = pg_query($this->dbconn, "DELETE FROM people");
            if (!$result) return [false, 'Ошибка удаления данных в таблице "people"'];
            $result = pg_query($this->dbconn, "DELETE FROM pets");
            if (!$result) return [false, 'Ошибка удаления данных в таблице "pets"'];
            foreach ($finalTableUser as $row) {
                $result = pg_insert($this->dbconn, 'people', $row);
                if (!$result) return [false, 'Ошибка передачи данных в БД'];
            }
            foreach ($finalTablePet as $row) {
                $result = pg_insert($this->dbconn, 'pets', $row);
                if (!$result) return [false, 'Ошибка передачи данных в БД'];
            }
            return [true, "Данные успешно импортированы в БД"];
        }
        return [false, "Данные для импорта в БД отсутствуют"];
    }

    public function flash($message = null, $noerror = false): void
    {
        if ($message) {
            $_SESSION['flash'] = [$message, $noerror];
        } else {
            unset($_SESSION['flash']);
        }
    }
    //Функция проверки данных с XML файла. Проверяются только Имя участника, код питомца, тип питомца, возраст питомца и кличка питомца. Также проверяем лимиты символов основных параметров. Если эти параметры отсутствуют - будет ошибка до момента исправления в файле XML.
    //Остальные параметры не проверяем. Их может вообще и не быть.
    private function verifyArray($table): array
    {
        if (empty($table['pet_code'])) $msg = 'Отсутствует код питомца участника, либо ошибка в XML файле в параметре "Pet Code"';
        else if (empty($table['pet_type'])) $msg = 'Отсутствует тип питомца участника, либо ошибка в XML файле в параметре "Pet Type"';
        else if (empty($table['pet_age'])) $msg = 'Отсутствует возраст питомца участника, либо ошибка в XML файле в параметре "Pet Age"';
        else if (empty($table['pet_nickname'])) $msg = 'Отсутствует кличка питомца участника, либо ошибка в XML файле в параметре "Pet Nickname Value"';
        else if (mb_strlen($table['pet_code']) > 3) $msg = 'Значение "Pet Code" превышает 3 символа';
        else if (mb_strlen($table['pet_type']) > 255) $msg = 'Значение "Pet Type" превышает 255 символов';
        else if (mb_strlen($table['pet_gender']) > 1) $msg = 'Значение "Pet Gender" превышает 1 символ';
        else if (mb_strlen($table['pet_nickname']) > 255) $msg = 'Значение "Pet Nickname Value" превышает 255 символов';
        else if (mb_strlen($table['pet_breed']) > 255) $msg = 'Значение "Pet Breed Name" превышает 255 символов';
        if (isset($msg)) return [false, $msg];
        return [true];
    }

    // Функция для генерации случайной строки
    public function generateCode($length=6): string
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHI JKLMNOPRQSTUVWXYZ0123456789";
        $code = "";
        $clen = strlen($chars) - 1;
        while (strlen($code) < $length) {
            $code .= $chars[mt_rand(0,$clen)];
        }
        return $code;
    }
    // Функция очистки сессии и куков
    public function deleteSessionCooKs() : void
    {
        unset($_SESSION['email']);
        unset($_COOKIE['hash']);
        unset($_COOKIE['id']);
        setcookie('id', null, -1, '/');
        setcookie('hash', null, -1, '/');
    }
    // Функция создания ассоциированного массива
    private function createArray(): array
    {
        return [
            'pet_code' => null,
            'id_people' => null,
            'pet_type' => null,
            'pet_gender' => null,
            'pet_age' => null,
            'pet_nickname' => null,
            'pet_breed' => null,
            'pet_rewards' => null,
            'pet_parents' => null,
            ];
    }
    // Функция преобразования массива PHP в массив для PostgresSql
    private function toPgArray($set): string
    {
        settype($set, 'array');
        $result = array();
        foreach ($set as $t) {
            $t = str_replace('"', '\\"', $t);
            if (! is_numeric($t)) $t = '"' . $t . '"';
            $result[] = $t;
            }
        return '{' . implode(",", $result) . '}';
    }

}