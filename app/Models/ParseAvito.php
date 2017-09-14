<?php
namespace Models;

class ParseAvito extends Parse {    
    /**
     * 
     * @param type $page_number
     * Загружаем страницы Авито и парсим список объявлений.
     */
    public function listPage($page_number)
    {
        //Квартиры, мобильная версия.
        $result = $this->getUrl('https://m.avito.ru/nizhniy_novgorod/kvartiry/sdam?p='.$page_number);       
        $arr_params_kvart = $this->pregMatchAll('|<div class="b-item-wrapper visited-highlight-wrapper">.*?<a href="(.*?)" class="item-link item-link-visited-highlight">|sei', $result);
       
        
                       
        $arr_params_room = array();
        //Комнаты, мобильная версия.
	$result = $this->getUrl('https://m.avito.ru/nizhniy_novgorod/komnaty/sdam?p='.$page_number);
        
        $arr_params_room = $this->pregMatchAll('|<div class="b-item-wrapper visited-highlight-wrapper">.*?<a href="(.*?)" class="item-link item-link-visited-highlight">|sei', $result);
        
        
       return array_merge($arr_params_kvart[1], $arr_params_room[1]);        
    }
    
    
   
    public function isCaptcha($content) {
        
        
        $arr_items = $this->pregMatch('|<title>(.*?)</title>|sei', $content);
        if (trim($arr_items[1] == 'Доступ временно заблокирован')){
            
            return true;
        }
        else false;
        
        
    }
    
    
    /**
     * 
     * @param type $url
     * Получаем ID объявления из урла.
     */
    private function getId($url) {        
        $mas = explode('_', $url);
        return $mas[count($mas)-1];		
    }
    
    public function getDateAdvert($content) {
	
        /*
        <div class="item-add-date">Размещено      сегодня&nbsp;в 15:22  </div>
        */
        preg_match('|<div class="item-add-date">Размещено (.*?)</div>|sei', $content, $arr_s);	

        $date_ads = null;			
        $date_val = $arr_s[1];

        $date_val = str_replace('&nbsp;',' ',$date_val);

        $date_act = explode(' ',trim($date_val));	

        $KOGDA = '';

        switch(trim($date_act[0])) {

            case 'вчера':					


                $date_format = date_create('0000-'.date("m", strtotime( '-1 days' ) ).'-'.date("d", strtotime( '-1 days' ) ).'');

                $date_up_base = date_create(date("Y", strtotime( '-1 days' ) ).'-'.date("m", strtotime( '-1 days' ) ).'-'.date("d", strtotime( '-1 days' ) ).' '.trim($date_act[2]).':00');						

            break;


            case 'сегодня':

                $date_format = date_create('0000-'.date("m").'-'.date("d").'');

                $date_up_base = date_create(date("Y").'-'.date("m").'-'.date("d").' '.trim($date_act[2]).':00');



            break;

            default:

                $date_act[1]=str_replace("января","01",$date_act[1]);
                $date_act[1]=str_replace("февраля","02",$date_act[1]);
                $date_act[1]=str_replace("марта","03",$date_act[1]);
                $date_act[1]=str_replace("мая","05",$date_act[1]);
                $date_act[1]=str_replace("апреля","04",$date_act[1]);
                $date_act[1]=str_replace("июня","06",$date_act[1]);
                $date_act[1]=str_replace("июля","07",$date_act[1]);
                $date_act[1]=str_replace("августа","08",$date_act[1]);
                $date_act[1]=str_replace("сентября","09",$date_act[1]);						
                $date_act[1]=str_replace("октября","10",$date_act[1]);
                $date_act[1]=str_replace("ноября","11",$date_act[1]);
                $date_act[1]=str_replace("декабря","12",$date_act[1]);			



                $date_format = date_create('0000-'.trim($date_act[1]).'-'.trim($date_act[0]).'');

                $date_up_base = date_create(date('Y').'-'.trim($date_act[1]).'-'.trim($date_act[0]).' '.trim($date_act[3]).':00');


            break;
        }

        return $date_up_base;

	}
    
    /**
     * 
     * @param type $url
     * Информация по объявлению.
     */
    public function getData($url) {
                
        
        $content = $this->getUrl($url,array(),true);                   
        
        //Стоимость аренды.
        //<span class="price-value">
        $price_items = $this->pregMatch('|<span class="price-value">(.*?)&nbsp;руб.(.*?)</span>|sei', $content);
        $price = $this->trimAll($price_items[1]);        
        
        //Период аренды.
        $timeRent = '';        
        switch(trim($price_items[2])) {
            case 'за сутки':
                    $timeRent = 2;
            break;
            case 'в месяц':
                    $timeRent = 1;
            break;
        }
        
        
        $params_main = $this->pregMatchAll('|<span class="text text-main">(.*?)</span>|sei', $content);
        
        $typeObject = 'FLAT';
        
        switch($params_main[1][0]) {


            case 'Сдам комнату':
                 
                $typeObject = 'ROOM';
                
                $key_kv = 2;
                $key_area = 1;
                $key_type = 3;

                //Если посуточно, то добавляется слово посуточно, и n-k квартира уже сдвигается на 1 элемент 
                if ($time_rent == 2) {			
                    $key_kv++;
                    $key_area++;
                    $key_type++;
                }

                $countRooms = 0;

                //Кол-во комнат.
                switch($params_main[1][$key_kv]) {

                    case '1-к квартире':
                        $countRooms = 1;
                    break;
                    case '2-к квартире':
                        $countRooms = 2;
                    break;
                    case '3-к квартире':
                        $countRooms = 3;
                    break;
                    case '4-к квартире':
                        $countRooms = 4;
                    break;
                    case '5-к квартире':
                        $countRooms = 5;
                    break;
                    case '6-к квартире':
                        $countRooms = 6;
                    break;
                    case '7-к квартире':
                        $countRooms = 7;
                    break;
                    case '8-к квартире':
                         $countRooms = 8;
                    break;
                    case '9-к квартире':
                        $countRooms = 8;
                    break;
                    case 'многокомнатной квартире':
                        $countRooms = 10;
                    break;			

                    }
                    //Площадь
                    $area = $params_main[1][$key_area];				
                    $area = str_replace('Комната','',$area);
                    $area = str_replace('м²','',$area);
                    $area = (double) $area;

                    //Тип дома
                    $typeHouse = '';
                    switch($params_main[1][$key_type]) {

                        case 'кирпичного дома':
                            $typeHouse = 1;
                        break;
                        case 'блочного дома':
                            $typeHouse = 3;
                        break;
                        case 'панельного дома':
                            $typeHouse = 2;
                        break;
                        case 'монолитного дома':
                            $typeHouse = 4;
                        break;
                        case 'деревянного дома':
                            $typeHouse = 5;
                        break;			

                    }



                break;

                case 'Сдам квартиру':

                    $key_kv = 1;
                    $key_area = 2;
                    $key_type = 3;

                    //Если посуточно, то добавляется слово посуточно, и n-k квартира уже сдвигается на 1 элемент 
                    if ($timeRent == 2) {			
                        $key_kv++;
                        $key_area++;
                        $key_type++;
                    }

                    $countRooms = 0;
                    //Кол-во комнат.
                    switch($params_main[1][$key_kv]) {

                        case '1-к квартира':
                            $countRooms = 1;
                        break;
                        case '2-к квартира':
                            $countRooms = 2;
                        break;
                        case '3-к квартира':
                            $countRooms = 3;
                        break;
                        case '4-к квартира':
                            $countRooms = 4;
                        break;
                        case '5-к квартира':
                            $countRooms = 5;
                        break;
                        case '6-к квартира':
                            $countRooms = 6;
                        break;
                        case '7-к квартира':
                            $countRooms = 7;
                        break;
                        case '8-к квартира':
                            $countRooms = 8;
                        break;
                        case '9-к квартира':
                            $countRooms = 8;
                        break;
                        case 'Многокомнатная квартира':
                            $countRooms = 10;
                        break;			

                    }
                    //Площадь
                    $area = (int)$params_main[1][$key_area];

                    //Тип дома
                    $typeHouse = '';

                    switch($params_main[1][$key_type]) {

                        case 'кирпичного дома':
                            $typeHouse = 1;
                        break;
                        case 'блочного дома':
                            $typeHouse = 3;
                        break;
                        case 'панельного дома':
                            $typeHouse = 2;
                        break;
                        case 'монолитного дома':
                            $typeHouse = 4;
                        break;
                        case 'деревянного дома':
                            $typeHouse = 5;
                        break;			

                    }


                break;

                case 'Сдам':
                    $typeObject = 'HOUSE';
                break;
        }
        
        //Этаж/Этажность.
        //<span class="text text-prefix    ">на 9 этаже  9-этажного</span>
        $paramsEtage = $this->pregMatch('|<span class="text text-prefix.*?>на (.*?) этаже (.*?)-этажного|sei', $content);
        $etage = $paramsEtage[1];
        $etageHouse = $paramsEtage[2];        
        
        //Адрес
        //<span class="info-text user-address-text">
        $addres_val = $this->pregMatch('|<span class="info-text user-address-text">(.*?)</span>|sei', $content);  
        $addres = trim($addres_val[1]);
        
        //Имя контакты
        //<a href="/user/68e1cd2784595d4d7f4d01695e8d5489/profile" class="person-name person-name-link">// Арслан //</a>
        $nameContact = strip_tags(trim($this->pregMatch('|class="person-name.*?">(.*?)<div|s', $content)[1]));
        
        //Агенство или нет
        //<div class="person-registered-since">
        $reg_since = $this->pregMatch('|<div class="person-registered-since">(.*?)</div>|sei', $content);
        $isAgent = false;
        if ( strpos($reg_since[1], 'Агентство') == true) {		
            $isAgent = true;
        }
        
        //Комиссия
        //<div class="info-price-extra"> Комиссия 12 600&nbsp;руб.<br>Без залога </div> //r
        
        $comm_val = $this->pregMatch('|<div class="info-price-extra">.*?Комиссия(.*?)&nbsp;|s', $content);     
        
        
        //Залог 
        $zalog_val = $this->pregMatch('|<div class="info-price-extra">.*?Залог(.*?)&nbsp;|s', $content);
        $commPrice =  $this->trimAll($comm_val[1]);
        $zalogPrice =  $this->trimAll($zalog_val[1]);
        
        //Фото
        //<meta property="og:image" content="https://10.img.avito.st/640x480/3707676910.jpg">
        $photo_vals = $this->pregMatchAll('|<meta property="og:image" content="(.*?)">|sei', $content);		
        $photos = $photo_vals[1];

        if ( strpos(trim($photos[0]), 'mobile/img/common/avito.png') == true) {	
            $photos = array();
        }	

        //Описание
        //<div class="description-preview-wrapper">
        $about = trim($this->pregMatch('|<div class="description-preview.*?<p>(.*?)</p>|s', $content)[1]);
        $about = str_replace('<br />','\r\n',$about);

        $items_params = $this->pregMatchAll('|<article class="single-item-description-param">.*?<h4 class="description-param-header gray-text">(.*?)</h4>.*?<ul class="description-param-values mdash-list">(.*?)</ul>|s', $content);	

        //Количество спальных мест

        $key = array_search('Количество спальных мест', $items_params[1]); 		
        $COUNT_SP_MEST = (int)trim(strip_tags($items_params[2][$key]));

        //Количество кроватей
        $key = array_search('Количество кроватей', $items_params[1]); 		
        $COUNT_KROVAT = (int)trim(strip_tags($items_params[2][$key]));

        //Мультимедиа
        $key = array_search('Мультимедиа', $items_params[1]); 		
        $multimedia_vals = $this->pregMatchAll('|<li class="description-param-value list-item">(.*?)</li>|sei', $items_params[2][$key]);	
        //Бытовая техника
        $key = array_search('Бытовая техника', $items_params[1]); 	
        $tehn_vals = $this->pregMatchAll('|<li class="description-param-value list-item">(.*?)</li>|sei', $items_params[2][$key]);	
        //Комфорт
        $key = array_search('Комфорт', $items_params[1]); 	
        $comfort_vals = $this->pregMatchAll('|<li class="description-param-value list-item">(.*?)</li>|sei', $items_params[2][$key]);	
        //Дополнительно
        $key = array_search('Дополнительно', $items_params[1]); 
        $dop_vals = $this->pregMatchAll('|<li class="description-param-value list-item">(.*?)</li>|sei', $items_params[2][$key]);


        $MULTIMEDIA = array ("WIFI" => "Wi-Fi", "TV" => "Телевизор", "CTV" => "Кабельное / цифровое ТВ");
        $APPLIANCES = array("STOVE" => "Плита", "MICROWAVE" => "Микроволновка", "FRIDGE" => "Холодильник", "WASHER" => "Стиральная машина", "HAIRDRYER"=> "Фен", "IRON" => "Утюг" );			
        $COMFORT = array ("CONDITIONING"=> "Кондиционер", "FIREPLACE" =>"Камин", "BALCONY" => "Балкон / лоджия", "PARKING" => "Парковочное место");	
        $ADDITIONALLY =  array("ANIMALS" => "Можно с питомцами", "CHILDREN" => "Можно с детьми", "EVENTS" => "Можно для мероприятий", "SMOKING" => "Можно курить");				

        $MULTIMEDIA_OUT = $this->getParamsSearch($MULTIMEDIA, $multimedia_vals[1]);
        $APPLIANCES_OUT = $this->getParamsSearch($APPLIANCES, $tehn_vals[1]);
        $COMFORT_OUT = $this->getParamsSearch($COMFORT, $comfort_vals[1]);
        $ADDITIONALLY_OUT = $this->getParamsSearch($ADDITIONALLY, $dop_vals[1]);


        $arr_phone = $this->pregMatch('/class="person-action button button-solid button-blue button-large action-link link action-show-number.*?href="(.*?)".*?>/s', $content);	

        
        $params = array(

            'countRooms' => $countRooms,
            'area' => $area, 
            'typeHouse' => $typeHouse, 
            'etage' => (int)$etage, 
            'etageHouse' => (int)$etageHouse,
            'price'	=> (int)$price,
            'timeRent'	=> $timeRent,
            'addres' => $addres,
            'nameContact' => $nameContact,
            'isAgent'	=> $isAgent,
            'commPrice'	=> (int)$commPrice,
            'zalogPrice'=> (int)$zalogPrice,
            'photos' => $photos,
            'about'	=> $about,
            'DATE_CREATE' => $this->getDateAdvert($content),
            'COUNT_KROVAT' => $COUNT_KROVAT,
            'COUNT_SP_MEST' => $COUNT_SP_MEST,
            'MULTIMEDIA' => $MULTIMEDIA_OUT,
            'APPLIANCES' => $APPLIANCES_OUT,
            'COMFORT'	=> $COMFORT_OUT,
            'ADDITIONALLY' => $ADDITIONALLY_OUT,
            'PHONE_GET_URL' => $arr_phone[1],
            'typeObject' => $typeObject
        );
        
        return $params;


    }
    
    public function getPhoneValue ($url, $refer) {
        
        $head = array(
            'Accept: application/json',
            'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
            'X-Requested-With:XMLHttpRequest',
            'Referer:'.$refer.''
	);
                
        $result = $this->getUrl('https://m.avito.ru/'.$url.'?async&searchHash=', $head, true);
        
        if (!$this->usedCookies) {            
            //Очищаем файл куков.
            $this->clearFileCookies(dirname(__FILE__).'/cookies.txt');
        }
       
        
        return json_decode($result)->phone; 
        
    }
    /**
     * 
     * @param type $adverts_list
     * @return string
     * Метод, проходящий по списку объявлений, собирает информацию и фильтрует не нужные.
     */
    public function workingAdverts($adverts_list){
        
        //Если пусто, то возвращает пустой массив.
        if (count($adverts_list) == 0 ) {            
            return array();
        }
        
        //Запрашиваем массив ID уже обработанных объявлений.
        $arrayIds = $this->readConfDataJson('avito.ru.json');        
        $FileAction = new FileAction();
        
        $advertListNew = array();
        
        foreach($adverts_list as $advertUrl) {
            
            $idAdvert = $this->getId($advertUrl);
            
            $advertFullUrl = 'https://m.avito.ru'.$advertUrl;            
                        
            //Проверяем, есть ли такой ID в нашем файле.
            if(!array_key_exists($idAdvert, $arrayIds)) {
            
                 sleep(3);
                 
                //Если нет, то запрашиваем информацию по объявлению.
                $advertInfo = $this->getData($advertFullUrl);
                                
                // Фильтр, без комисиии.
                if($advertInfo['commPrice'] == 0 ) {
    
                    if ($advertInfo['timeRent'] == '2' && $advertInfo['isAgent'] == true) {
                        //Фильтр, посуточно агенты - не пускаем
                        //Сразу записываем ID и дату объявления, которое не прошло проверку.
                        $arrayIds[$idAdvert] = date('Y-m-d');	
                    }
                    else {
                        
                        sleep(3);

                        //Суда записываем всех остальных.
                        //Запрашиваем номер телефона.
                        $advertInfo['phoneValue'] = $this->getPhoneValue($advertInfo['PHONE_GET_URL'], $advertFullUrl);
                        
                                                
                        if (empty($advertInfo['phoneValue'])){
                            //Что то пошло не так, завершаем работу парсера.
                            break;
                        }                        
                                               
                       //Проверим, есть ли фотографии более лучшего расширения.
                       $listPhoto = $advertInfo['photos'];
				
                       $c = 0;                       
                        foreach ($listPhoto as $photoTemp) {				

                            $photoNew = str_replace('640x480', '1280x960', $photoTemp);
                            if ( $FileAction->isExist($photoNew) ) {						
                                    $listPhoto[$c] = $photoNew;
                            }						
                            $c++;
                        }
                        
                        $advertInfo['photoPrew'] = $listPhoto[0];                        
                        $advertInfo['photos'] = serialize ($listPhoto);	                        
                        $advertInfo['id'] = $idAdvert;
                        $advertInfo['url'] = $advertFullUrl;
                        $advertInfo['photoCount'] = $c;
                        
                        //Добавяем в список новое объявление.
                        $advertListNew[] = $advertInfo;
                                                                        
                    }
                }
                else {                    
                    //Сразу записываем ID и дату объявления, которое не прошло проверку.
                    $arrayIds[$idAdvert] = date('Y-m-d');	
                }                
                
            }            
            
        }
        
        //Записываем обновлённый массив arrId.
        $this->saveConfDataJson($arrayIds, 'avito.ru.json');
        
        return $advertListNew;        
 
    }
    
   
    /**
     * 
     * @param type $adverts_list
     * Метод записываем объявления в базу и обновляет конфиг JSON файл.
     */
    public function insertAdverts($adverts_list, $db) {
        
         //Если пусто, то возвращает пустой false.
        if (count($adverts_list) == 0 ) {            
            return false;
        }
        
        $arrayIds = $this->readConfDataJson('avito.ru.json');

        foreach($adverts_list as $advert) {

            $arrayIds[$advert['id']] = date('Y-m-d');
            $blockTitle = $advert['addres'].'|'.$advert['countRooms'].'|'.$advert['area'].'|'.$advert['price'].'|'.$location_key.'|'.$metro_key;
            
            $id_user = null;
            if($advert['isAgent'] == true) {
              $id_user = -1;					
            }

            
          
            $db->insert('PARS_ADVERT', array(
                'SOURCE' => 'avito.ru',    
                'ID_USER'     => $id_user,                          
                'IS_PHOTO_COUNT'    => $advert['photoCount'], 
                'DATE_CREATE' 		=> time(),
                'BLOCK_TITLE' => $blockTitle,
                'PHOTO_PREW' => $advert['photoPrew']
            ));
            
            $ID_ADVERT = $db->lastInsertId();
    
            $db->insert('PARS_ADVERT_VALUE', array(
                'ID_ADVERT'     => $ID_ADVERT,
                'LOCATION'   	=> $location_key,
                'METRO' 	 => $metro_key,
                'SITE_NAME' 	 => 'avito.ru',
                'URL_ADVERT'	=> $advert['url'],
                'NAME'		 => $advert['nameContact'],
                'ABOUT'		=> $advert['about'],
                'ADDRES'	=> $advert['addres'],
                'TYPE'		=> $advert['typeObject'], 	
                'PRICE'		=> $advert['price'], 	
                'PHONE'		=> $advert['phoneValue'],	
                'ETAGE'		 => $advert['etage'],
                'ETAGE_HOUSE'	=> $advert['etageHouse'],	
                'AREA' 		 => $advert['area'],	

                'NUMBER_BED' 	=> $advert['COUNT_KROVAT'],
                'AREA_SLEEPING' => $advert['COUNT_SP_MEST'],

                'MULTIMEDIA'	=> serialize($advert['MULTIMEDIA']),			
                'APPLIANCES'    => serialize($advert['APPLIANCES']),	
                'COMFORT'	=> serialize($advert['COMFORT']),
                'ADDITIONALLY'	=> serialize($advert['ADDITIONALLY']),
                'TYPE_HOUSE'	=> $advert['typeHouse'],
                'COUNT_ROOMS' 		=> $advert['countRooms'],	
                'PHOTO_URLS' 		=> $advert['photos']
            ));
 

        }
        
         //Записываем обновлённый массив arrId.
        $this->saveConfDataJson($arrayIds, 'avito.ru.json');
        
        
        return true;
        
    }
    
    
}
    
