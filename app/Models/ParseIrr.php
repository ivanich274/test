<?php
namespace Models;

class ParseIrr extends Parse {
    

    public function listPage($page_number){
        
        $result = $this->getUrl('http://nizhniynovgorod.irr.ru/real-estate/rent/search/tab=users/sort/date_sort:desc/');        
        $paramsItems = $this->pregMatchAll('|<div class="listing__itemTitleWrapper">.*?<a href="(.*?)" class="listing__itemTitle">|sei',$result);
        
        return $paramsItems[1];  
        
    }
    
    public function getData($url) {
        
        $result = $this->getUrl($url);
        
        $items_params = $this->pregMatchAll('|<li class="productPage__infoColumnBlockText">(.*?):(.*?)</li>|s', $result);	

        $key = array_search('Этаж', $items_params[1]); 


        $etage = $this->getArrayKeyVal($items_params[1],$items_params[2],'Этаж');           
        $etageHouse = $this->getArrayKeyVal($items_params[1],$items_params[2],'Этажей в здании');
        $countRooms = $this->getArrayKeyVal($items_params[1],$items_params[2],'Комнат в квартире');  

        $area = (int)$this->getArrayKeyVal($items_params[1],$items_params[2],'Общая площадь');  
        $addres = "".$this->getArrayKeyVal($items_params[1],$items_params[2],'Улица').", ".$this->getArrayKeyVal($items_params[1],$items_params[2],'Дом');

        $isRoom = (int)$this->getArrayKeyVal($items_params[1],$items_params[2],'Комнат сдается');
        $typeObject = 'FLAT';        
        if ($isRoom > 0) {            
            $typeObject = 'ROOM';
        }
        
        $timeRent = 2;          
        $locationVal = $this->getArrayKeyVal($items_params[1],$items_params[2],'Район города'); 

        switch($this->getArrayKeyVal($items_params[1],$items_params[2],'Период аренды')) {

            case "Долгосрочная":
                $timeRent = 1;
            break;
        }
        //Комиссия
        switch($this->getArrayKeyVal($items_params[1],$items_params[2],'Комиссия')) {
            case "Без комиссии":
                $commPrice = 0;
            break;
            default :
                $commPrice = $this->getArrayKeyVal($items_params[1],$items_params[2],'Комиссия');
            break;
        }
        
        $items_params = $this->pregMatch('|<p class="productPage__descriptionText js-productPageDescription" itemprop="description">(.*?)</p>|s', $result);      
        $about = trim($items_params[1]);

        //Цена
        $items_params = $this->pregMatch('|<div class="productPageFixedContact__price">(.*?)</div>|s', $result);   
        //ЖЕСТЬ МЛЯТЬ
        $price =  urlencode($items_params[1]);    
        $price = str_replace('%C2%A0','',$price);        
        
        $price = (int)$this->trimAll(urldecode($price));      
               
        $nameContact = strip_tags(trim($this->pregMatch('|<div class="productPage__infoTextBold productPage__infoTextBold_inline">(.*?)</div>|s', $result)[1]));  

        $photos = $this->pregMatchAll('|<meta content="(.*?)" itemprop="image">|s', $result)[1];
        
        $photoCount = 0;
        $photoPrew ='';
        if(count($photos) > 0){
            
            $photoCount = count($photos);
            $photoPrew = $photos[0];
            $photos = serialize($photos);           
        }
        
        //productPage__infoColumnBlockText
        $items_params = $this->pregMatchAll('|<li class="productPage__infoColumnBlockText">(.*?)</li>|s', $result);


        $MULTIMEDIA = array ("WIFI" => "Интернет", "TV" => "Телевизор", "CTV" => "Кабельное / цифровое ТВ");
        $APPLIANCES = array("STOVE" => "Плита", "MICROWAVE" => "Микроволновка", "FRIDGE" => "Холодильник", "WASHER" => "Стиральная машина", "HAIRDRYER"=> "Фен", "IRON" => "Утюг" );			
        $COMFORT = array ("CONDITIONING"=> "Кондиционер", "FIREPLACE" =>"Камин", "BALCONY" => "Балкон/Лоджия", "PARKING" => "Парковочное место");	
        $ADDITIONALLY =  array("ANIMALS" => "Можно с питомцами", "CHILDREN" => "Можно с детьми", "EVENTS" => "Можно для мероприятий", "SMOKING" => "Можно курить");				

        $MULTIMEDIA_OUT = $this->GetParamsSearch($MULTIMEDIA, $items_params[1]);
        $APPLIANCES_OUT = $this->GetParamsSearch($APPLIANCES, $items_params[1]);
        $COMFORT_OUT = $this->GetParamsSearch($COMFORT, $items_params[1]);
        $ADDITIONALLY_OUT = $this->GetParamsSearch($ADDITIONALLY, $items_params[1]);

        $phone  = base64_decode($this->pregMatch('|<div class="productPageFixedContact__phoneText js-showContactPopup" data-phone="(.*?)">|s', $result)[1]);
        
        $LOCATION_LIST = array('','Автозаводский','Канавинский', 'Ленинский','Московский','Нижегородский','Приокский','Советский','Сормовский');
       
         $locationId = array_search($locationVal, $LOCATION_LIST);
       
        
        
        
        $params = array(             

            "etage" => $etage,
            "etageHouse" => $etageHouse,
            'countRooms' => $countRooms,
            'addres' => $addres,
            'timeRent' => $timeRent,
            'commPrice' => $commPrice,
            'about' => $about,
            'nameContact' => $nameContact,
            'photos' => $photos,
            'MULTIMEDIA' => $MULTIMEDIA_OUT,
            'APPLIANCES' => $APPLIANCES_OUT,
            'COMFORT'	=> $COMFORT_OUT,
            'ADDITIONALLY' => $ADDITIONALLY_OUT,
            'location' => $locationId,
            'phone' => $phone,
            'area' => $area,
            'price'=> $price,
            'photoCount' =>$photoCount,
            'photoPrew' => $photoPrew,
            'typeObject' => $typeObject

        );
        
        return $params;
           
    }
    /**
     * 
     * @param type $url
     * Метод определяющий ID объявлений по урлу.
     */
    public function getId($url){
        
        $result = $this->pregMatch('|.*?advert(.*?).html|sei', $url);        
        return $result[1];        
    }
    
    public function workingAdverts($adverts_list){
        
         //Если пусто, то возвращает пустой массив.
        if (count($adverts_list) == 0 ) {            
            return array();
        }
        //Запрашиваем массив ID уже обработанных объявлений.
        $arrayIds = $this->readConfDataJson('irr.ru.json');        
       
        $advertListNew = array();
        
        foreach($adverts_list as $advertUrl) {
            
            $idAdvert = $this->getId($advertUrl);
              
            //Нужно ли как то фильтровать их?.
            //Проверяем, есть ли такой ID в нашем файле.
            if(!array_key_exists($idAdvert, $arrayIds)) {
                
                sleep(3);
                
                $advert = $this->getData($advertUrl);                
                $advert['id'] = $idAdvert;
                $advert['url'] = $advertUrl;
                 
                $advertListNew[] = $advert;
              
            }          
            
        }
        
        return $advertListNew;
        
        
    }
    
    public function insertAdverts($adverts_list, $db) {
        
        
         //Если пусто, то возвращает пустой false.
        if (count($adverts_list) == 0 ) {            
            return false;
        }
        
        $arrayIds = $this->readConfDataJson('irr.ru.json');
           
        
        foreach($adverts_list as $advert) {

            $arrayIds[$advert['id']] = date('Y-m-d');
            $blockTitle = $advert['addres'].'|'.$advert['countRooms'].'|'.$advert['area'].'|'.$advert['price'].'|'.$advert['location'].'|'.$metro_key;
            
            $id_user = null;            
            
         
            $db->insert('PARS_ADVERT', array(
                'SOURCE' => 'irr.ru',    
                'ID_USER'     => $id_user,                          
                'IS_PHOTO_COUNT'    => $advert['photoCount'],
                'DATE_CREATE' 		=> time(),
                'BLOCK_TITLE' => $blockTitle,
                'PHOTO_PREW' => $advert['photoPrew']
            ));
            
            $ID_ADVERT = $db->lastInsertId();
    
            $db->insert('PARS_ADVERT_VALUE', array(
                'ID_ADVERT'     => $ID_ADVERT,
                'LOCATION'   	=> $advert['location'],
                'SITE_NAME' 	 => 'irr.ru',
                'URL_ADVERT'	=> $advert['url'],
                'NAME'		 => $advert['nameContact'],
                'ABOUT'		=> $advert['about'],
                'ADDRES'	=> $advert['addres'],
                'TYPE'		=> $advert['typeObject'], 	
                'PRICE'		=> $advert['price'], 	
                'PHONE'		=> $advert['phone'],	
                'ETAGE'		 => $advert['etage'],
                'ETAGE_HOUSE'	=> $advert['etageHouse'],	
                'AREA' 		 => $advert['area'],	
                'MULTIMEDIA'	=> serialize($advert['MULTIMEDIA']),			
                'APPLIANCES'    => serialize($advert['APPLIANCES']),	
                'COMFORT'	=> serialize($advert['COMFORT']),
                'ADDITIONALLY'	=> serialize($advert['ADDITIONALLY']),
                'COUNT_ROOMS' 		=> $advert['countRooms'],	
                'PHOTO_URLS' 		=> $advert['photos']
            ));
 
        }
        
         //Записываем обновлённый массив arrId.
        $this->saveConfDataJson($arrayIds, 'irr.ru.json');
        
        
        return true;
    }
    
    
}