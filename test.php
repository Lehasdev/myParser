<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");
const InfoBlockId = 4;
$csvFilePath = "test.csv";

if (file_exists($csvFilePath) && is_readable($csvFilePath)){      //выполняю валидацию и получаю данные для сравнения
    $databaseData= getDatabaseData();
    $csvData= readCsvData();

    if(areDataDifferent($databaseData,$csvData)){
        updateDatabase($csvData);              //проверка на изменение csv и обновление/создание если true
    }
}
function getDatabaseData()
{
    $databaseData = array();
    $res = CIBlockElement::GetList(array("NAME" => "ASC"), array("IBLOCK_ID" => InfoBlockId), false, false, array("ID", "XML_ID", "NAME", "PREVIEW_TEXT", "DETAIL_TEXT", "PROPERTY_PROP_1", "PROPERTY_PROP_2"));
    while ($IBlockElement = $res->GetNext()) {
        $databaseData[] = $IBlockElement;   //собираю массив элементов(строк) из инфоблока
    }
    return $databaseData;
}
function readCsvData(){
    global $csvFilePath;
    $csvData= array();
    $handle= fopen($csvFilePath,"r");
    if($handle !== false){
        fgetcsv($handle,0,";");
        while(($data=fgetcsv($handle,0,";"))!==FALSE){
            $csvData[]=$data;  //собираю массив элементов(строк) из csv
        }
        fclose($handle);
    }
    return $csvData;
}
function areDataDifferent($databaseData,$csvData){
    return $databaseData !== $csvData;
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

    $res = CIBlockElement::GetList(array("NAME" => "ASC"), array("IBLOCK_ID" => InfoBlockId,"XML_ID"=>$id), false, false, array("ID","IBLOCK_ID"));
    $el = new CIBlockElement;
    if($IBlockElement=$res->GetNext()){
        //элемент существует, обновляю
        updateExistingElement($el,$IBlockElement["ID"],$id,$name,$previewText,$detailText,$property1,$property2);
    }else{
        //элемент не существует, создаю новый
        addNewElement($el,$id,$name,$previewText,$detailText,$property1,$property2);
    }
}
function updateExistingElement($el,$elementId,$id,$name,$previewText,$detailText,$property1,$property2){
    $arLoadProductArray = array(
        "XML_ID" => $id,
        "NAME" => $name,
        "PREVIEW_TEXT" => $previewText,
        "DETAIL_TEXT" => $detailText,
        "PROPERTY_VALUES" => array(
            "PROP_1" => $property1,
            "PROP_2" => $property2,
        ),
    );
    $el->Update($elementId, $arLoadProductArray);
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

}
