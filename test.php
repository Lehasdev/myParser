<?php
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");
const InfoBlockId = 4;              //общая константа id инфоблока
$csvFilePath = "test.csv";

if (file_exists($csvFilePath) && is_readable($csvFilePath)){     //выполняю валидацию и получаю данные для сравнения
    $databaseData= getDatabaseData();
    $csvData= readCsvData();

    if(areDataDifferent($databaseData,$csvData)){                //функция сравнения для csv и бд, bool
        deleteExtraRecords($databaseData, $csvData);           //функция удаляет элемент если его нет в csv
        updateDatabase($csvData);                              //проверка на изменение csv и обновление/создание если true
        echo "csv изменился, массивы данных разные";

    } else { echo "csv не изменен, масивы данных одинаковы"; }      //return;
}return;
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
    $dbTempData = array(); //массив временных данных из бд для сравнения
    $csvTempData = array();//массив временных данных из csv для сравнения

    $databaseData = array_reverse($databaseData); // пробегаю по элементам и собираю удобный массив. данные из бд идут в другом порядке, переворачиваю.
    foreach (array_reverse($databaseData) as $item) {
        $dbTempData[] = [
            "id" => ($item["XML_ID"] !== "empty") ? $item["XML_ID"] : "",
            "name" => ($item["NAME"] !== "unnamed") ? $item["NAME"] : "",
            "preview_text" => ($item["PREVIEW_TEXT"] !== "empty") ? $item["PREVIEW_TEXT"] : "",
            "detail_text" => ($item["DETAIL_TEXT"] !== "empty") ? $item["DETAIL_TEXT"] : "",
            "property1" => ($item["PROPERTY_PROP_1_VALUE"] !== "empty") ? $item["PROPERTY_PROP_1_VALUE"] : "",
            "property2" => ($item["PROPERTY_PROP_2_VALUE"] !== "empty") ? $item["PROPERTY_PROP_2_VALUE"] : "",
        ];
    }

    for($i=0;count($csvData)>$i;$i++){

        $csvTempData[$i]["id"] = $csvData[$i][0];
        $csvTempData[$i]["name"] = $csvData[$i][1];
        $csvTempData[$i]["preview_text"] = $csvData[$i][2];
        $csvTempData[$i]["detail_text"] = $csvData[$i][3];
        $csvTempData[$i]["property1"] = $csvData[$i][4];
        $csvTempData[$i]["property2"] = $csvData[$i][5];
    }
    return $dbTempData !== $csvTempData;
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
function deleteExtraRecords($databaseData, $csvData)
{
    // Получить все id записей из базы данных
    $databaseIds = array_map(function ($data) {
        return $data['XML_ID'];
    }, $databaseData);

    // Получите все id записей из csv-файла
    $csvIds = array_map(function ($data) {
        return $data[0];
    }, $csvData);

    // Найти id записей, которых нет в csv-файле
    $idsToDelete = array_diff($databaseIds, $csvIds);
    if(!empty($idsToDelete)){

        // Удалить записи
        foreach ($idsToDelete as $idToDelete) {
            $res = CIBlockElement::GetList(array("NAME" => "ASC"), array("IBLOCK_ID" => InfoBlockId, "XML_ID" => $idToDelete), false, false, array("ID", "IBLOCK_ID"));
            if ($IBlockTempElement = $res->GetNext()) {
                CIBlockElement::Delete($IBlockTempElement["ID"]);
            }

        }
    }

}