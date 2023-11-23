<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");
const InfoBlockId = 4;              //общая константа id инфоблока
$csvFilePath = "test.csv";
echo 'test2';
if (file_exists($csvFilePath) && is_readable($csvFilePath)){     //выполняю валидацию и получаю данные
    $csvData= readCsvData();
    updateDatabase($csvData);
    deleteExtraRecords($csvData);   //удаляю элементы, которых нет в csv
}return;

function readCsvData(){
    global $csvFilePath;
    $csvData= array();
    $handle= fopen($csvFilePath,"r");
    if($handle !== false){
        fgetcsv($handle,0,";");
        while(($data=fgetcsv($handle,0,";"))!==FALSE){
            unset($data[6]);
            $csvData[]=$data;  //собираю массив элементов(строк) из csv
        }
        fclose($handle);
    }
    return $csvData;
}

function updateDatabase($csvData){
    foreach ($csvData as $data){
        processData($data);  //разбиваю массив csv данных на строки и передаю в обработчик
    }
    BXClearCache(true);
}

function processData($data){
    //разбиваю строку на переменные, провожу валидацию
    list($id, $name, $previewText, $detailText, $property1, $property2)=$data;
    $name=!empty($name)? htmlspecialcharsbx($name):"unnamed";
    $previewText=!empty($previewText)? htmlspecialcharsbx($previewText):"empty";
    $detailText=!empty($detailText)? htmlspecialcharsbx($detailText):"empty";
    $property1=!empty($property1)? htmlspecialcharsbx($property1):"empty";
    $property2=!empty($property2)? htmlspecialcharsbx($property2):"empty";

    //поиск элемета по id
    $res = CIBlockElement::GetList(array("NAME" => "ASC"), array("IBLOCK_ID" => InfoBlockId,"XML_ID"=>$id), false, false, array("ID", "XML_ID", "NAME", "PREVIEW_TEXT", "DETAIL_TEXT", "PROPERTY_PROP_1", "PROPERTY_PROP_2"));
    $el = new CIBlockElement;
    if($IBlockElement=$res->GetNext()){
        //элемент существует, обновляю
        updateExistingElement($el,$IBlockElement,$id,$name,$previewText,$detailText,$property1,$property2);
    }else{
        //элемент не существует, создаю новый
        addNewElement($el,$id,$name,$previewText,$detailText,$property1,$property2);
    }
}
function updateExistingElement($el,$element,$id,$name,$previewText,$detailText,$property1,$property2){
    $arLoadProductArray = array(
        "XML_ID" => $id,
        "NAME" => $name,
        "PREVIEW_TEXT" => $previewText,
        "DETAIL_TEXT" => $detailText,
        "PROPERTY_VALUES" => array(
            "PROP_1" => $property1,
            "PROP_2" => $property2,
        ),
    );         //сравниваю элементы csv и бд
    if( $arLoadProductArray["XML_ID"]===$element["XML_ID"]
        && $arLoadProductArray["NAME"]===$element["NAME"]
        && $arLoadProductArray["PREVIEW_TEXT"]===$element["PREVIEW_TEXT"]
        && $arLoadProductArray["DETAIL_TEXT"]===$element["DETAIL_TEXT"]
        && $arLoadProductArray["PROPERTY_PROP_1"]===$element["PROPERTY_PROP_1"]
        && $arLoadProductArray["PROPERTY_PROP_2"]===$element["PROPERTY_PROP_2"]) {
        echo "csv не изменен, масивы данных одинаковы";
    }else{
        echo "изменен";
        $el->Update($element["ID"], $arLoadProductArray); // есть отличия, актуализирую базу данных
    }
}
function addNewElement($el,$id,$name,$previewText,$detailText,$property1,$property2){
    $arLoadProductArray = array(
        "IBLOCK_ID" => InfoBlockId,
        "XML_ID" => $id,
        "NAME" => $name,
        "PREVIEW_TEXT" => $previewText,
        "DETAIL_TEXT" => $detailText,
        "PROPERTY_VALUES" => array(
            "PROP_1" => $property1,
            "PROP_2" => $property2,
        ),
    );

    $el->Add($arLoadProductArray);
    echo "csv изменился, массивы данных разные";
}

function deleteExtraRecords($csvData)
{
    // Получить все id записей из базы данных
    $databaseIds = array();
    $res = CIBlockElement::GetList(array("NAME" => "ASC"), array("IBLOCK_ID" => InfoBlockId), false, false, array( "XML_ID"));
    while ($IBlockId = $res->GetNext()) {
        $databaseIds[] = $IBlockId["XML_ID"];

        // Получить все id записей из csv-файла
        $csvIds = array_map(function ($data) {
            return $data[0];
        }, $csvData);

        // Найти id записей, которых нет в csv-файле
        $idsToDelete = array_diff($databaseIds, $csvIds);
        if (!empty($idsToDelete)) {

            // Удалить записи
            foreach ($idsToDelete as $idToDelete) {
                $res = CIBlockElement::GetList(array("NAME" => "ASC"), array("IBLOCK_ID" => InfoBlockId, "XML_ID" => $idToDelete), false, false, array("ID", "IBLOCK_ID"));
                if ($IBlockTempElement = $res->GetNext()) {
                    CIBlockElement::Delete($IBlockTempElement["ID"]);
                }
            }
        }
    }
}