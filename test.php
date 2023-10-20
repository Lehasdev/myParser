<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock"); //Подключение модуля инфоблока
$csvFile= "test.csv"; //Путь до обрабатываемого файла, в корневой папке
if (($file = fopen($csvFile, "r"))!== FALSE){ //Открываю csv файл на чтение
    fgetcsv($file, 1000, ";");//пропускаю заголовки
    while(($data = fgetcsv($file, 1000, ";"))!== FALSE){     //открываю цикл и кладу в data построчно
        list($id, $name, $previewText, $detailText, $prop1, $prop2) = $data; //раскладываю по переменным

        $arFilter = array( //собираю параметры поиска
            "IBLOCK_ID" => "3", //id моего инфоблока
            "ID" => $id); //id элемента
        $arSelect = array("ID", "IBLOCK_ID");// что хочу вернуть
        $res = CIBlockElement::GetList(array("NAME" => "ASC"), $arFilter, false, false,$arSelect);// получаю ресурс для дальнейше работы


        $el = new CIBlockElement; //создаю объект класса для работы с элементами инфоблока
        if ($ob = $res->GetNext()) {
            // проверяю существует ли элемент, если да - начинаю обновление
            $arFields = array(
                //собираю новые данные
                "NAME" => $name,
                "PREVIEW_TEXT" => $previewText,
                "DETAIL_TEXT" => $detailText,
                "PROPERTY_VALUES" => array(
                    "PROP_1" => $prop1,
                    "PROP_2" => $prop2,
                ),

            );
            $el->Update($ob["ID"], $arFields);
        } else {
            // Элемент не существует, создаю новый
            $arLoadProductArray = array(
            //новые id на автоинкременте
                "IBLOCK_ID"      => "3",
                "NAME"           => $name,
                "ACTIVE"         => "Y",
                "PREVIEW_TEXT"   => $previewText,
                "DETAIL_TEXT"    => $detailText,
                "PROPERTY_VALUES" => array(
                    "PROP_1" => $prop1,
                    "PROP_2" => $prop2,
                ),
            );
            $el->Add($arLoadProductArray);
        }
    }
    fclose($file);
}
BXClearCache(true); //очистка кэша
?>
