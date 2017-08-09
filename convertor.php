<?php
    include("Classes/PHPExcel.php");
    require "libs/phpQuery.php";
    require "db.php";
    header('content-type: text/html;charset=utf-8');

    function convert_price($price_buy){
        $pos=strpos($price_buy,',');
        if($pos === false){
            $price_sell=floatval($price_buy);
            if($price_sell<990)
                $price= $price_sell+100;
            else
                $price=$price_sell+200;
        }
        else{
            $position=(-1)*(strlen($price_buy)-$pos);
            $price_sell=substr($price_buy,0,$position);
            $price_sell.=substr($price_buy,$pos+1);
            if($price_sell<1990)
                $price=$price_sell+200;
            elseif($price_sell<2990)
                $price=$price_sell+300;
            elseif($price_sell<3990)
                $price=$price_sell+400;
            elseif($price_sell<4990)
                $price=$price_sell+500;
        }
        return $price;
    }

    function convert($start,$excel_name)
    {
        //------------------------------------
        //2 Часть: чтение файла
        //Файл лежит в директории веб-сервера!
        $objPHPExcel = PHPExcel_IOFactory::load($excel_name);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //$objWriter = new PHPExcel_Writer_Excel($objPHPExcel);
        //R::nuke();
        //createTable();
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            //Имя таблицы
            $Title = $worksheet->getTitle();
            //Последняя используемая строка
            $lastRow = $worksheet->getHighestRow();
            //Последний используемый столбец
            $lastColumn = $worksheet->getHighestColumn();
            //Последний используемый индекс столбца
            $lastColumnIndex = PHPExcel_Cell::columnIndexFromString($lastColumn);
            echo '<table>';
            echo '<thead>';
            for($col=1;$col<=$lastColumnIndex;++$col){
                echo '<th>'.$col.'</th>';
            }
            echo '</thead>';
            echo '<tbody>';
            for ($row = $start/*10*/; $row <= $lastRow; $row=$row+2) {
                echo '<tr>';
                $col=0;
                while($col<=$lastColumnIndex) {
                    $col++;
                    if($col==7) {
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, convert_price($worksheet->getCellByColumnAndRow($col, $row)->getValue()));
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col+2, $row, $worksheet->getCellByColumnAndRow($col, $row)->getValue()*$worksheet->getCellByColumnAndRow($col+1, $row)->getValue());
                    }
                    echo '<td>'.$worksheet->getCellByColumnAndRow($col, $row)->getValue().'</td>';
                }
                echo '<tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
        $objWriter->save('refactored1-'.$excel_name);
    }
    convert(9,'31072017.xls');
?>