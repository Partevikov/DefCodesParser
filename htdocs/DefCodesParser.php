<?php
/**
 * Файл, определяющий класс парсера кодов сотовых операторов
 * 
 * PHP version 7
 * 
 * @category Parsing
 * @package  DefCodesParser
 * @author   Ilya Chetverikov <ischetverikov@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     http://pear.php.net/package/PackageName
 */
require_once "DefCodesParserException.php";

/**
 * Парсер Def-кодов сотовых операторов
 *
 * Получает def-коды и подробную информацию о них с сайта
 * компании «Межрегиональный ТранзитТелеком»
 *
 * @category Parsing
 * @package  DefCodesParser
 * @author   Ilya Chetverikov <ischetverikov@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     http://pear.php.net/package/PackageName
 */
class Def_Codes_Parser
{
    /**
     * URL сайта компании "МТТ"
     *
     * @var string $_url
     */
    private $_url = "http://www.mtt.ru/ru/defcodes/";
    
    /**
     * Время в секундах между запросами к сайту
     *
     * @var int $_sleepTime
     */
    private $_sleepTime = 1;
    
    /**
     * Конструктор класса, устанавливающий URL целевого сайта 
     * и время ожидания между запросами
     * 
     * @param string $url       URL сайта компании "МТТ"
     * @param int    $sleepTime время в секундах между запросами к сайту
     */
    function __construct($url, $sleepTime)
    {
        $this->_url = $url;
        $this->_sleepTime = $sleepTime;
    }
    
    /**
     * Получение Def-кодов и подробной информации о них
     *
     * @return array массив ('def-код' => пронумерованные массивы c описанием
     *               каждого поддиапазона номеров значениями ключей 
     *               num_s, num_e, region, operator, fd, td), где:
     *               'num_s' - начало поддиапазона
     *               'num_e' - конец поддиапазона
     *               'region' - область действия
     *               'operator' - название оператора
     *               'fd' - дата начала эксплуатации
     *               'td' - дата конца эксплуатации
     * @throws Def_Codes_Parser_Exception в случае неудачи
     */
    function getDefCodesInfo()
    {
        
        $content = file_get_contents($this->_url);
        if ($content == "") {
            throw new 
                Def_Codes_Parser_Exception("Сайт по данному URL недоступен.");
        }
        
        $defCodesPattern = "/<option value=\"(\d{3})\">\d{3}<\/option>/s";
        preg_match_all($defCodesPattern, $content, $matches);
        
        $defCodesArr = $matches[1];
        if (count($defCodesArr) == 0) {
            throw new 
                Def_Codes_Parser_Exception("По данному URL def-коды не найдены.");
        }
        
        foreach ($defCodesArr as $defCode) {
            $defCodesInfoArr[$defCode] = $this->getDefCodeInfo($defCode);
            sleep($this->_sleepTime);
        }
        
        return $defCodesInfoArr;
    }
    
    /**
     * Получение подробной информации об одном def-коде
     *
     * @param int $defCode Def-код
     *
     * @return array массив пронумерованных массивов, описывающий 
     *               каждый поддиапазон номеров одного def-кода значениями
     *               ключей num_s, num_e, region, operator, fd, td, где:
     *               'num_s' - начало поддиапазона
     *               'num_e' - конец поддиапазона
     *               'region' - область действия
     *               'operator' - название оператора
     *               'fd' - дата начала эксплуатации
     *               'td' - дата конца эксплуатации
     */
    function getDefCodeInfo($defCode)
    {
        /*
         * Формирование POST запроса для получения информации
         * о коде в формате JSON
         */
        $postData = array(
            "a" => "processRequest",
            "area" => "*",
            "date" => "",
            "def" => $defCode,
            "def_number" => "",
            "g" => "mtt",
            "m" => "def_codes",
            "operator" => "*",
            "standard" => "*"
        );
        
        /*
         * POST запрос
         */
        $content = file_get_contents(
            $this->_url,
            false, 
            stream_context_create(
                array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => 
                            'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query($postData)
                    )
                )
            )
        );
        
        $decodedContent = html_entity_decode(json_decode($content)->{'resultHTML'});
        
        $pattern = "/<tr>.*?";
        $pattern .= "<td>(\d{3})<\/td>.*?";
        $pattern .= "<td>(\d{7})-(\d{7})<\/td>.*?";
        $pattern .= "<td>(.*?)<\/td>.*?";
        $pattern .= "<td><strong>(.*?)<\/strong>.*?";
        $pattern .= "<span class=\"date-from-to\">(.*?)-(.*?)<\/span>.*?<\/td>.*?";
        $pattern .= "<\/tr>/s";
        
        preg_match_all($pattern, $decodedContent, $matches, PREG_SET_ORDER);
        
        $result = array();
        for ($i = 0; $i < count($matches); $i++) {
            $result[$i] = array(
                'num_s' => $matches[$i][2],
                'num_e' => $matches[$i][3],
                'region' => $matches[$i][4],
                'operator' => $matches[$i][5],
                'fd' => $matches[$i][6],
                'td' => $matches[$i][7]
            );
        }
        
        return $result;
    }
}
?>
