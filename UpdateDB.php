<?php
    //Скачать библиотеку - http://phpexcel.codeplex.com/
    //Нашел русскую документацию - http://www.cyberforum.ru/php-beginners/thread1074684.html
    //Подключаем скачаную библиотеку
    include("Classes/PHPExcel.php");
    require "libs/phpQuery.php";
    require "db.php";
    header('content-type: text/html;charset=utf-8');

    function get_http_response_code($url) {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }

    function photo_check($i,$article){
        $photo_url='http://demandware.edgesuite.net/sits_pod14-adidas/dw/image/v2/aagl_prd/on/demandware.static/-/Sites-adidas-products/default/dw5d486956/zoom/'.$article.'_0'.$i.'_standard.jpg';
        $response_code = get_http_response_code($photo_url);
        if($response_code==200){
            return $photo_url;
        }
        else{
            return 'No photo yet';
        }
    }
    //изменено 22 05
    function get_type_excel($name){
        $arr=explode(" ", $name);
            $i=0;
            foreach ($arr as $key) {
                for ($i=1;$i<=10;$i++) {
                    $where='keyword_'.$i.' LIKE ?';
                    $find=R::findOne('categories',$where,array($key));
                    if(isset($find)){
                        if($find->describtion=='sub_type')
                            return $find;
                        elseif($find->describtion=='type'){
                            $id=$find->id+1;
                            $find=R::findOne('categories','id = ?',array($id));
                        }
                    }
                }
            }
            $find=R::findOne('categories','name = ? AND describtion = ?',array('Прочее','sub_type'));
            return $find;
    }
    function get_gender($name){
        $arr=explode(" ", $name);
            $i=0;
            foreach ($arr as $key) {
                for ($i=1;$i<=10;$i++) {
                    $where='keyword_'.$i.' = ? AND describtion = ?';
                    $find=R::findOne('categories',$where,array($key,'gender'));
                    if(isset($find)){
                        return $find;
                    }
                }
            }
            $find=R::findOne('categories','name = ? AND describtion = ?',array('Прочее','gender'));
            return $find;
    }
    function get_brand($name){
        $arr=explode(" ", $name);
            $i=0;
            foreach ($arr as $key) {
                for ($i=1;$i<=10;$i++) {
                    $where='keyword_'.$i.' = ? AND describtion = ?';
                    $find=R::findOne('categories',$where,array($key,'producer'));
                    if(isset($find)){
                        return $find;
                    }
                }
            }
            $find=R::findOne('categories','name = ? AND describtion = ?',array('Неопределенный бренд','producer'));
            return $find;
    }
    function get_collection($name){
        $arr=explode(" ", $name);
            $i=0;
            foreach ($arr as $key) {
                for ($i=1;$i<=10;$i++) {
                    $where='keyword_'.$i.' = ? AND describtion = ?';
                    $find=R::findOne('categories',$where,array($key,'brand'));
                    if(isset($find)){
                        return $find;
                    }
                }
            }
            $find=R::findOne('categories','name = ? AND describtion = ?',array('Другие коллекции','brand'));
            return $find;
    }

    function get_sport($name){
        $arr=explode(" ", $name);
            $i=0;
            foreach ($arr as $key) {
                for ($i=1;$i<=10;$i++) {
                    $where='keyword_'.$i.' = ? AND describtion = ?';
                    $find=R::findOne('categories',$where,array($key,'sport'));
                    if(isset($find)){
                        return $find;
                    }
                }
            }
            $find=R::findOne('categories','name = ? AND describtion = ?',array('Другой вид спорта','sport'));
            return $find;
    }

    function data_parser($article,$flag){
        if($flag==0)
        {
            $url = 'http://www.adidas.ru/search?q='.$article;
            $file = file_get_contents($url);//скачиваем страницу по url

            $html_code = htmlspecialchars($file);//для вывода html
            $pos = strpos($html_code, 'Мы не смогли ничего найти по Вашему запросу: ');
            if ($pos != false) {
                $url = 'http://www.reebok.ru/search?q='.$article;
                $file = file_get_contents($url);
                $html_code = htmlspecialchars($file);
                $pos = strpos($html_code, 'Написать комментарий');
                if ($pos === false){
                    $res=data_parser_co_uk($article,0);
                    if($res===false){
                        $res=data_parser_draft($article);
                        if($res===false){
                            $res=data_parser_adidas_net_ua($article);
                            if($res===false){
                                $res=data_parser_oneteam($article);
                                if($res===false){
                                    $res=data_parser_reebok_shop_in_ua($article);
                                    if($res===false){
                                        $res=data_parser_adilike($article);
                                        if($res===false){
                                            $res=data_parser_clubsale($article);
                                            if($res===false){
                                                $product=R::dispense('unique');
                                                $product->article=$article;
                                                $product->code='404';
                                                $gender=get_gender(trim('Прочее'));
                                                $product->gender_name=$gender->name;
                                                $product->gender_id=$gender->id;
                                                $product->sport_name='Другой вид спорта';
                                                $sport=R::findOne('categories','describtion = ? AND name = ?',array('sport','Другой вид спорта'));
                                                $product->sport_id=$sport->id;
                                                $product->col_name='Другие коллекции';
                                                $col=R::findOne('categories','describtion = ? AND name = ?',array('brand','Другие коллекции'));
                                                $product->col_id=$col->id;
                                                $product->photo_1=photo_check(1,$article);
                                                R::store($product);
                                                return false;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    return true;
                }
                else{
                    $brand = 'reebok';
                    $html = phpQuery::newDocument($file);
                }
            }
            else{
                $url1 = 'http://www.reebok.ru/search?q='.$article;
                $file1 = file_get_contents($url1);
                $html_code1 = htmlspecialchars($file1);
                $pos1 = strpos($html_code1, 'Написать комментарий');
                if($pos1 != false){
                    $flag=1;
                    data_parser($article,$flag);
                }
                $brand = 'adidas';
                $html = phpQuery::newDocument($file);
            }
        }
        elseif($flag==1){
            $url = 'http://www.reebok.ru/search?q='.$article;
            $file = file_get_contents($url);
            $html = phpQuery::newDocument($file);
            $brand='reebok';
        }
                $article_check= pq($html)->find('div[class*="product-color para-small"]')->text();
                $pos_check= strpos($article_check,$article);
                if($pos_check===false){
                    $res=data_parser_co_uk($article,0);
                    if($res===false){
                        $res=data_parser_draft($article);
                        if($res===false){
                            $res=data_parser_adidas_net_ua($article);
                            if($res===false){  
                                $res=data_parser_oneteam($article);
                                if($res===false){
                                    $res=data_parser_reebok_shop_in_ua($article);
                                    if($res===false){
                                        $res=data_parser_adilike($article);
                                        if($res===false){
                                            $res=data_parser_clubsale($article);
                                            if($res===false){
                                                $product=R::dispense('unique');
                                                $product->article=$article;
                                                $product->code='404';
                                                $gender=get_gender(trim('Прочее'));
                                                $product->gender_name=$gender->name;
                                                $product->gender_id=$gender->id;
                                                $product->sport_name='Другой вид спорта';
                                                $sport=R::findOne('categories','describtion = ? AND name = ?',array('sport','Другой вид спорта'));
                                                $product->sport_id=$sport->id;
                                                $product->col_name='Другие коллекции';
                                                $col=R::findOne('categories','describtion = ? AND name = ?',array('brand','Другие коллекции'));
                                                $product->col_id=$col->id;
                                                $product->photo_1=photo_check(1,$article);
                                                R::store($product);
                                                return false;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    return true;
                }

                $photoCount=0;
                foreach ($html->find('segment:eq(0)')->find('div[id*="main-section"]')->find('div[id*="productInfo"]')->find('div[class*="col-8"]')->find('div[class*="image-carousel-zoom-container"]')->find('div[class*="image-carousel-container"]')->find('div[id*="image-carousel"]')->find('div[class*="pdp-image-carousel track stack"]')->find('ul[class*="pdp-image-carousel-list"]')->find('li') as $el){
                    $photo = pq($el)->find('img')->attr('src');
                    $photo = substr( $photo,0,-11).'2000&sfrm=jpg';
                    $photoCount++;
                    $photoArray[$photoCount] = $photo;
                }
                
                $model=pq($html)->find('segment:eq(0)')->find('div[id*="main-section"]')->find('div[id*="productInfo"]')->find('div[class*="col-8"]')->find('div[class*="image-carousel-zoom-container"]')->find('div[id*="main-image"]')->find('div:eq(1)')->text();
                
                
                $short_describtion=pq($html)->find('span[class*="pdp-category-in"]')->text();//Gender Col/Sport
                $gender=get_gender(trim($short_describtion));
                if ($gender->name=='Прочее'){
                    $buf=R::dispense('keywords');
                    $buf->gender=$short_describtion;
                    R::store($buf);
                }
                $gender_name=$gender->name;
                $gender_id=$gender->id;
                $collection=substr($short_describtion,strpos($short_describtion,' ')+1);
                
                $name = pq($html)->find('segment:eq(0)')->find('div[id*="main-section"]')->find('div[id*="productInfo"]')->find('div[class*="col-4"]')->find('div[id*="buy-block"]')->find('div[class*="buy-block-header"]')->find('h1')->text();
                $colour = pq($html)->find('segment:eq(0)')->find('div[id*="main-section"]')->find('div[id*="productInfo"]')->find('div[class*="col-4"]')->find('div[id*="buy-block"]')->find('div[class*="buy-block-header"]')->find('div[class*="rbk-rounded-block "]')->find('div:eq(0)')->find('span[class*="product-color-clear"]')->text();

                $sub_type=get_type_excel($name);
                if ($sub_type->name=='Прочее'){
                    $buf=R::dispense('keywords');
                    $buf->sub_type=$name;
                    R::store($buf);
                }
                $sub_type_name=$sub_type->name;
                $sub_type_id= $sub_type->id;
                $type_id=$sub_type->parent_id;

                $sport_name=R::findOne('categories','describtion = ? AND name =?',array('sport',trim($collection)))->name;
                if(!isset($sport_name)){
                    $sport_name='Другой вид спорта';
                    $buf=R::dispense('keywords');
                    $buf->sport=$collection;
                    R::store($buf);
                }
                $sport_id=R::findOne('categories','describtion = ? AND name =?',array('sport',$sport_name))->id;

                $collection_name=R::findOne('categories','describtion = ? AND name =?',array('brand',trim($collection)))->name;
                if(!isset($collection_name)){
                    $collection_name='Другие коллекции';
                    $buf=R::dispense('keywords');
                    $buf->collection=$collection;
                    R::store($buf);
                }
                $collection_id=R::findOne('categories','describtion = ? AND name =?',array('brand',$collection_name))->id;

                $sameCount=0;
                foreach ($html->find('div[id*="colorVariationsCarousel"]')->find('div[class*="color-variation-row"]')->find('div') as $key) {
                        $sameCount++;
                        $sameArray[$sameCount] = pq($key)->attr('data-articleno');
                }
                
                $type_from_site = pq($html)->find('segment:eq(1)')->find('div:eq(0)')->find('h4')->text();
                $describtion = pq($html)->find('segment:eq(1)')->find('div[itemprop*="description"]')->text();

                $advantagesCount=0;
                foreach ($html->find('segment:eq(1)')->find('div:eq(0)')->find('ul')->find('li') as $key) {
                        $advantagesCount++;
                        $advantagesArray[$advantagesCount] = pq($key)->text();
                }  

                phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
                $product=R::dispense('unique');
                $product->article = $article;
                $product->sport_name=$sport_name;
                $product->sport_id=$sport_id;
                $product->col_name=$collection_name;
                $product->col_id=$collection_id;
                $product->gender_name=$gender_name;
                $product->gender_id=$gender_id;
                $product->url=$url;
                if($name==''){
                    $product->code='500';
                    $product->photo_1=photo_check(1,$article);
                    R::store($product);
                }
                else{
                    $product->code='200';
                    $product->brand = $brand;
                    $product->brand_id = R::findOne('categories','name = ?',array($brand))->id;
                    $product->model=$model;
                    $product->type_id=$type_id;
                    $product->type_name = R::findOne('categories','id = ?',array($type_id))->name;
                    $product->sub_type_name = $sub_type_name;
                    $product->sub_type_id = $sub_type_id;
                    $product->type_from_site= $type_from_site;
                    $product->name=$name;
                    $product->colour=$colour;
                    $product->describtion=$describtion;
                    $product->short_describtion=$short_describtion;
                    R::store($product);
                    for($i=1;$i<=$photoCount;$i++){
                        $query="UPDATE `unique` SET `unique`.`photo_".$i."`='".$photoArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";
                        R::exec($query);
                    }
                    for($i=1;$i<=$sameCount;$i++){
                        $query="UPDATE `unique` SET `unique`.`other_colour_".$i."`='".$sameArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";;
                        R::exec($query);
                    }
                    for($i=1;$i<=$advantagesCount;$i++){
                        $query="UPDATE `unique` SET `unique`.`advantages_".$i."`='".$advantagesArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";;
                        R::exec($query);
                    }
                }
                return true;
    }
    function data_parser_co_uk($article,$flag){
        if($flag==0)
        {
            $url = 'http://www.adidas.co.uk/search?q='.$article;
            $file = file_get_contents($url);//скачиваем страницу по url

            $html_code = htmlspecialchars($file);//для вывода html
            $pos = strpos($html_code, 'We are sorry but no results were found for: ');
            if ($pos != false) {
                $url = 'http://www.reebok.co.uk/search?q='.$article;
                $file = file_get_contents($url);
                $html_code = htmlspecialchars($file);
                $pos = strpos($html_code, 'RATINGS & REVIEWS');
                if ($pos === false){
                    return false;
                }
                else{
                    $brand = 'reebok';
                    $html = phpQuery::newDocument($file);
                }
            }
            else{
                $url1 = 'http://www.reebok.co.uk/search?q='.$article;
                $file1 = file_get_contents($url1);
                $html_code1 = htmlspecialchars($file1);
                $pos1 = strpos($html_code1, 'RATINGS & REVIEWS');
                if($pos1 != false){
                    $flag=1;
                    data_parser_co_uk($article,$flag);
                }
                $brand = 'adidas';
                $html = phpQuery::newDocument($file);
            }
        }
        elseif($flag==1){
            $url = 'http://www.reebok.co.uk/search?q='.$article;
            $file = file_get_contents($url);
            $html = phpQuery::newDocument($file);
            $brand='reebok';
        }
                $photoCount=0;
                foreach ($html->find('segment:eq(0)')->find('div[id*="main-section"]')->find('div[id*="productInfo"]')->find('div[class*="col-8"]')->find('div[class*="image-carousel-zoom-container"]')->find('div[class*="image-carousel-container"]')->find('div[id*="image-carousel"]')->find('div[class*="pdp-image-carousel track stack"]')->find('ul[class*="pdp-image-carousel-list"]')->find('li') as $el){
                    $photo = pq($el)->find('img')->attr('src');
                    $photo = substr( $photo,0,-11).'2000&sfrm=jpg';
                    $photoCount++;
                    $photoArray[$photoCount] = $photo;
                }
                
                $model=pq($html)->find('segment:eq(0)')->find('div[id*="main-section"]')->find('div[id*="productInfo"]')->find('div[class*="col-8"]')->find('div[class*="image-carousel-zoom-container"]')->find('div[id*="main-image"]')->find('div:eq(1)')->text();
                
                
                $short_describtion=pq($html)->find('span[class*="pdp-category-in"]')->text();//Gender Col/Sport
                $gender=get_gender(trim($short_describtion));
                $gender_name=$gender->name;
                $gender_id=$gender->id;
                $collection=substr($short_describtion,strpos($short_describtion,' ')+1);
                
                $name = pq($html)->find('segment:eq(0)')->find('div[id*="main-section"]')->find('div[id*="productInfo"]')->find('div[class*="col-4"]')->find('div[id*="buy-block"]')->find('div[class*="buy-block-header"]')->find('h1')->text();
                $colour = pq($html)->find('segment:eq(0)')->find('div[id*="main-section"]')->find('div[id*="productInfo"]')->find('div[class*="col-4"]')->find('div[id*="buy-block"]')->find('div[class*="buy-block-header"]')->find('div[class*="rbk-rounded-block "]')->find('div:eq(0)')->find('span[class*="product-color-clear"]')->text();
                $article_check= pq($html)->find('div[class*="product-color para-small"]')->text();
                $pos_check= strpos($article_check,$article);
                if($pos_check===false)
                    return false;
                $sub_type=get_type_excel($name);
                if ($sub_type->name=='Прочее'){
                    $buf=R::dispense('keywords');
                    $buf->sub_type=$name;
                    R::store($buf);
                }
                $sub_type_name=$sub_type->name;
                $sub_type_id= $sub_type->id;
                $type_id=$sub_type->parent_id;
                $sport_name=R::findOne('categories','describtion = ? AND name =?',array('sport',trim($collection)))->name;
                if(!isset($sport_name)){
                    $sport_name='Другой вид спорта';
                    $buf=R::dispense('keywords');
                    $buf->sport=$collection;
                    R::store($buf);
                }
                $sport_id=R::findOne('categories','describtion = ? AND name =?',array('sport',$sport_name))->id;

                $collection_name=R::findOne('categories','describtion = ? AND name =?',array('brand',trim($collection)))->name;
                if(!isset($collection_name)){
                    $collection_name='Другие коллекции';
                    $buf=R::dispense('keywords');
                    $buf->collection=$collection;
                    R::store($buf);
                }
                $collection_id=R::findOne('categories','describtion = ? AND name =?',array('brand',$collection_name))->id;

                $sameCount=0;
                foreach ($html->find('div[id*="colorVariationsCarousel"]')->find('div[class*="color-variation-row"]')->find('div') as $key) {
                        $sameCount++;
                        $sameArray[$sameCount] = pq($key)->attr('data-articleno');
                }
                
                $type_from_site = pq($html)->find('segment:eq(1)')->find('div:eq(0)')->find('h4')->text();
                $describtion = pq($html)->find('segment:eq(1)')->find('div[itemprop*="description"]')->text();

                $advantagesCount=0;
                foreach ($html->find('segment:eq(1)')->find('div:eq(0)')->find('ul')->find('li') as $key) {
                        $advantagesCount++;
                        $advantagesArray[$advantagesCount] = pq($key)->text();
                }  

                phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
                $product=R::dispense('unique');//findOne('unique','article = ?',array($article));
                $product->article = $article;
                $product->sport_name=$sport_name;
                $product->sport_id=$sport_id;
                $product->col_name=$collection_name;

                $product->col_id=$collection_id;
                $product->gender_name=$gender_name;
                $product->gender_id=$gender_id;
                $product->url=$url;
                if($name==''){
                    $product->code='500';
                    $product->photo_1=photo_check(1,$article);
                    R::store($product);
                }
                else{
                    $product->code='200';
                    $product->brand = $brand;
                    $product->brand_id = R::findOne('categories','name = ?',array($brand))->id;
                    $product->model=$model;
                    $product->type_id=$type_id;
                    $product->type_name = R::findOne('categories','id = ?',array($type_id))->name;
                    $product->sub_type_name = $sub_type_name;
                    $product->sub_type_id = $sub_type_id;
                    $product->type_from_site= $type_from_site;
                    $product->name=$name;
                    $product->colour=$colour;
                    $product->describtion=$describtion;
                    $product->short_describtion=$short_describtion;
                    R::store($product);
                    for($i=1;$i<=$photoCount;$i++){
                        $query="UPDATE `unique` SET `unique`.`photo_".$i."`='".$photoArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";
                        R::exec($query);
                    }
                    for($i=1;$i<=$sameCount;$i++){
                        $query="UPDATE `unique` SET `unique`.`other_colour_".$i."`='".$sameArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";;
                        R::exec($query);
                    }
                    for($i=1;$i<=$advantagesCount;$i++){
                        $query="UPDATE `unique` SET `unique`.`advantages_".$i."`='".$advantagesArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";;
                        R::exec($query);
                    }
                }
                return true;
    }
     function data_parser_clubsale($article){
        $url = 'https://clubsale.com.ua/index.php?lan=rus&page=catalog&param=staff&znak=search&q='.$article.'&x=0&y=0';
        $file = file_get_contents($url);//скачиваем страницу по url

        $html_code = htmlspecialchars($file);//для вывода html

        $pos = strpos($html_code, 'Сейчас, этот товар отсутствует. Подберите себе, пожалуйста, другую модель.');
        if ($pos != false) 
            return false;
        
        $html = phpQuery::newDocument($file);
        $ref=pq($html)->find('div[class*="content"]')->find('div[class*="inf"]')->find('div[class*="tovar"]')->find('div[class*="konv"]')->find('div[class*="diks"]')->find('a[target*="_blank"]')->attr('href');

        $url='https://clubsale.com.ua'.$ref;   
        $file = file_get_contents($url);//скачиваем страницу по url
        $html = phpQuery::newDocument($file);
        $gender=pq($html)->find('div[class*="content"]')->find('div[class*="inf"]')->find('div[class*="path"]')->find('div[class*="breadcrumb"]')->find('span:eq(1)')->find('a')->text();

        $type=pq($html)->find('div[class*="content"]')->find('div[class*="inf"]')->find('div[class*="path"]')->find('div[class*="breadcrumb"]')->find('span:eq(2)')->find('a')->text();

        $brand_name=pq($html)->find('div[class*="content"]')->find('div[class*="inf"]')->find('div[class*="path"]')->find('div[class*="breadcrumb"]')->find('span:eq(3)')->find('a')->text();

        $col_name=pq($html)->find('div[class*="content"]')->find('div[class*="inf"]')->find('div[class*="path"]')->find('div[class*="breadcrumb"]')->find('span:eq(4)')->find('a')->text();

        $gender=get_gender($gender);
        $gender_id=$gender->id;
        $gender_name=$gender->name;
        $sub_type=get_type_excel($type);
        if ($sub_type->name=='Прочее'){
            $buf=R::dispense('keywords');
            $buf->sub_type=$type;
            R::store($buf);
        }
        $sub_type_id=$sub_type->id;
        $sub_type_name=$sub_type->name;
        $collection=get_collection($col_name);
        if ($collection->name=='Другие коллекции'){
            $buf=R::dispense('keywords');
            $buf->collection=$col_name;
            R::store($buf);
        }
        $collection_id=$collection->id;
        $collection_name=$collection->name;        
        $brand=get_brand($brand_name);
        //нету спорта
        $sport=get_sport($col_name);
        $sport_name=$sport->name;
        $sport_id=$sport->id;

        $type=R::findOne('categories','id = ?',array($sub_type->parent_id));
        $type_name=$type->name;
        $type_id=$type->id;

        $product=R::dispense('unique');//findOne('unique','article = ?',array($article));
        $product->article=$article;
        $product->sport_name=$sport_name;
        $product->sport_id=$sport_id;
        $product->col_name=$collection_name;
        $product->col_id=$collection_id;
        $product->type_id=$type_id;
        $product->type_name=$type_name;
        $product->sub_type_id=$sub_type_id;
        $product->sub_type_name=$sub_type_name;
        $product->gender_name=$gender_name;
        $product->gender_id=$gender_id;
        $product->brand=$brand->name;
        $product->brand_id = $brand->id;
        $product->code='200';
        $product->url=$url;
        R::store($product);
        phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
        return true;
    }
    function data_parser_draft($article){
        $url = 'https://draft.in.ua/search/?%5B%5D='.$article;
        $file = file_get_contents($url);//скачиваем страницу по url

        $html_code = htmlspecialchars($file);//для вывода html

        $pos = strpos($html_code, 'По данному запросу товаров не найдено.');
        if ($pos != false) 
            return false;
        
        $html = phpQuery::newDocument($file);
        $ref=pq($html)->find('div[id*="content"]')->find('div[id*="contents"]')->find('div[id*="content_text"]')->find('div[class*="products-list"]')->find('div:eq(0)')->find('div:eq(0)')->find('a')->attr('href');

        $url='https://draft.in.ua'.$ref;   
        
        $file = file_get_contents($url);//скачиваем страницу по url
        $html = phpQuery::newDocument($file);
        $check_article=pq($html)->find('#content_text > div.description.cart_product > div.price_left > div.sku > span')->text();
        if(strnatcasecmp(trim($check_article),$article)!=0){
            return false;
        }
        $gender_and_type=pq($html)->find('div[id*="content"]')->find('ul[class*="way"]')->find('li:eq(4)')->find('a:eq(0)')->find('span:eq(0)')->text();


        $sub_type_pars=pq($html)->find('div[id*="content"]')->find('ul[class*="way"]')->find('li:eq(6)')->find('a:eq(0)')->find('span:eq(0)')->text();


        $brand_pars=pq($html)->find('div[id*="content"]')->find('ul[class*="way"]')->find('li:eq(8)')->find('a:eq(0)')->find('span:eq(0)')->text();


        $name=pq($html)->find('div[id*="content"]')->find('div[id*="contents"]')->find('div[id*="content_text"]')->find('h1:eq(0)')->text();        


        $photo=pq($html)->find('img[class*="zoomImg"]')->attr('src');
        $photoCount=0;
        foreach ($html->find('a[rel*="prettyPhoto[product]"]') as $el) {
            $photoCount++;
            $photoArray[$photoCount]=pq($el)->attr('href');
        }

        $gender=get_gender($gender_and_type);       
        if ($gender->name=='Прочее'){
            $buf=R::dispense('keywords');
            $buf->gender=$gender_and_type;
            R::store($buf);
        }
        $gender_id=$gender->id;
        $gender_name=$gender->name;

        $sub_type=get_type_excel($sub_type_pars);        
        if ($sub_type->name=='Прочее'){
            $buf=R::dispense('keywords');
            $buf->sub_type=$sub_type_pars;
            R::store($buf);
        }
        $sub_type_id=$sub_type->id;
        $sub_type_name=$sub_type->name;

        $collection=get_collection($name);        
        if ($collection->name=='Другие коллекции'){
            $buf=R::dispense('keywords');
            $buf->collection=$name;
            R::store($buf);
        }
        $collection_id=$collection->id;
        $collection_name=$collection->name;

        $sport=get_sport($sub_type_pars);        
        if ($sport->name=='Другой вид спорта'){
            $buf=R::dispense('keywords');
            $buf->sport=$sub_type_pars;
            R::store($buf);
        }
        $sport_name=$sport->name;
        $sport_id=$sport->id;

        $type=R::findOne('categories','id = ?',array($sub_type->parent_id));
        $type_name=$type->name;
        $type_id=$type->id;
        $brand=get_brand($name);
        $product=R::dispense('unique');//findOne('unique','article = ?',array($article));
        $product->article=$article;
        $product->name=$name;
        $product->code='200';
        $product->sport_name=$sport_name;
        $product->sport_id=$sport_id;
        $product->col_name=$collection_name;
        $product->col_id=$collection_id;
        $product->type_id=$type_id;
        $product->type_name=$type_name;
        $product->sub_type_id=$sub_type_id;
        $product->sub_type_name=$sub_type_name;
        $product->gender_name=$gender_name;
        $product->gender_id=$gender_id;
        $product->brand=$brand->name;
        $product->brand_id=$brand->id;
        $product->url=$url;
        R::store($product);
        for($i=1;$i<=$photoCount;$i++){
            $query="UPDATE `unique` SET `unique`.`photo_".$i."`='".$photoArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";
            R::exec($query);
        }
        phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
        return true;
    }
    //adilike.kiev.ua
    function data_parser_adilike($article){
        $url = 'http://adilike.kiev.ua/index.php?route=product/search&filter_name='.$article;
        $file = file_get_contents($url);//скачиваем страницу по url

        $html_code = htmlspecialchars($file);//для вывода html

        if (strlen($html_code)<10)
            return false;
        
        $html = phpQuery::newDocument($file);
        $ref=pq($html)->find('div[id*="container"]')->find('div[id*="content"]')->find('div[class*="product-list"]')->find('div[class*="name"]')->find('a:eq(0)')->attr('href');

        $file = file_get_contents($ref);
        $html = phpQuery::newDocument($file);

        $brand_name=pq($html)->find('div[class*="product-info"]')->find('div[class*="right"]')->find('div[class*="description"]')->find('a:eq(0)')->text();
        $brand=get_brand($brand_name);
        // $name=pq($html)->find('div[class*="product-info"]')->find('div[class*="right"]')->find('div[class*="description"]')->find('span:eq(2)')->text();

        $check_article=pq($html)->find('div[class*="product-info"]')->find('div[class*="right"]')->find('div[class*="description"]')->find('span:eq(6)')->text();

        if(strnatcasecmp(trim($check_article),$article)!=0){
            return false;
        }

        $photoArray[1]=pq($html)->find('div[id*="container"]')->find('div[id*="content"]')->find('div[class*="product-info"]')->find('div[class*="left"]')->find('div[class*="image"]')->find('a:eq(0)')->attr('href');

        $photoCount=1;
        foreach ($html->find('div[class*="product-info"]')->find('div[class*="left"]')->find('div[class*="image"]')->find('a') as $photo) {
            $photoCount++;
            $photoArray[$photoCount]=pq($photo)->attr('href');
        }

        $name=pq($html)->find('div[id*="container"]')->find('div[id*="content"]')->find('div[id*="tab-description"]')->find('h2')->text();
        $short_describtion=pq($html)->find('div[id*="container"]')->find('div[id*="content"]')->find('div[id*="tab-description"]')->find('h4')->text();
        $describtion=pq($html)->find('div[id*="container"]')->find('div[id*="content"]')->find('div[id*="tab-description"]')->find('div:eq(0)')->find('div:eq(0)')->text();
        $tag=pq($html)->find('div[id*="container"]')->find('div[id*="content"]')->find('div[class*="tags"]')->find('a:eq(0)')->text();
        $advantagesCount=0;
        foreach ($html->find('div[id*="container"]')->find('div[id*="content"]')->find('div[id*="tab-description"]')->find('div:eq(0)')->find('ul')->find('li') as $advantage) {
            $advantagesCount++;
            $advantagesArray[$advantagesCount]=pq($advantage)->text();
        }

        $gender=get_gender($describtion);
        if ($gender->name=='Прочее'){
            $gender=get_gender($tag);
            if ($gender->name=='Прочее'){
                $buf=R::dispense('keywords');
                $buf->gender=$tag;
                R::store($buf);
            }
        }
        $gender_id=$gender->id;
        $gender_name=$gender->name;
        //$buf_arr=explode(' ',$name);
        $sub_type=get_type_excel($name);        
        if ($sub_type->name=='Прочее'){
            $sub_type=get_type_excel($tag);
                if ($sub_type->name=='Прочее'){
                $buf=R::dispense('keywords');
                $buf->sub_type=$name;
                R::store($buf);
            }
        }        
        $sub_type_id=$sub_type->id;
        $sub_type_name=$sub_type->name;

        $type=R::findOne('categories','id = ?',array($sub_type->parent_id));
        $type_name=$type->name;
        $type_id=$type->id;

        $collection=get_collection($describtion); 
        $collection_id=$collection->id;
        $collection_name=$collection->name;

        $sport=get_sport($describtion);
        $sport_name=$sport->name;
        $sport_id=$sport->id;

        $product=R::dispense('unique');
        $product->name=$name;
        $product->article=$article;
        $product->describtion=$describtion;
        $product->short_describtion=$short_describtion;
        $product->sport_name=$sport_name;
        $product->sport_id=$sport_id;
        $product->col_name=$collection_name;
        $product->col_id=$collection_id;
        $product->type_id=$type_id;
        $product->type_name=$type_name;
        $product->sub_type_id=$sub_type_id;
        $product->sub_type_name=$sub_type_name;
        $product->gender_name=$gender_name;
        $product->gender_id=$gender_id;
        $product->brand=$brand->name;
        $product->brand_id=$brand->id;
        $product->code='200';
        $product->url=$url;
        R::store($product);
        for($i=1;$i<=$photoCount;$i++){
            $query="UPDATE `unique` SET `unique`.`photo_".$i."`='".$photoArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";
            R::exec($query);
        }
        for($i=1;$i<=$advantagesCount;$i++){
            $query="UPDATE `unique` SET `unique`.`advantages_".$i."`='".$advantagesArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";;
            R::exec($query);
        }
        phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
        return true;
    }
    //oneteam.com.ua
    function data_parser_oneteam($article){
        $url = 'https://oneteam.com.ua/search?s='.$article;
        $file = file_get_contents($url);//скачиваем страницу по url

        $html_code = htmlspecialchars($file);//для вывода html

        $pos = strpos($html_code, 'По вашему запросу ничего не найдено.');
        if ($pos != false) 
            return false;
        
        $html = phpQuery::newDocument($file);
        $ref='https://oneteam.com.ua'.pq($html)->find('div[id*="main"]')->find('div[class*="container"]')->find('div[id*="main-page-content"]')->find('div[class*="content"]')->find('div[class*="mediaholder"]')->find('a:eq(0)')->attr('href');
        $file = file_get_contents($ref);
        $html = phpQuery::newDocument($file);

        $type_gender_brand=pq($html)->find('div[class*="container"]')->find('nav[id*="breadcrumbs"]')->find('ul')->find('li:eq(3)')->text();

        $check_article=pq($html)->find('div[class*="container"]')->find('div[class*="row"]')->find('div[class*="content"]')->find('div[class*="commerce-product-sku"]')->find('span')->text();

        if(strnatcasecmp(trim($check_article),$article)!=0){
            return false;
        }

        $collection=pq($html)->find('div[class*="field_manufacturer_content"]')->find('a')->find('img')->attr('alt');
        $colour = pq($html)->find('div[class*="product-title"]')->find('div[class*="field field-name-field-features field-type-text field-label-inline clearfix"]')->find('div[class*="field-items"]')->find('div')->text();
        $brand=get_brand('adidas');

        $photoCount=1;
        foreach($html->find('div[class*="commerce-product-field commerce-product-field-field-product-photo field-field-product-photo node-7917-product-field-product-photo"]')->find('div[class*="field field-name-field-product-photo field-type-image field-label-hidden"]')->find('div[class*="field-items"]')->find('div[class*="field-item even"]')->find('div')->find('div:eq(0)')->find('div') as $photo){
            $buf=pq($photo)->find('img')->attr('src');
            $photoArray[$photoCount]=substr($buf,0,strpos($buf,'?'));
            $photoCount++;
        }
        $name=pq($html)->find('#product-description > div.field.field-name-body.field-type-text-with-summary.field-label-hidden > div > div > h2')->text();

        $short_describtion=pq($html)->find('#product-description > div.field.field-name-body.field-type-text-with-summary.field-label-hidden > div > div > h4')->text();

        $gender=get_gender($type_gender_brand);
        if ($gender->name=='Прочее'){
            $buf=R::dispense('keywords');
            $buf->gender=$type_gender_brand;
            R::store($buf);
        }
        $gender_id=$gender->id;
        $gender_name=$gender->name;

        $sub_type=get_type_excel($name);        
        if ($sub_type->name=='Прочее'){
            $sub_type=get_type_excel($short_describtion);
                if ($sub_type->name=='Прочее'){
                $buf=R::dispense('keywords');
                $buf->sub_type=$name.'|'.$short_describtion;
                R::store($buf);
            }
        }        
        $sub_type_id=$sub_type->id;
        $sub_type_name=$sub_type->name;

        $type=R::findOne('categories','id = ?',array($sub_type->parent_id));
        $type_name=$type->name;
        $type_id=$type->id;

        $collection=get_collection($collection); 
        if ($collection->name=='Другие коллекции'){
            $buf=R::dispense('keywords');
            $buf->collection=$collection;
            R::store($buf);
        }
        $collection_id=$collection->id;
        $collection_name=$collection->name;

        $sport=get_sport($short_describtion);
        $sport_name=$sport->name;
        $sport_id=$sport->id;

        $product=R::dispense('unique');
        $product->name=$name;
        $product->colour=$colour;
        $product->article=$article;
        $product->describtion=$short_describtion;
        $product->sport_name=$sport_name;
        $product->sport_id=$sport_id;
        $product->col_name=$collection_name;
        $product->col_id=$collection_id;
        $product->type_id=$type_id;
        $product->type_name=$type_name;
        $product->sub_type_id=$sub_type_id;
        $product->sub_type_name=$sub_type_name;
        $product->gender_name=$gender_name;
        $product->gender_id=$gender_id;
        $product->brand=$brand->name;
        $product->brand_id=$brand->id;
        $product->code='200';
        $product->url=$url;
        R::store($product);
        for($i=1;$i<$photoCount;$i++){
            $query="UPDATE `unique` SET `unique`.`photo_".$i."`='".$photoArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";
            R::exec($query);
        }
        phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
        return true;
    }
    //reebok.in.ua
    function data_parser_reebok_in_ua($article){
        $url = 'https://reebok.in.ua/search.html?search='.$article;
        $file = file_get_contents($url);//скачиваем страницу по url

        $html_code = htmlspecialchars($file);//для вывода html

        $pos = strpos($html_code, 'Нет товаров, соответствующих критериям поиска.');
        if ($pos != false) 
            return false;
        
        $html = phpQuery::newDocument($file);
        $ref=pq($html)->find('div[class*="product-about"]')->find('div[class*="name"]')->find('a:eq(0)')->attr('href');

        $file = file_get_contents($ref);
        $html = phpQuery::newDocument($file);

        $check_article=pq($html)->find('#content > div > div > div.col-sm-6.product-right > div.description > span:nth-child(2)')->text();
        if(strnatcasecmp(trim($check_article),$article)!=0){
            return false;
        }
        $brand=get_brand('reebok');
        $photoCount=1;
        foreach($html->find('div[class*="image-additional"]')->find('div:eq(0)')->find('div') as $photo){
            $buf=pq($photo)->find('a')->attr('href');
            $photoArray[$photoCount]=substr($buf,0,strpos($buf,'?'));
            $photoCount++;
        }
        $name=pq($html)->find('div[id*="tab-description"]')->find('span')->find('h1')->text();
        $describtion=pq($html)->find('#tab-description > span > div:nth-child(2)')->text();
        $advantagesCount=1;
        foreach ($html->find('#tab-description > span > div:nth-child(3) > ul')->find('li') as $advantage) {
            $advantagesArray[$advantagesCount]=pq($advantage)->text();
            $advantagesCount++;
        }

        $gender=get_gender($name);
        if ($gender->name=='Прочее'){
            $buf=R::dispense('keywords');
            $buf->gender=$name;
            R::store($buf);
        }
        $gender_id=$gender->id;
        $gender_name=$gender->name;

        $sub_type=get_type_excel($name);        
        if ($sub_type->name=='Прочее'){
            $buf=R::dispense('keywords');
            $buf->sub_type=$name;
            R::store($buf);
        }        
        $sub_type_id=$sub_type->id;
        $sub_type_name=$sub_type->name;

        $type=R::findOne('categories','id = ?',array($sub_type->parent_id));
        $type_name=$type->name;
        $type_id=$type->id;

        $collection=get_collection($name); 
        if ($collection->name=='Другие коллекции'){
            $buf=R::dispense('keywords');
            $buf->collection=$name;
            R::store($buf);
        }
        $collection_id=$collection->id;
        $collection_name=$collection->name;

        $sport=get_sport($name);
        $sport_name=$sport->name;
        $sport_id=$sport->id;

        $product=R::dispense('unique');
        $product->name=$name;
        $product->colour=$colour;
        $product->article=$article;
        $product->describtion=$short_describtion;
        $product->sport_name=$sport_name;
        $product->sport_id=$sport_id;
        $product->col_name=$collection_name;
        $product->col_id=$collection_id;
        $product->type_id=$type_id;
        $product->type_name=$type_name;
        $product->sub_type_id=$sub_type_id;
        $product->sub_type_name=$sub_type_name;
        $product->gender_name=$gender_name;
        $product->gender_id=$gender_id;
        $product->brand=$brand->name;
        $product->brand_id=$brand->id;
        $product->code='200';
        $product->url=$url;
        R::store($product);
        for($i=1;$i<$photoCount;$i++){
            $query="UPDATE `unique` SET `unique`.`photo_".$i."`='".$photoArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";
            R::exec($query);
        }
        phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
        return true;
    }
    //reebok-shop.in.ua
    function data_parser_reebok_shop_in_ua($article){
        $url = 'http://reebok-shop.com.ua/kat/поиск?keyword='.$article;
        $file = file_get_contents($url);//скачиваем страницу по url

        $html_code = htmlspecialchars($file);//для вывода html

        $pos = strpos($html_code, 'Нет результатов :');
        if ($pos != false) 
            return false;
        
        $html = phpQuery::newDocument($file);
        $ref='http://reebok-shop.com.ua'.pq($html)->find('#t3-content > div.category-view > div > div.row > div > div > div.vm-product-descr-container > div.product-name > h2 > a')->attr('href');
        $file = file_get_contents($ref);
        $html = phpQuery::newDocument($file);

        $check_article=pq($html)->find('#t3-content > div.productdetails-view.product-container.productdetails.b1c-good > div.row > div.col-md-5 > div > div.product-info > div.scu')->text();
        if(strnatcasecmp(trim($check_article,'Артикул: '),$article)!=0){
            return false;
        }
        $name= pq($html)->find('#t3-content > div.productdetails-view.product-container.productdetails.b1c-good > h1')->text();
        $collection=pq($html)->find('#t3-content > div.productdetails-view.product-container.productdetails.b1c-good > div.row > div.col-md-5 > div > div.product-info > div.manufacturer > div')->text();
        $name_brand=pq($html)->find('#home > p:nth-child(1) > strong')->text();
        $describtion=pq($html)->find('#home > p:nth-child(2)')->html();
        $gender_pars=pq($html)->find('body > div.t3-wrapper > div.breadcrumbs.small > div > ol > li:nth-child(3) > a')->text();

        $photoCount=1;
        foreach ($html->find('a[data-fresco-group*="full-image"]') as $photo) {
            $photoArray[$photoCount]= 'http://reebok-shop.com.ua'.pq($photo)->attr('href');
            $photoCount++;
        }


        $brand=get_brand('reebok');

        $gender=get_gender($gender_pars);
        if ($gender->name=='Прочее'){
            $gender=get_gender($describtion);
            if ($gender->name=='Прочее'){
                $buf=R::dispense('keywords');
                $buf->gender=$gender_pars;
                R::store($buf);
            }
        }
        $gender_id=$gender->id;
        $gender_name=$gender->name;

        $sub_type=get_type_excel($describtion);   
        $sub_type_id=$sub_type->id;
        $sub_type_name=$sub_type->name;

        $type=R::findOne('categories','id = ?',array($sub_type->parent_id));
        $type_name=$type->name;
        $type_id=$type->id;

        $collection=get_collection($collection); 
        if ($collection->name=='Другие коллекции'){
            $buf=R::dispense('keywords');
            $buf->collection=$collection;
            R::store($buf);
        }
        $collection_id=$collection->id;
        $collection_name=$collection->name;

        $sport=get_sport($gender_pars);
        if ($sport->name=='Другие виды спорта'){
            $buf=R::dispense('keywords');
            $buf->sport=$gender_pars;
            R::store($buf);
        }
        $sport_name=$sport->name;
        $sport_id=$sport->id;

        $product=R::dispense('unique');
        $product->name=$name;
        $product->article=$article;
        $product->describtion=$describtion;
        $product->sport_name=$sport_name;
        $product->sport_id=$sport_id;
        $product->col_name=$collection_name;
        $product->col_id=$collection_id;
        $product->type_id=$type_id;
        $product->type_name=$type_name;
        $product->sub_type_id=$sub_type_id;
        $product->sub_type_name=$sub_type_name;
        $product->gender_name=$gender_name;
        $product->gender_id=$gender_id;
        $product->brand=$brand->name;
        $product->brand_id=$brand->id;
        $product->code='200';
        $product->url=$url;
        R::store($product);
        for($i=1;$i<$photoCount;$i++){
            $query="UPDATE `unique` SET `unique`.`photo_".$i."`='".$photoArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";
            R::exec($query);
        }
        phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
        return true;
    }
    //adidas.net.ua
    function data_parser_adidas_net_ua($article){
        $url = 'https://www.adidas.net.ua/search.html?search='.$article;
        $file = file_get_contents($url);//скачиваем страницу по url

        $html_code = htmlspecialchars($file);//для вывода html

        $pos = strpos($html_code, 'Нет товаров, соответствующих критериям поиска.');
        if ($pos != false) 
            return false;
        
        $html = phpQuery::newDocument($file);
        $ref=pq($html)->find('div[class*="container"]')->find('div[id*="container"]')->find('div[class*="content"]')->find('div[id*="brainyfilter-product-container]')->find('div[class*="product"]')->find('div[class*="image"]')->find('a:eq(0)')->attr('href');
        $file = file_get_contents($ref);
        $html = phpQuery::newDocument($file);

        $gender=pq($html)->find('div[id*="container"]')->find('div[class*="row"]')->find('ul:eq(0)')->find('li:eq(2)')->find('a:eq(0)')->find('span:eq(0)')->text();
        $col_and_brand=pq($html)->find('div[class*="container"]')->find('div[id*="container"]')->find('div[class*="content"]')->find('div[class*="product-info"]')->find('div:eq(0)')->find('a:eq(0)')->find('span:eq(0)')->text();
        
        $brand=get_brand($col_and_brand);
        $name=pq($html)->find('div[class*="container"]')->find('div[id*="container"]')->find('div[class*="content"]')->find('div[class*="product-info"]')->find('div:eq(0)')->find('h1')->text();

        $check_article=pq($html)->find('div[class*="description"]')->find('span[class*="product-gray"]')->find('span')->text();
        if(strnatcasecmp(trim($check_article),$article)!=0)
            return false;

        $photoCount=0;
        foreach ($html->find('div[class*="image-additional"]')->find('div:eq(2)')->find('div') as $photo) {
            $photoCount++;
            $photoArray[$photoCount]=pq($photo)->find('img')->attr('src');
            $photoArray[$photoCount]=substr($photoArray[$photoCount],0,-11).'600x600.jpg';
        }
        $describtion=pq($html)->find('div[id*="tab-description"]')->find('div:eq(0)')->html();

        $advantagesCount=0;
        foreach ($html->find('div[id*="tab-specification"]')->find('ul')->find('li') as $advantage) {
            $advantagesCount++;
            $advantagesArray[$advantagesCount]=pq($advantage)->text();
        }
        $materials=pq($html)->find('div[id*="tab-specification"]')->find('ul')->find('li:eq(7)')->text();
        $sub_col_name=pq($html)->find('div[id*="tab-specification"]')->find('ul')->find('li:eq(6)')->text();//NMD/..
        $sub_type_name=pq($html)->find('div[id*="tab-specification"]')->find('ul')->find('li:eq(4)')->text();
        $type_name=pq($html)->find('div[id*="tab-specification"]')->find('ul')->find('li:eq(2)')->text();
        $colour=pq($html)->find('div[id*="tab-specification"]')->find('ul')->find('li:eq(8)')->text();
        $gender_name=pq($html)->find('div[id*="tab-specification"]')->find('ul')->find('li:eq(1)')->text();

        $gender=get_gender($gender);
        if ($gender->name=='Прочее'){
            $gender=get_gender($gender_name);
            if ($gender->name=='Прочее'){
                $buf=R::dispense('keywords');
                $buf->gender=$gender_name;
                R::store($buf);
            }
        }
        $gender_id=$gender->id;
        $gender_name=$gender->name;

        $sub_type=get_type_excel($describtion);        

        $sub_type_id=$sub_type->id;
        $sub_type_name=$sub_type->name;

        $type=R::findOne('categories','id = ?',array($sub_type->parent_id));
        $type_name=$type->name;
        $type_id=$type->id;

        $collection=get_collection($col_and_brand);       
        if ($collection->name=='Прочее'){
            $buf=R::dispense('keywords');
            $buf->collection=$collection;
            R::store($buf);
        }
        $collection_id=$collection->id;
        $collection_name=$collection->name;

        $sport=get_sport($col_and_brand);
        $sport_name=$sport->name;
        $sport_id=$sport->id;

        $product=R::dispense('unique');
        $product->name=$name;
        $product->materials=$materials;
        $product->article=$article;
        $product->colour=$colour;
        $product->describtion=$describtion;
        $product->sport_name=$sport_name;
        $product->sport_id=$sport_id;
        $product->col_name=$collection_name;
        $product->col_id=$collection_id;
        $product->type_id=$type_id;
        $product->type_name=$type_name;
        $product->sub_type_id=$sub_type_id;
        $product->sub_type_name=$sub_type_name;
        $product->gender_name=$gender_name;
        $product->gender_id=$gender_id;
        $product->brand=$brand->name;
        $product->brand_id=$brand->id;
        $product->code='200';
        $product->url=$url;
        R::store($product);
        for($i=1;$i<=$photoCount;$i++){
            $query="UPDATE `unique` SET `unique`.`photo_".$i."`='".$photoArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";
            R::exec($query);
        }
        for($i=1;$i<=$advantagesCount;$i++){
            $query="UPDATE `unique` SET `unique`.`advantages_".$i."`='".$advantagesArray[$i]."' WHERE `unique`.`article` = '".$article."' AND `unique`.`name` = '".$name."'";;
            R::exec($query);
        }
        phpQuery::unloadDocuments();//очистить оперативку от последствий парсинга
        return true;
    }
    function excel_to_db($start,$last,$excel_name,$excel_type){
        //------------------------------------
        //2 Часть: чтение файла
        //Файл лежит в директории веб-сервера!
        $objPHPExcel = PHPExcel_IOFactory::load($excel_name);
        //R::nuke();
        //createTable();
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            //Имя таблицы
            $Title              = $worksheet->getTitle();
            //Последняя используемая строка
            $lastRow         = $worksheet->getHighestRow();
            //Последний используемый столбец
            $lastColumn      = $worksheet->getHighestColumn();
            //Последний используемый индекс столбца
            $lastColumnIndex = PHPExcel_Cell::columnIndexFromString($lastColumn);
            if($start>$lastRow ){
                return 'false';
                exit();
            }
            elseif($last>$lastRow){
                $last=$lastRow;
            }
            for ($row = $start/*10*/; $row <= $last/*$lastRow*/; ++$row) {
                if($worksheet->getCellByColumnAndRow(1, $row)->getValue()!='' ) {
                    $article = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue());
                    $size=$worksheet->getCellByColumnAndRow(4, $row)->getValue();
                    $size_ru=$worksheet->getCellByColumnAndRow(5, $row)->getValue();
                    $name_excel=$worksheet->getCellByColumnAndRow(2, $row)->getValue();
                    $count= $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                    $price_buy= $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                    $price_buy= round (get_price($price_buy,1.13)/10)*10;
                    $price_sell=round (get_price($price_buy,0.93)/10)*10;
                    $product=R::findOne('base','article = ?',array($article));
                    if(isset($product)){
                        for($i=1;$i<=10;$i++){
                            $where='size_id_'.$i.' = ? AND article = ?';
                            $product_checker=R::findOne('base',$where,array('empty',$article));
                            if(isset($product_checker)){
                                $size_id=get_size($size,$size_ru,$product->gender_name,$product->type_id,$product->type_name,$product->brand);
                                $query="UPDATE `base` SET `size_id_".$i."`='".$size_id."' , `size_name_".$i."` = 'Size:".$size." Size_ru:".$size_ru."' WHERE `base`.`article` = '".$article."'";
                                R::exec($query);
                                break;
                            }
                        }
                        continue;
                    }

                    $parent_product=R::findOne('unique','article = ?',array($article));
                    if(!isset($parent_product)){
                        data_parser($article,0);
                        $parent_product=R::findOne('unique','article = ?',array($article));
                    }

                    if($parent_product->code=='404' || $parent_product->code=='500'){
                        $sub_type=get_type_excel($name_excel);
                        $sub_type_id=$sub_type->id;
                        $sub_type_name=$sub_type->name;
                        $type_id=$sub_type->parent_id;
                        $parent_product->type_id=$type_id;
                        $parent_product->sub_type_name=$sub_type_name;
                        $parent_product->sub_type_id=$sub_type_id;
                        $parent_product->name=$name_excel;
                        if($parent_product->code=='404')
                            $parent_product->code='100';
                    }
                    elseif($parent_product->code=='200'){
                        $parent_product->name_excel=$name_excel; 
                        $sub_type_id=$parent_product->sub_type_id;
                        $sub_type_name=$parent_product->sub_type_name;
                        $type_id=$parent_product->type_id;
                        $type_name=$parent_product->type_name;
                    }


                    $col_id=$parent_product->col_id;
                    $gender_id=$parent_product->gender_id;
                    $sport_id=$parent_product->sport_id;
                    $col_name=$parent_product->col_name;
                    $gender_name=$parent_product->gender_name;
                    $sport_name=$parent_product->sport_name;

                    $product=R::dispense('base');
                    $product->article = $article;
                    $product->col_id = $col_id;
                    $product->gender_id = $gender_id;
                    $product->sport_id = $sport_id;
                    $product->type_id=$type_id;
                    $product->sub_type_id=$sub_type_id;
                    $product->new = '';
                    $product->sale = '';
                    $product->popular = '';
                    $product->col_name = $col_name;
                    $product->gender_name = $gender_name;
                    $product->sport_name = $sport_name;
                    $product->type_name=$type_name;
                    $product->sub_type_name=$sub_type_name;
                    $product->size=$size;
                    $product->size_ru=$size_ru;
                    $product->name = $parent_product->name;
                    $product->model = $parent_product->model;
                    $product->brand= $parent_product->brand;
                    $product->brand_id= $parent_product->brand_id;
                    $product->colour=$parent_product->colour;
                    $product->describtion=$parent_product->describtion;
                    $product->type_from_site = $parent_product->type_from_site;
                    $product->name_excel = $name_excel;
                    $product->price_buy = $price_buy;
                    $product->price_sell= $price_sell;
                    $product->count=$count;
                    $product->photo_1=$parent_product->photo_1;
                    $product->photo_2=$parent_product->photo_2;
                    $product->photo_3=$parent_product->photo_3;
                    $product->photo_4=$parent_product->photo_4;
                    $product->photo_5=$parent_product->photo_5;
                    $product->photo_6=$parent_product->photo_6;
                    $product->photo_7=$parent_product->photo_7;
                    $product->photo_8=$parent_product->photo_8;
                    $product->photo_9=$parent_product->photo_9;
                    $product->phort_10=$parent_product->photo_10;
                    $product->other_colour_1=$parent_product->other_colour_1;
                    $product->other_colour_2=$parent_product->other_colour_2;
                    $product->other_colour_3=$parent_product->other_colour_3;
                    $product->other_colour_4=$parent_product->other_colour_4;
                    $product->other_colour_5=$parent_product->other_colour_5;
                    $product->other_colour_6=$parent_product->other_colour_6;
                    $product->other_colour_7=$parent_product->other_colour_7;
                    $product->other_colour_8=$parent_product->other_colour_8;
                    $product->other_colour_9=$parent_product->other_colour_9;
                    $product->other_colour_10=$parent_product->other_colour_10;
                    $product->advantages_1=$parent_product->advantages_1;
                    $product->advantages_2=$parent_product->advantages_2;
                    $product->advantages_3=$parent_product->advantages_3;
                    $product->advantages_4=$parent_product->advantages_4;
                    $product->advantages_5=$parent_product->advantages_5;
                    $product->advantages_6=$parent_product->advantages_6;
                    $product->advantages_7=$parent_product->advantages_7;
                    $product->advantages_8=$parent_product->advantages_8;
                    $product->advantages_9=$parent_product->advantages_9;
                    $product->advantages_10=$parent_product->advantages_10;
                    $product->size_id_1=get_size($size,$size_ru,$parent_product->gender_name,$parent_product->type_id,$parent_product->type_name,$parent_product->brand);
                    $product->size_id_2='empty';
                    $product->size_id_3='empty';
                    $product->size_id_4='empty';
                    $product->size_id_5='empty';
                    $product->size_id_6='empty';
                    $product->size_id_7='empty';
                    $product->size_id_8='empty';
                    $product->size_id_9='empty';
                    $product->size_id_10='empty';
                    $product->size_name_1='Size:'.$size.' Size_ru:'.$size_ru;
                    $product->size_name_2='empty';
                    $product->size_name_3='empty';
                    $product->size_name_4='empty';
                    $product->size_name_5='empty';
                    $product->size_name_6='empty';
                    $product->size_name_7='empty';
                    $product->size_name_8='empty';
                    $product->size_name_9='empty';
                    $product->size_name_10='empty';
                    R::store($parent_product);
                    R::store($product);
                }
            }
        }
    }
    function excel_convertor($start,$last,$excel_name,$excel_type){//из двух строк в кол-во размеров
        //------------------------------------
        //2 Часть: чтение файла
        //Файл лежит в директории веб-сервера!
        $objPHPExcel = PHPExcel_IOFactory::load($excel_name.'.xls');
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            //Имя таблицы
            $Title              = $worksheet->getTitle();
            //Последняя используемая строка
            $lastRow         = $worksheet->getHighestRow();
            //Последний используемый столбец
            $lastColumn      = $worksheet->getHighestColumn();
            //Последний используемый индекс столбца
            $lastColumnIndex = PHPExcel_Cell::columnIndexFromString($lastColumn);
            if($start>$lastRow ){
                return 'false';
                exit();
            }
            elseif($last>$lastRow){
                $last=$lastRow;
            }
            $row=$start;
            while ($row < $last) {
                $article=  trim($worksheet->getCellByColumnAndRow(1, $row)->getValue()); 
                $name=    $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                $colour=  $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $price=   $worksheet->getCellByColumnAndRow(5, $row)->getValue();
                $sizes=explode(' ',$worksheet->getCellByColumnAndRow(2, $row+1)->getValue());

                $product=R::findOne('base','article = ?',array($article));
                if(isset($product)){
                    $i=1;
                    while($i<=20){
                        $where='size_id_'.$i.' = ? AND article = ?';
                        $product_checker=R::findOne('base',$where,array('empty',$article));
                        if(isset($product_checker))
                            break;                        
                        $i++;
                    }
                    foreach ($sizes as $size) {
                        $size_one = explode('/',$size);
                        $size=$size_one[0];
                        $size_ru=$size_one[1];
                        if($product_checker->size_names!=''){
                            $sizesArray=json_decode($product_checker->size_names);
                        }
                        $sizesArray[] ="Size:".$size." Size_ru:".$size_ru;
                        $size_id=get_size($size,$size_ru,$product_checker->gender_name,$product_checker->type_id,$product_checker->type_name,$product_checker->brand);
                        $flag=true;
                        $j=1;
                        while($j<=20){
                            $where='size_id_'.$j.' = ? AND article = ?';
                            $product_after_check=R::findOne('base',$where,array($size_id,$article));
                            if(isset($product_after_check))
                                $flag=false;       
                            $j++;
                        }
                        if($flag){
                            $query="UPDATE `base` SET `size_id_".$i."`='".$size_id."' , `size_names` = '".json_encode($sizesArray)."' WHERE `base`.`article` = '".$article."'";
                            R::exec($query);
                            $i++;
                        }
                    }
                    continue;
                }

                $parent_product=R::findOne('unique','article = ?',array($article));
                if(!isset($parent_product)){
                    data_parser($article,0);
                    $parent_product=R::findOne('unique','article = ?',array($article));
                }

                if($parent_product->code=='404' || $parent_product->code=='500'){
                    $sub_type=get_type_excel($name_excel);
                    $sub_type_id=$sub_type->id;
                    $sub_type_name=$sub_type->name;
                    $type_id=$sub_type->parent_id;
                    $parent_product->type_id=$type_id;
                    $parent_product->sub_type_name=$sub_type_name;
                    $parent_product->sub_type_id=$sub_type_id;
                    $parent_product->name=$name_excel;
                    if($parent_product->code=='404')
                        $parent_product->code='100';
                }
                elseif($parent_product->code=='200'){
                    $parent_product->name_excel=$name_excel; 
                    $sub_type_id=$parent_product->sub_type_id;
                    $sub_type_name=$parent_product->sub_type_name;
                    $type_id=$parent_product->type_id;
                    $type_name=$parent_product->type_name;
                }


                $col_id=$parent_product->col_id;
                $gender_id=$parent_product->gender_id;
                $sport_id=$parent_product->sport_id;
                $col_name=$parent_product->col_name;
                $gender_name=$parent_product->gender_name;
                $sport_name=$parent_product->sport_name;

                $product=R::dispense('base');
                $product->article = $article;
                $product->col_id = $col_id;
                $product->gender_id = $gender_id;
                $product->sport_id = $sport_id;
                $product->type_id=$type_id;
                $product->sub_type_id=$sub_type_id;
                $product->new = '';
                $product->sale = '';
                $product->popular = '';
                $product->col_name = $col_name;
                $product->gender_name = $gender_name;
                $product->sport_name = $sport_name;
                $product->type_name=$type_name;
                $product->sub_type_name=$sub_type_name;
                $product->size=$size;
                $product->size_ru=$size_ru;
                $product->name = $parent_product->name;
                $product->model = $parent_product->model;
                $product->brand= $parent_product->brand;
                $product->brand_id= $parent_product->brand_id;
                if(strlen(trim($colour))!=0)
                    $product->colour=$colour;
                else
                    $product->colour=$parent_product->colour;
                $product->describtion=$parent_product->describtion;
                $product->type_from_site = $parent_product->type_from_site;
                $product->name_excel = $name_excel;
                $product->price_buy = $price_buy;
                $product->price_sell= $price_sell;
                $product->count=$count;
                $product->photo=$parent_product->photo_1;
                $photoArray = array($parent_product->photo_1 => 1, $parent_product->photo_2 => 2, $parent_product->photo_3 => 3, $parent_product->photo_4 => 4, $parent_product->photo_5 => 5,$parent_product->photo_6 => 6, $parent_product->photo_7 => 7, $parent_product->photo_8 => 8, $parent_product->photo_9 => 9, $parent_product->photo_10 => 10);
                $product->photos=json_encode($photoArray);
                $other_colourArray = array($parent_product->other_colour_1 => 1, $parent_product->other_colour_2 => 2, $parent_product->other_colour_3 => 3, $parent_product->other_colour_4 => 4, $parent_product->other_colour_5 => 5,$parent_product->other_colour_6 => 6, $parent_product->other_colour_7 => 7, $parent_product->other_colour_8 => 8, $parent_product->other_colour_9 => 9, $parent_product->other_colour_10 => 10);
                $product->other_colours=json_encode($other_colourArray);
                $advantagesArray = array($parent_product->advantages_1 => 1, $parent_product->advantages_2 => 2, $parent_product->advantages_3 => 3, $parent_product->advantages_4 => 4, $parent_product->advantages_5 => 5,$parent_product->advantages_6 => 6, $parent_product->advantages_7 => 7, $parent_product->advantages_8 => 8, $parent_product->advantages_9 => 9, $parent_product->advantages_10 => 10);
                $product->advantages=json_encode($advantagesArray);
                $product->size_id_1='empty';
                $product->size_id_2='empty';
                $product->size_id_3='empty';
                $product->size_id_4='empty';
                $product->size_id_5='empty';
                $product->size_id_6='empty';
                $product->size_id_7='empty';
                $product->size_id_8='empty';
                $product->size_id_9='empty';
                $product->size_id_10='empty';
                $product->size_id_11='empty';
                $product->size_id_12='empty';
                $product->size_id_13='empty';
                $product->size_id_14='empty';
                $product->size_id_15='empty';
                $product->size_id_16='empty';
                $product->size_id_17='empty';
                $product->size_id_18='empty';
                $product->size_id_19='empty';
                $product->size_id_20='empty';
                $product->size_names='';
                R::store($parent_product);
                R::store($product);
                $i=1;
                while($i<=20){
                    $where='size_id_'.$i.' = ? AND article = ?';
                    $product_checker=R::findOne('base',$where,array('empty',$article));
                    if(isset($product_checker))
                        break;                        
                    $i++;
                }
                foreach ($sizes as $size) {
                    $size_one = explode('/',$size);
                    $size=$size_one[0];
                    $size_ru=$size_one[1];
                    if($product_checker->size_names!=''){
                        $sizesArray=json_decode($product_checker->size_names);
                    }
                    $sizesArray[] ="Size:".$size." Size_ru:".$size_ru;
                    $size_id=get_size($size,$size_ru,$product_checker->gender_name,$product_checker->type_id,$product_checker->type_name,$product_checker->brand);
                    $flag=true;
                    $j=1;
                    while($j<=20){
                        $where='size_id_'.$j.' = ? AND article = ?';
                        $product_after_check=R::findOne('base',$where,array($size_id,$article));
                        if(isset($product_after_check))
                            $flag=false;       
                        $j++;
                    }
                    if($flag){
                        $query="UPDATE `base` SET `size_id_".$i."`='".$size_id."' , `size_names` = '".json_encode($sizesArray)."' WHERE `base`.`article` = '".$article."'";
                        R::exec($query);
                        $i++;
                    }
                }
                $row=$row+2;
            }
        }
        //$objWriter->save($excel_name.'_for_parsing.xls'); 
    }
function get_price($price_buy,$coef){
    $pos=strpos($price_buy,',');
    if($pos === false){
        $price_sell=floatval($price_buy);
        $price= $price_sell*$coef;
    }
    else{
        $position=(-1)*(strlen($price_buy)-$pos);
        $price_sell=substr($price_buy,0,$position);
        $price_sell.=substr($price_buy,$pos+1);
        $price= $price_sell*$coef;
    }
    return $price;
}
function get_size($size,$size_ru,$gender_name,$type_id,$type_name,$brand){
        if($type_name=='Обувь'){
            if($brand=='Reebok'){
                $brand_filter[1]='_R:';
            }
            elseif($brand=='Adidas'){
                $brand_filter[1]='_A:';
            }
            else{//Неопределен. бренд
                $brand_filter[1]='_R:';
                $brand_filter[2]='_A:';
            }
            if($gender_name=='Мужчины'){
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для Мужчин'))->id;
                $first_size[1]='UK'.$brand_filter[1].$size;
                $first_size[2]='US'.$brand_filter[1].$size;
                $first_size[5]='UK'.$brand_filter[1].$size_ru;
                $first_size[6]='US'.$brand_filter[1].$size_ru;
                $second_size[1]='RU'.$brand_filter[1].$size;
                $second_size[2]='EUR'.$brand_filter[1].$size;
                $second_size[5]='RU'.$brand_filter[1].$size_ru;
                $second_size[6]='EUR'.$brand_filter[1].$size_ru;
                if(strlen($brand_filter[2])==3){
                    $first_size[3]='UK'.$brand_filter[2].$size;//UK adidas
                    $first_size[4]='UK'.$brand_filter[2].$size;//US reebok
                    $first_size[7]='UK'.$brand_filter[2].$size_ru;
                    $first_size[8]='US'.$brand_filter[2].$size_ru;
                    $second_size[3]='RU'.$brand_filter[2].$size;//Ru adidas
                    $second_size[4]='EUR'.$brand_filter[2].$size;//EUR adidas
                    $second_size[7]='RU'.$brand_filter[2].$size_ru;
                    $second_size[8]='EUR'.$brand_filter[2].$size_ru;
                }
            }
            elseif($gender_name=='Женщины'){
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для Женщин'))->id;
                $first_size[1]='UK'.$brand_filter[1].$size;
                $first_size[2]='US'.$brand_filter[1].$size;
                $first_size[5]='UK'.$brand_filter[1].$size_ru;
                $first_size[6]='US'.$brand_filter[1].$size_ru;
                $second_size[1]='RU'.$brand_filter[1].$size;
                $second_size[2]='EUR'.$brand_filter[1].$size;
                $second_size[5]='RU'.$brand_filter[1].$size_ru;
                $second_size[6]='EUR'.$brand_filter[1].$size_ru;
                if(strlen($brand_filter[2])==3){
                    $first_size[3]='UK'.$brand_filter[2].$size;//UK adidas
                    $first_size[4]='UK'.$brand_filter[2].$size;//US reebok
                    $first_size[7]='UK'.$brand_filter[2].$size_ru;
                    $first_size[8]='US'.$brand_filter[2].$size_ru;
                    $second_size[3]='RU'.$brand_filter[2].$size;//Ru adidas
                    $second_size[4]='EUR'.$brand_filter[2].$size;//EUR adidas
                    $second_size[7]='RU'.$brand_filter[2].$size_ru;
                    $second_size[8]='EUR'.$brand_filter[2].$size_ru;
                }
            }
            elseif($gender_name=='Унисекс'){
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для Мужчин'))->id;
                $parent_id[2]=R::findOne('categories','name = ?',array('Размер обуви для Женщин'))->id;
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для детей и подростков'))->id;
                $parent_id[2]=R::findOne('categories','name = ?',array('Размер обуви для малышей'))->id;
                $first_size[1]='UK'.$brand_filter[1].$size;
                $first_size[2]='US'.$brand_filter[1].$size;
                $first_size[5]='UK'.$brand_filter[1].$size_ru;
                $first_size[6]='US'.$brand_filter[1].$size_ru;
                $second_size[1]='RU'.$brand_filter[1].$size;
                $second_size[2]='EUR'.$brand_filter[1].$size;
                $second_size[5]='RU'.$brand_filter[1].$size_ru;
                $second_size[6]='EUR'.$brand_filter[1].$size_ru;
                if(strlen($brand_filter[2])==3){
                    $first_size[3]='UK'.$brand_filter[2].$size;//UK adidas
                    $first_size[4]='UK'.$brand_filter[2].$size;//US reebok
                    $first_size[7]='UK'.$brand_filter[2].$size_ru;
                    $first_size[8]='US'.$brand_filter[2].$size_ru;
                    $second_size[3]='RU'.$brand_filter[2].$size;//Ru adidas
                    $second_size[4]='EUR'.$brand_filter[2].$size;//EUR adidas
                    $second_size[7]='RU'.$brand_filter[2].$size_ru;
                    $second_size[8]='EUR'.$brand_filter[2].$size_ru;
                }
            }
            elseif($gender_name=='Дети'){
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для детей и подростков'))->id;
                $parent_id[2]=R::findOne('categories','name = ?',array('Размер обуви для малышей'))->id;
                $first_size[1]='UK'.$brand_filter[1].$size;
                $first_size[2]='US'.$brand_filter[1].$size;
                $first_size[5]='UK'.$brand_filter[1].$size_ru;
                $first_size[6]='US'.$brand_filter[1].$size_ru;
                $second_size[1]='RU'.$brand_filter[1].$size;
                $second_size[2]='EUR'.$brand_filter[1].$size;
                $second_size[5]='RU'.$brand_filter[1].$size_ru;
                $second_size[6]='EUR'.$brand_filter[1].$size_ru;
                if(strlen($brand_filter[2])==3){
                    $first_size[3]='UK'.$brand_filter[2].$size;//UK adidas
                    $first_size[4]='UK'.$brand_filter[2].$size;//US reebok
                    $first_size[7]='UK'.$brand_filter[2].$size_ru;
                    $first_size[8]='US'.$brand_filter[2].$size_ru;
                    $second_size[3]='RU'.$brand_filter[2].$size;//Ru adidas
                    $second_size[4]='EUR'.$brand_filter[2].$size;//EUR adidas
                    $second_size[7]='RU'.$brand_filter[2].$size_ru;
                    $second_size[8]='EUR'.$brand_filter[2].$size_ru;
                }
            }
            elseif($gender_name=='Малыши'){
                $parent_id=R::findOne('categories','name = ?',array('Размер обуви для малышей'))->id;
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для детей и подростков'))->id;
                $first_size[1]='UK'.$brand_filter[1].$size;
                $first_size[2]='US'.$brand_filter[1].$size;
                $first_size[5]='UK'.$brand_filter[1].$size_ru;
                $first_size[6]='US'.$brand_filter[1].$size_ru;
                $second_size[1]='RU'.$brand_filter[1].$size;
                $second_size[2]='EUR'.$brand_filter[1].$size;
                $second_size[5]='RU'.$brand_filter[1].$size_ru;
                $second_size[6]='EUR'.$brand_filter[1].$size_ru;
                if(strlen($brand_filter[2])==3){
                    $first_size[3]='UK'.$brand_filter[2].$size;//UK adidas
                    $first_size[4]='UK'.$brand_filter[2].$size;//US reebok
                    $first_size[7]='UK'.$brand_filter[2].$size_ru;
                    $first_size[8]='US'.$brand_filter[2].$size_ru;
                    $second_size[3]='RU'.$brand_filter[2].$size;//Ru adidas
                    $second_size[4]='EUR'.$brand_filter[2].$size;//EUR adidas
                    $second_size[7]='RU'.$brand_filter[2].$size_ru;
                    $second_size[8]='EUR'.$brand_filter[2].$size_ru;
                }
            }
            elseif($gender_name=='Мальчики'){
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для детей и подростков'))->id;
                $parent_id[2]=R::findOne('categories','name = ?',array('Размер обуви для малышей'))->id;
                $first_size[1]='UK'.$brand_filter[1].$size;
                $first_size[2]='US'.$brand_filter[1].$size;
                $first_size[5]='UK'.$brand_filter[1].$size_ru;
                $first_size[6]='US'.$brand_filter[1].$size_ru;
                $second_size[1]='RU'.$brand_filter[1].$size;
                $second_size[2]='EUR'.$brand_filter[1].$size;
                $second_size[5]='RU'.$brand_filter[1].$size_ru;
                $second_size[6]='EUR'.$brand_filter[1].$size_ru;
                if(strlen($brand_filter[2])==3){
                    $first_size[3]='UK'.$brand_filter[2].$size;//UK adidas
                    $first_size[4]='UK'.$brand_filter[2].$size;//US reebok
                    $first_size[7]='UK'.$brand_filter[2].$size_ru;
                    $first_size[8]='US'.$brand_filter[2].$size_ru;
                    $second_size[3]='RU'.$brand_filter[2].$size;//Ru adidas
                    $second_size[4]='EUR'.$brand_filter[2].$size;//EUR adidas
                    $second_size[7]='RU'.$brand_filter[2].$size_ru;
                    $second_size[8]='EUR'.$brand_filter[2].$size_ru;
                }
            }
            elseif($gender_name=='Девочки'){
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для детей и подростков'))->id;
                $parent_id[2]=R::findOne('categories','name = ?',array('Размер обуви для малышей'))->id;
                $first_size[1]='UK'.$brand_filter[1].$size;
                $first_size[2]='US'.$brand_filter[1].$size;
                $first_size[5]='UK'.$brand_filter[1].$size_ru;
                $first_size[6]='US'.$brand_filter[1].$size_ru;
                $second_size[1]='RU'.$brand_filter[1].$size;
                $second_size[2]='EUR'.$brand_filter[1].$size;
                $second_size[5]='RU'.$brand_filter[1].$size_ru;
                $second_size[6]='EUR'.$brand_filter[1].$size_ru;
                if(strlen($brand_filter[2])==3){
                    $first_size[3]='UK'.$brand_filter[2].$size;//UK adidas
                    $first_size[4]='UK'.$brand_filter[2].$size;//US reebok
                    $first_size[7]='UK'.$brand_filter[2].$size_ru;
                    $first_size[8]='US'.$brand_filter[2].$size_ru;
                    $second_size[3]='RU'.$brand_filter[2].$size;//Ru adidas
                    $second_size[4]='EUR'.$brand_filter[2].$size;//EUR adidas
                    $second_size[7]='RU'.$brand_filter[2].$size_ru;
                    $second_size[8]='EUR'.$brand_filter[2].$size_ru;
                }
            }
            else{//Прочее
                $parent_id[1]=R::findOne('categories','name = ?',array('Размер обуви для Мужчин'))->id;
                $parent_id[2]=R::findOne('categories','name = ?',array('Размер обуви для детей и подростков'))->id;
                $parent_id[3]=R::findOne('categories','name = ?',array('Размер обуви для малышей'))->id;
                $first_size[1]='UK'.$brand_filter[1].$size;
                $first_size[2]='US'.$brand_filter[1].$size;
                $first_size[5]='UK'.$brand_filter[1].$size_ru;
                $first_size[6]='US'.$brand_filter[1].$size_ru;
                $second_size[1]='RU'.$brand_filter[1].$size;
                $second_size[2]='EUR'.$brand_filter[1].$size;
                $second_size[5]='RU'.$brand_filter[1].$size_ru;
                $second_size[6]='EUR'.$brand_filter[1].$size_ru;
                if(strlen($brand_filter[2])==3){
                    $first_size[3]='UK'.$brand_filter[2].$size;//UK adidas
                    $first_size[4]='UK'.$brand_filter[2].$size;//US reebok
                    $first_size[7]='UK'.$brand_filter[2].$size_ru;
                    $first_size[8]='US'.$brand_filter[2].$size_ru;
                    $second_size[3]='RU'.$brand_filter[2].$size;//Ru adidas
                    $second_size[4]='EUR'.$brand_filter[2].$size;//EUR adidas
                    $second_size[7]='RU'.$brand_filter[2].$size_ru;
                    $second_size[8]='EUR'.$brand_filter[2].$size_ru;
                }
            }
            foreach ($parent_id as $parent) {
                foreach ($first_size as $first) {
                    foreach ($second_size as $second) {
                        for($i=1;$i<=12;$i++){
                            for($j=$i;$j<=12;$j++){
                                $finded_size=R::findOne('categories','keyword_'.$i.' = ? AND keyword_'.$j.' = ? AND parent_id = ?',array($first,$second,$parent));
                                if(isset($finded_size->name))
                                    return $finded_size->id;
                            }
                        }
                    }
                }
            }
            foreach ($parent_id as $parent) {
                foreach ($first_size as $first) {
                    foreach ($second_size as $second) {
                        for($i=1;$i<=12;$i++){
                            $finded_size=R::findOne('categories','keyword_'.$i.' = ? AND parent_id = ?',array($first,$parent));
                            if(isset($finded_size->name))
                                return $finded_size->id;
                            $finded_size=R::findOne('categories','keyword_'.$i.' = ? AND parent_id = ?',array($second,$parent));
                            if(isset($finded_size->name))
                                return $finded_size->id;
                        }
                    }
                }
            }
            if(strpos($size,'.5')!=false){
                $size=substr($size,0,strpos($size,'.5')).'-';
                $id=get_size($size,$size_ru,$gender_name,$type_id,$type_name,$brand);
                if(R::findOne('categories','id = ?',array($id))->name !='Неопределенный размер')
                    return $id;
            }
            if(strpos($size_ru,'.5')!=false){
                $size_ru=substr($size_ru,0,strpos($size_ru,'.5')).'-';
                $id=get_size($size,$size_ru,$gender_name,$type_id,$type_name,$brand);
                if(R::findOne('categories','id = ?',array($id))->name !='Неопределенный размер')
                    return $id;
            }
            return R::findOne('categories','id = ?',array($id))->id;

        }
        elseif($type_name=='Аксессуары'){
            return R::findOne('categories','name = ?',array('Неопределенный размер'))->id;
        }
        elseif($type_name!='Прочее'){//Одежда
            if($brand=='Reebok'){
                $brand_filter[1]='_R:';
            }
            elseif($brand=='Adidas'){
                $brand_filter[1]='_1_A:';
                $brand_filter[2]='_2_A:';
            }
            else{//Неопределен. бренд
                $brand_filter[1]='_1_A:';
                $brand_filter[2]='_2_A:';
                $brand_filter[3]='_R:';
            }
            $sizes_arr=explode($size_ru, '|');
            $sizes_second_arr=explode($size, '|');
            foreach ($sizes_arr as $size_ru) {
                foreach ($sizes_second_arr as $size) {
                    if($gender_name=='Мужчины'){
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Мужчин'))->id;
                        $first_size[1]='RU'.$brand_filter[1].$size_ru;
                        $second_size[1]='EUR:'.$size;
                        $first_size[4]='RU'.$brand_filter[1].$size;
                        $second_size[2]='EUR:'.$size_ru;
                        if(strlen($brand_filter[3])==3){
                            $first_size[3]='RU'.$brand_filter[3].$size_ru;
                            $first_size[6]='RU'.$brand_filter[3].$size;
                        }
                        if(strlen($brand_filter[2])==5){
                            $first_size[2]='RU'.$brand_filter[2].$size_ru;
                            $first_size[5]='RU'.$brand_filter[2].$size;
                        }
                    }
                    elseif($gender_name=='Женщины'){
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Женщин'))->id;
                        $first_size[1]='RU'.$brand_filter[1].$size_ru;
                        $second_size[1]='EUR:'.$size;
                        $first_size[4]='RU'.$brand_filter[1].$size;
                        $second_size[2]='EUR:'.$size_ru;
                        if(strlen($brand_filter[3])==3){
                            $first_size[3]='RU'.$brand_filter[3].$size_ru;
                            $first_size[6]='RU'.$brand_filter[3].$size;
                        }
                        if(strlen($brand_filter[2])==5){
                            $first_size[2]='RU'.$brand_filter[2].$size_ru;
                            $first_size[5]='RU'.$brand_filter[2].$size;
                            $first_size[7]='GER'.$brand_filter[1].$size;
                            $first_size[8]='GER'.$brand_filter[1].$size_ru;
                            $first_size[9]='GER'.$brand_filter[2].$size_ru;
                            $first_size[10]='GER'.$brand_filter[2].$size;
                        }
                    }
                    elseif($gender_name=='Унисекс'){
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Мужчин'))->id;
                        $parent_id[2]=R::findOne('categories','name = ?',array('Размер одежды для Женщин'))->id;
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Подростков'))->id;
                        $parent_id[2]=R::findOne('categories','name = ?',array('Размер одежды для Детей'))->id;
                        $parent_id[3]=R::findOne('categories','name = ?',array('Размер одежды для малышей'))->id;
                        $first_size[0]=$size_ru;
                        $second_size[0]=$size;
                        $first_size[1]='RU'.$brand_filter[1].$size_ru;
                        $second_size[1]='EUR:'.$size;
                        $first_size[4]='RU'.$brand_filter[1].$size;
                        $second_size[2]='EUR:'.$size_ru;
                        if(strlen($brand_filter[3])==3){
                            $first_size[3]='RU'.$brand_filter[3].$size_ru;
                            $first_size[6]='RU'.$brand_filter[3].$size;
                        }
                        if(strlen($brand_filter[2])==5){
                            $first_size[2]='RU'.$brand_filter[2].$size_ru;
                            $first_size[5]='RU'.$brand_filter[2].$size;
                            $first_size[7]='GER'.$brand_filter[1].$size;
                            $first_size[8]='GER'.$brand_filter[1].$size_ru;
                            $first_size[9]='GER'.$brand_filter[2].$size_ru;
                            $first_size[10]='GER'.$brand_filter[2].$size;
                        }
                    }
                    elseif($gender_name=='Дети'){
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Подростков'))->id;
                        $parent_id[2]=R::findOne('categories','name = ?',array('Размер одежды для Детей'))->id;
                        $parent_id[3]=R::findOne('categories','name = ?',array('Размер одежды для малышей'))->id;
                        $parent_id[4]=R::findOne('categories','name = ?',array('Размер одежды для Мужчин'))->id;
                        $parent_id[5]=R::findOne('categories','name = ?',array('Размер одежды для Женщин'))->id;
                        $first_size[0]=$size_ru;
                        $second_size[0]=$size;
                        $first_size[1]='RU'.$brand_filter[1].$size_ru;
                        $second_size[1]='EUR:'.$size;
                        $first_size[4]='RU'.$brand_filter[1].$size;
                        $second_size[2]='EUR:'.$size_ru;
                        if(strlen($brand_filter[3])==3){
                            $first_size[3]='RU'.$brand_filter[3].$size_ru;
                            $first_size[6]='RU'.$brand_filter[3].$size;
                        }
                        if(strlen($brand_filter[2])==5){
                            $first_size[2]='RU'.$brand_filter[2].$size_ru;
                            $first_size[5]='RU'.$brand_filter[2].$size;
                            $first_size[7]='GER'.$brand_filter[1].$size;
                            $first_size[8]='GER'.$brand_filter[1].$size_ru;
                            $first_size[9]='GER'.$brand_filter[2].$size_ru;
                            $first_size[10]='GER'.$brand_filter[2].$size;
                        }
                    }
                    elseif($gender_name=='Малыши'){
                        $parent_id=R::findOne('categories','name = ?',array('Размер одежды для малышей'))->id;
                        $parent_id[2]=R::findOne('categories','name = ?',array('Размер одежды для Детей'))->id;
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Подростков'))->id;
                        $parent_id[4]=R::findOne('categories','name = ?',array('Размер одежды для Мужчин'))->id;
                        $parent_id[5]=R::findOne('categories','name = ?',array('Размер одежды для Женщин'))->id;
                        $first_size[0]=$size_ru;
                        $second_size[0]=$size;
                        $first_size[1]='RU'.$brand_filter[1].$size_ru;
                        $second_size[1]='EUR:'.$size;
                        $first_size[4]='RU'.$brand_filter[1].$size;
                        $second_size[2]='EUR:'.$size_ru;
                        if(strlen($brand_filter[3])==3){
                            $first_size[3]='RU'.$brand_filter[3].$size_ru;
                            $first_size[6]='RU'.$brand_filter[3].$size;
                        }
                        if(strlen($brand_filter[2])==5){
                            $first_size[2]='RU'.$brand_filter[2].$size_ru;
                            $first_size[5]='RU'.$brand_filter[2].$size;
                            $first_size[7]='GER'.$brand_filter[1].$size;
                            $first_size[8]='GER'.$brand_filter[1].$size_ru;
                            $first_size[9]='GER'.$brand_filter[2].$size_ru;
                            $first_size[10]='GER'.$brand_filter[2].$size;
                        }
                    }
                    elseif($gender_name=='Мальчики'){
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Подростков'))->id;
                        $parent_id[2]=R::findOne('categories','name = ?',array('Размер одежды для Детей'))->id;
                        $parent_id[3]=R::findOne('categories','name = ?',array('Размер одежды для малышей'))->id;
                        $parent_id[4]=R::findOne('categories','name = ?',array('Размер одежды для Мужчин'))->id;
                        $parent_id[5]=R::findOne('categories','name = ?',array('Размер одежды для Женщин'))->id;
                        $first_size[0]=$size_ru;
                        $second_size[0]=$size;
                        $first_size[1]='RU'.$brand_filter[1].$size_ru;
                        $second_size[1]='EUR:'.$size;
                        $first_size[4]='RU'.$brand_filter[1].$size;
                        $second_size[2]='EUR:'.$size_ru;
                        if(strlen($brand_filter[3])==3){
                            $first_size[3]='RU'.$brand_filter[3].$size_ru;
                            $first_size[6]='RU'.$brand_filter[3].$size;
                        }
                        if(strlen($brand_filter[2])==5){
                            $first_size[2]='RU'.$brand_filter[2].$size_ru;
                            $first_size[5]='RU'.$brand_filter[2].$size;
                            $first_size[7]='GER'.$brand_filter[1].$size;
                            $first_size[8]='GER'.$brand_filter[1].$size_ru;
                            $first_size[9]='GER'.$brand_filter[2].$size_ru;
                            $first_size[10]='GER'.$brand_filter[2].$size;
                        }
                    }
                    elseif($gender_name=='Девочки'){
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Подростков'))->id;
                        $parent_id[2]=R::findOne('categories','name = ?',array('Размер одежды для Детей'))->id;
                        $parent_id[3]=R::findOne('categories','name = ?',array('Размер одежды для Малышей'))->id;
                        $parent_id[5]=R::findOne('categories','name = ?',array('Размер одежды для Женщин'))->id;
                        $parent_id[4]=R::findOne('categories','name = ?',array('Размер одежды для Мужчин'))->id;
                        $first_size[0]=$size_ru;
                        $second_size[0]=$size;
                        $first_size[1]='RU'.$brand_filter[1].$size_ru;
                        $second_size[1]='EUR:'.$size;
                        $first_size[4]='RU'.$brand_filter[1].$size;
                        $second_size[2]='EUR:'.$size_ru;
                        if(strlen($brand_filter[3])==3){
                            $first_size[3]='RU'.$brand_filter[3].$size_ru;
                            $first_size[6]='RU'.$brand_filter[3].$size;
                        }
                        if(strlen($brand_filter[2])==5){
                            $first_size[2]='RU'.$brand_filter[2].$size_ru;
                            $first_size[5]='RU'.$brand_filter[2].$size;
                            $first_size[7]='GER'.$brand_filter[1].$size;
                            $first_size[8]='GER'.$brand_filter[1].$size_ru;
                            $first_size[9]='GER'.$brand_filter[2].$size_ru;
                            $first_size[10]='GER'.$brand_filter[2].$size;
                        }
                    }
                    else{//Прочее
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Мужчин'))->id;
                        $parent_id[1]=R::findOne('categories','name = ?',array('Размер одежды для Подростков'))->id;
                        $parent_id[2]=R::findOne('categories','name = ?',array('Размер одежды для Детей'))->id;
                        $parent_id[3]=R::findOne('categories','name = ?',array('Размер одежды для малышей'))->id;
                        $parent_id[5]=R::findOne('categories','name = ?',array('Размер одежды для Женщин'))->id;
                        $first_size[0]=$size_ru;
                        $second_size[0]=$size;
                        $first_size[1]='RU'.$brand_filter[1].$size_ru;
                        $second_size[1]='EUR:'.$size;
                        $first_size[4]='RU'.$brand_filter[1].$size;
                        $second_size[2]='EUR:'.$size_ru;
                        if(strlen($brand_filter[3])==3){
                            $first_size[3]='RU'.$brand_filter[3].$size_ru;
                            $first_size[6]='RU'.$brand_filter[3].$size;
                        }
                        if(strlen($brand_filter[2])==5){
                            $first_size[2]='RU'.$brand_filter[2].$size_ru;
                            $first_size[5]='RU'.$brand_filter[2].$size;
                            $first_size[7]='GER'.$brand_filter[1].$size;
                            $first_size[8]='GER'.$brand_filter[1].$size_ru;
                            $first_size[9]='GER'.$brand_filter[2].$size_ru;
                            $first_size[10]='GER'.$brand_filter[2].$size;
                        }
                    }
                    foreach ($parent_id as $parent) {
                        foreach ($first_size as $first) {
                            foreach ($second_size as $second) {
                                for($i=1;$i<=12;$i++){
                                    for($j=$i;$j<=12;$j++){
                                        $finded_size=R::findOne('categories','keyword_'.$i.' = ? AND keyword_'.$j.' = ? AND parent_id = ?',array($first,$second,$parent));
                                        if(isset($finded_size->name))
                                            return $finded_size->id;
                                    }
                                }
                            }
                        }
                    }
                    foreach ($parent_id as $parent) {
                        foreach ($first_size as $first) {
                            foreach ($second_size as $second) {
                                for($i=1;$i<=12;$i++){
                                    $finded_size=R::findOne('categories','keyword_'.$i.' = ? AND parent_id = ?',array($first,$parent));
                                    if(isset($finded_size->name))
                                        return $finded_size->id;
                                    $finded_size=R::findOne('categories','keyword_'.$i.' = ? AND parent_id = ?',array($second,$parent));
                                    if(isset($finded_size->name))
                                        return $finded_size->id;
                                }
                            }
                        }
                    }
                }
            }
            return R::findOne('categories','name = ?',array('Неопределенный размер'))->id;
        }
        else{//Прочее
            return R::findOne('categories','name = ?',array('Неопределенный размер'))->id;
        }
    }
function get_size_old($size,$size_ru,$gender_name,$type_id,$type_name,$brand){

        if($type_id==125){//Обувь
            if($brand=='adidas'){
                $first='RU_A:'.$size_ru;
                $second='UK_A:'.$size;
                if($gender_name=='Мальчики' || $gender_name=='Девочки' || $gender_name=='Дети'){
                    $gender_name=='Дети';
                    $first='EUR_A:'.$size;//в некоторых случаях size=UK size_ru=RU
                    $second='RU_A:'.$size_ru;//RU нету в таблице
                }
            }
            elseif($brand=='reebok'){
                $first.='RU_R:'.$size_ru;
                $second='US_R:'.$size;
                if($gender_name=='Мальчики' || $gender_name=='Девочки' || $gender_name=='Дети'){
                    $gender_name=='Дети';
                }
            }

            $parent_size_id=R::findOne('categories','keyword_1 = ? AND parent_id = ? AND describtion = ?',array($gender_name,0,'size_shoes'))->id;

            for ($i=1;$i<=10;$i++) {
                $first_where='keyword_'.$i.' = ? AND parent_id = ?';
                $first_find=R::findOne('categories',$first_where,array($first,$parent_size_id));
                if(isset($first_find)){
                    for ($j=1;$j<=10;$j++) {
                        $where='id = ? AND keyword_'.$j.' = ? AND parent_id = ?';
                        $find=R::findOne('categories',$where,array($first_find->id,$second,$parent_size_id));
                        if(isset($find)){
                            return $find->id;
                        }
                    }
                    return $first_find->id;
                }
            }
            for ($i=1;$i<=10;$i++) {
                $first_where='keyword_'.$i.' = ? AND parent_id = ?';
                $first_find=R::findOne('categories',$first_where,array($second,$parent_size_id));
                if(isset($first_find)){
                    return $first_find->id;
                }
            }
        }//Конец обувь
        elseif($type_id<123 && $type_id>64){//Одежда
            if($gender_name=='Мальчики' || $gender_name=='Девочки'){
                $gender_name=='Подростки';
            }

            if($gender_name=='Подростки' || $gender_name=='Дети' || $gender_name=='Малыши'){
                $first='Height:'.$size;
                $second='Height:'.$size_ru;
            }
            else{
                $find_val = '|';
                $pos = strpos($size_ru, $find_val);
                if($pos!= false){//if (size_ru=='X|Y')
                    $first='RU_1_A:'.substr( $size_ru,0,-3);
                    $second='RU_2_A:'.substr( $size_ru,-2);
                }
                elseif($size==$size_ru){
                    $first='RU_1_A:'.$size;
                    $second='RU_2_A:'.$size;
                }
                elseif($size_ru==' ' && $brand=='reebok'){
                    $first='EUR_R:'.$size;
                    $second='EUR_R:'.$size;
                }
                elseif($brand=='adidas'){
                    $first='RU_1_A:'.$size_ru;
                    $second='RU_2_A:'.$size_ru;
                }
                elseif($brand=='reebok'){
                    $first='EUR_R:'.$size;
                    $second='EUR_R:'.$size;
                }
            }

            $parent_size_id=R::findOne('categories','keyword_1 = ? AND parent_id = ? AND describtion = ?',array($gender_name,0,'size_cloth'))->id;


            for ($i=1;$i<=10;$i++) {
                $first_where='keyword_'.$i.' = ? AND parent_id = ?';
                $first_find=R::findOne('categories',$first_where,array($first,$parent_size_id));
                if(isset($first_find)){
                    for ($j=1;$j<=10;$j++) {
                        $where='id = ? AND keyword_'.$j.' = ? AND parent_id = ?';
                        $find=R::findOne('categories',$where,array($first_find->id,$second,$parent_size_id));
                        if(isset($find)){
                            return $find->id;
                        }
                    }
                    return $first_find->id;
                }
            }
            for ($i=1;$i<=10;$i++) {
                $first_where='keyword_'.$i.' = ? AND parent_id = ?';
                $first_find=R::findOne('categories',$first_where,array($second,$parent_size_id));
                if(isset($first_find)){
                    return $first_find->id;
                }
            }
        }
        else{
            return 'Undefined';
        }

        for ($i=1;$i<=10;$i++) {
            $where='(keyword_'.$i.' = ? OR keyword_'.$i.' = ?) AND parent_id = ?';
            $find=R::findOne('categories',$where,array($size_ru,$size,$parent_size_id));
            if(isset($find)){
                return $find->id;
            }
        }
    }

    function update_photos(){
        for($i=1;$i<=R::count( 'base' );$i++){
            $product=R::findOne('base','id = ?',array($i));
            $product->photo = R::findOne('unique','article =?',array($article))->photo_1;
            R::store($product);
        }
    }
    


    function start(){
        if($_POST['start']==0){
           excel_to_db(10,10,$_POST['source'],'sale'); 
        }
        else{
            $starti=$_POST['start'];
            $end=$_POST['start']+1;
            $from = $starti*10+1;
            $to = $end*10;
            for($i=$starti;$i<$end;$i++){//58
                $start=$i*10+1;
                $last=($i+1)*10;
                $flag = excel_to_db($start,$last,$_POST['source']);
                if($flag=='false')
                {
                    echo 'end';
                }
                else{
                    echo 'Now done from '.$from.' to '.$to.' rows.';
                }
            }
        }
        exit();
    }
    function start_another(){
        if($_POST['start']==0){
           excel_convertor(9,10,$_POST['source'],'new');
        }
        else{
            $starti=$_POST['start'];
            $end=$_POST['start']+1;
            $from = $starti*10+1;
            $to = $end*10;
            for($i=$starti;$i<$end;$i++){//58
                $start=$i*10+1;
                $last=($i+1)*10;
                $flag = excel_convertor($start,$last,$_POST['source'],'new');
                if($flag=='false')
                {
                    echo 'end';
                }
                else{
                    echo 'Now done from '.$from.' to '.$to.' rows.';
                }
            }
        }
        exit();
    }
    
    start_another();
    //start();
?>