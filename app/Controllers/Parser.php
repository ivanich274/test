<?php

namespace Controllers;

use Silex\Application;
use Silex\Route;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Models;
use Symfony\Component\HttpFoundation\Request;

class Parser implements ControllerProviderInterface{  
 
    public function connect(Application $app) {    
          
        /** @var $backend \Silex\ControllerCollection */
        $backend = $app['controllers_factory'];    
        /**
         * Очистка конфиг JSON файлов, раз в сутки.
         */
        $backend->get('/clear', function (Request $request) use ($app) {
            
            
            $abstractResurs = new Models\ParseIrr("","");

            //Проверяем и очищаем конфиг файл.
            $abstractResurs->clearConfDataJson('avito.ru.json', 30);
            $abstractResurs->clearConfDataJson('irr.ru.json', 30);   
            
            return '';
            
        });
        
        /**
         * Парсер сайта irr.ru
         */
        $backend->get('/irr', function (Request $request) use ($app) {            
            
            ini_set('max_execution_time', 0); //0=NOLIMIT
            set_time_limit(0);    
            ignore_user_abort(true);       
            ini_set('display_errors','off');

            $ParseIrr = new Models\ParseIrr("37.139.33.244:44800","");
            $advertList = $ParseIrr->listPage(1);  
            //Обрабатываем, фильтруем, собираем информацию.
            $advertListNew = $ParseIrr->workingAdverts($advertList);
            //Записываем в базу, обновляем конфиг json файл.        
            $ParseIrr->insertAdverts($advertListNew, $app['db']);
            
            return '';
            
        });
        
        /**
         * Парсер сайта avito.ru.
         */
        $backend->get('/avito', function (Request $request) use ($app) {    

            ini_set('max_execution_time', 0); //0=NOLIMIT
            set_time_limit(0);    
            ignore_user_abort(true);    
            ini_set('display_errors','off');
            //Авито.
            $ParseAvito  = new Models\ParseAvito("37.139.33.244:44800","");
            //Все новые объявления.
            $advertList =  $ParseAvito->listPage(1);      

            //Обрабатываем, фильтруем, собираем информацию.
            $advertListNew = $ParseAvito->workingAdverts($advertList);

            //Записываем в базу, обновляем конфиг json файл.    
            $ParseAvito->insertAdverts($advertListNew, $app['db']);
            
            return '';
            
        });
        
        
        return $backend;
        
    }
}