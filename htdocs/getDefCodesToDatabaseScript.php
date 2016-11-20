<?php
/**
 * Скрипт, извлекающий с сайта компании "МТТ" def-коды 
 * сотовых операторов и помещающий их в базу данных
 * 
 * PHP version 7
 * 
 * @category Parsing
 * @package  DefCodesParser
 * @author   Ilya Chetverikov <ischetverikov@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     https://github.com/Partevikov/DefCodesParser
 */
require_once "DefCodesParser.php";
$configPath = "config.ini";

/*
 * PHP оповещает обо всех ошибках
 */
error_reporting(E_ALL);

/*
 * Чтение конфигурационного файла
 */
$configArr = parse_ini_file($configPath);    

/*
 * Извлекаемые из конфигурационного файла параметры
 */
$configParamsArr = array(
    'targetUrl',
    'sleepTime',
    'dbms',
    'hostName',
    'port',
    'userName',
    'password',
    'dbName',
    'tableName',
    'encoding'
);

/*
 * Проверка наличия параметров в конфигурационном файле 
 * В случае успеха создание переменной с именем параметра
 */
foreach ($configParamsArr as $configParam) {
    if (!isset($configArr[$configParam])) {
        print("Отсутствует конфигурационный параметр ".$configParam);
        print(" в файле ".$configPath.".<br/>\n");
        
        exit();
    } else {
        $$configParam = $configArr[$configParam];
    }
}

/*
 * Создание строки подключения
 */
$dbConnectionString = "$dbms:host=$hostName;dbname=$dbName";

if (is_int($port) && ($port != 0)) {
    $dbConnectionString.=";port=$port";
}

if ($dbms == "mysql") {
    $dbConnectionString .= ";charset=$encoding";
} elseif ($dbms == "pgsql") {
    $dbConnectionString .= ";client_encoding=$encoding";
} else {
    print("Указанная СУБД ".$dbms." не поддерживается.<br/>\n");
}

/*
 * Подключение к СУБД. В случае неудачи завершить вполнение скрипта
 */
try {
    $dbConnection = new PDO($dbConnectionString, $userName, $password);
} catch (PDOException $e) {
    print ("Error!: ".$e->getMessage()."<br />");
    exit();
}

/*
 * Получение информации о def-кодах
 */
$defCodesParser = new Def_Codes_Parser($targetUrl, $sleepTime);
try {
    $defCodesInfoArr = $defCodesParser->getDefCodesInfo();
} catch (Def_Codes_Parser_Exception $e) {
    print("Не удалось получить информацию о кодах: ".$e->getMessage()."<br/>\n");
    exit();
}

/*
 * Вставка в БД информации о всех полученных кодах
 */
foreach ($defCodesInfoArr as $defCode => $defCodeInfoArr) {
    if (count($defCodeInfoArr) == 0) {
        print("Для кода $defCode информация не найдена!<br/>\n");
        continue;
    }
    
    $query = "INSERT INTO $tableName ";
    $query .= "(def, num_s, num_e, region, operator, fd, td) VALUES ";
    $query .= "(:def, :num_s, :num_e, :region, :operator, :fd, :td);";

    $STH = $dbConnection->prepare($query);
    $dbConnection->beginTransaction();
    
    $data = array();
    $data['def'] = $defCode;
    
    for ($i = 0; $i < count($defCodeInfoArr); $i++) {
        $data['num_s'] = $defCodeInfoArr[$i]['num_s'];
        $data['num_e'] = $defCodeInfoArr[$i]['num_e'];
        $data['region'] = $defCodeInfoArr[$i]['region'];
        $data['operator'] = $defCodeInfoArr[$i]['operator'];
        $data['fd'] = date("Y-m-d", strtotime($defCodeInfoArr[$i]['fd']));
        $data['td'] = date("Y-m-d", strtotime($defCodeInfoArr[$i]['td']));
        
        try {        
            $STH->execute($data); 
        } catch (PDOException $e) {
            print "Ошибка при добавленни данных в БД: ".$e->getMessage()."<br/>\n";
        }
    }
    
    $dbConnection->commit();
    print("Код $defCode обработан.<br/>\n");
     
}

$dbConnection = null;
    
?>
