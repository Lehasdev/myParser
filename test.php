<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock"); //Подключение модуля инфоблока
$csvFile = "test.csv"; // Путь до обрабатываемого файла, в корневой папке
$filePath = "hash.txt"; // Путь к файлу, где будет храниться хэш
if (file_exists($csvFile) && is_readable($csvFile)) {//проверка подключаемых файлов
    $lastHash = file_get_contents($filePath);//подключаю файл с хранящимся хэш кодом с предыдущего запуска скрипта
    if ($lastHash !== false) {
        $hash = md5_file($csvFile);//хэширую csv файл

        if ($hash === $lastHash) {//проверка на изменение csv файла
            die(); // Файл не изменился, прерываем выполнение скрипта
        }
    }  //файл либо пустой, либо изменен, начинаю создание или обновление
    $file = fopen($csvFile, "r");

    if ($file !== false) {

        fgetcsv($file, 0, ";");//пропускаю заголовки
        while (($data = fgetcsv($file, 0, ";")) !== FALSE) {     //открываю цикл и кладу в data построчно
            list($id, $name, $previewText, $detailText, $prop1, $prop2) = $data; //раскладываю по переменным
            // пишу проверки пустых значений на входе
            if (empty($name)) {
                $name = "unnamed"; // Присвоение значения по умолчанию
            }
            if (empty($previewText)) {
                $previewText = "empty"; // Присвоение значения по умолчанию
            }
            if (empty($detailText)) {
                $detailText = "empty"; // Присвоение значения по умолчанию
            }
            if (empty($prop1)) {
                $prop1 = "empty"; // Присвоение значения по умолчанию
            }
            if (empty($prop2)) {
                $prop2 = "empty"; // Присвоение значения по умолчанию
            }

            $arFilter = array(     "IBLOCK_ID" => "4",      //id моего инфоблока
                                   "PROPERTY_MY_ID" => $id //id элемента, добавил свойство
                              );

            $arSelect = array("ID", "IBLOCK_ID");// что хочу вернуть
            $res = CIBlockElement::GetList(array("NAME" => "ASC"), $arFilter, false, false, $arSelect);// получаю ресурс для дальнейше работы


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
                        "MY_ID" => $id
                    ),

                );
                $el->Update($ob["ID"], $arFields);
            } else {
                // Элемент не существует, создаю новый
                $arLoadProductArray = array(

                    "IBLOCK_ID" => "4",
                    "NAME" => $name,
                    "ACTIVE" => "Y",
                    "PREVIEW_TEXT" => $previewText,
                    "DETAIL_TEXT" => $detailText,
                    "PROPERTY_VALUES" => array(
                        "PROP_1" => $prop1,
                        "PROP_2" => $prop2,
                        "MY_ID"=> $id,
                    ),
                );
                $el->Add($arLoadProductArray);
            }
        }
        fclose($file);

        if (isset($hash)) {
            file_put_contents($filePath, $hash); //обновляю хэш запись
        }
    }
    BXClearCache(true); //очистка кэша

}
?>
