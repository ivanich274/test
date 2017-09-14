<?php
namespace Models;

abstract class Parse {
    
    protected $proxy;
    protected $userspwd;
    
    protected $usedCookies = false;
    /**
     * 
     * @param type $proxy
     * @param type $userpwd
     * Конструктор принимает прокси:порт, логин:пароль.
     */
    public function __construct($proxy, $userpwd) {
     
        $this->proxy = $proxy;
        $this->userspwd = $userpwd;
    }
    /**
     * 
     * @param type $url
     * @param type $isCookies
     * @param type $array_head
     * @return type
     * Загрузка страницы средствами CURL. (Урл, нужно ли передовать и принимать куки?, массив заголовков.
     */
    protected function getUrl($url, $array_head = array(), $isCookies = false ){
        
        $ch = curl_init();        
		curl_setopt($ch, CURLOPT_URL, $url);	
        
        if (count ($array_head) == 0) {            
            $array_head = array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36'
            );
        }	
        curl_setopt($ch, CURLOPT_HTTPHEADER, $array_head);	
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
          
       
        
        if ($isCookies || $this->usedCookies) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookies.txt');
            curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookies.txt');          
        }      
        
        curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->userspwd);
        $result = curl_exec($ch); 
        curl_close($ch);

        return $result;
    }
    /**
     * 
     * @param type $url
     * @param type $value
     * @param type $array_head
     * @return type
     * Отправка POST запроса.
     */
    protected function postUrl($url, $value, $array_head = array()) {
 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ( count ( $array_head ) > 0 ) {
            
            r($array_head);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $array_head);
        }	
        curl_setopt($ch, CURLOPT_POSTFIELDS, $value);		
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);		

        curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookies.txt');

        curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
        curl_setopt($ch, CURLOPT_PROXY, $this->PROXY);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->USERPWD);

        $result = curl_exec($ch);	 
        curl_close($ch);

        return $result;
    }
    /**
     * 
     * @param type $object_file
     * @param type $difDays - За какое кол-во дней чистить данные.
     * Метод, подчищающий устаревшие данные.
     */
    public function clearConfDataJson($object_file, $difDays = 30){
          
        $date_now = date_create(date('Y-m-d'));
        $dataClear = $this->readConfDataJson('dataClear.json');

        if(count($dataClear) == 0) {            
            $dataClear['dateCheck'] =  date_create('2017-08-25');
        }
        else {
            $dataClear['dateCheck'] =  date_create($dataClear['dateCheck']);            
        }

        if (date_diff($dataClear['dateCheck'],$date_now)->days > 0) {

            $data = $this->readConfDataJson($object_file);
            foreach ($data as $key => $value) {

                $date_item = date_create($data[$key]);         
                if (date_diff($date_item,$date_now)->days > $difDays) {                
                    unset($data[$key]);
                }            
            }
            $this->saveConfDataJson($data,$object_file); 
            
            //Записываем новую дату последней проверки.
            $dataClear['dateCheck'] = date('Y-m-d');
            $this->saveConfDataJson($dataClear,'dataClear.json');  
            
        }           
             
    }


    /**
     * 
     * @param type $file
     * Очистка файла с куками.
     */
    protected function clearFileCookies($file) {        
        file_put_contents($file, "");
    }
    /**
     * 
     * @param type $object_file
     * @return type
     * Чтение из конфиг-файла.
     */
    protected function readConfDataJson($object_file) {        
        return json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/app/DataConf/'.$object_file), true);
    }
   /**
    * 
    * @param type $array
    * @param type $object_file
    * Запись в конфиг-файл.
    */
    protected function saveConfDataJson($array, $object_file) {
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/app/DataConf/'.$object_file, json_encode($array));
    }
    /**
     * 
     * @param type $arr1
     * @param type $arr2
     * @param type $val
     * @return type
     * Метод поиска в массиве значения, и возвращение по ключу из другого массива.
     */
    protected function getArrayKeyVal($arr1,$arr2, $val){            
        $key = array_search($val, $arr1);              
        return trim($arr2[$key]);            
    } 
    /**
     * 
     * @param type $content
     * @return type
     * Удаляет все возможные пробелы.
     */
    protected function trimAll($content) {
        
        $content = str_replace("&nbsp;","",$content);
        $content = str_replace(' ','', $content);
        
        return $content;		
    }
    /**
     * 
     * @param type $regex
     * @param type $content
     * @return type
     * Находит все вхождения по регулярному выражению.
     */
    protected function pregMatchAll($regex, $content) {
        preg_match_all($regex, $content, $result);
        return $result;	
    }
    /**
     * 
     * @param type $regex
     * @param type $content
     * @return type
     * Находит 1 вхождение по регулярному выражению.
     */
    protected function pregMatch($regex, $content) {
        preg_match($regex, $content, $result);
        return $result;		
    }
    
    function getParamsSearch($ARR_OBJEST, $arr_params) {

        $RESULT_ARR = array();

        foreach ( $ARR_OBJEST as $key => $value ) {
            if (in_array ($value, $arr_params)) {
                $RESULT_ARR[$key] = 1;
            }
        }

        return $RESULT_ARR;
    }

    function getParamsSerachValue($arr_keys, $arr_values) {

        $res_items = array();

        for ($i = 0; $i <= count ($arr_keys) - 1; $i++) {
            $res_items[$arr_keys[$i]] = $arr_values[$i];
        }

        return $res_items;

    }
	
}




