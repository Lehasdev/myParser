<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");

$csvFile = "test.csv";
if (file_exists($csvFile) && is_readable($csvFile)) {
    $dbData = getDatabaseData();
    $csvData = readCsvData();

    if (areDataDifferent($dbData, $csvData)) {
        updateDatabase($csvData);
    }
}


function getDatabaseData()
{
    $dbData = array();

    $res = CIBlockElement::GetList(array("NAME" => "ASC"), array("IBLOCK_ID" => 4), false, false, array("ID", "XML_ID", "NAME", "PREVIEW_TEXT", "DETAIL_TEXT", "PROPERTY_PROP_1", "PROPERTY_PROP_2"));

    while ($ob = $res->GetNext()) {
        $dbData[] = $ob;
    }

    return $dbData;
}

function readCsvData()
{
    global $csvFile;
    $csvData = array();
    $file = fopen($csvFile, "r");

    if ($file !== false) {
        fgetcsv($file, 0, ";");

        while (($data = fgetcsv($file, 0, ";")) !== FALSE) {
            $csvData[] = $data;
        }
        fclose($file);
    }

    return $csvData;
}

function areDataDifferent($dbData, $csvData)
{
    return $dbData !== $csvData;
}

function updateDatabase($csvData)
{
    foreach ($csvData as $data) {
        processData($data);
    }
    BXClearCache(true);
}

function processData($data)
{
    list($id, $name, $previewText, $detailText, $prop1, $prop2) = $data;

    $name = !empty($name) ? htmlspecialcharsbx($name) : "unnamed";
    $previewText = !empty($previewText) ? htmlspecialcharsbx($previewText) : "empty";
    $detailText = !empty($detailText) ? htmlspecialcharsbx($detailText) : "empty";
    $prop1 = !empty($prop1) ? htmlspecialcharsbx($prop1) : "empty";
    $prop2 = !empty($prop2) ? htmlspecialcharsbx($prop2) : "empty";

    $arFilter = array("IBLOCK_ID" => 4, "XML_ID" => $id);
    $arSelect = array("ID", "IBLOCK_ID");
    $res = CIBlockElement::GetList(array("NAME" => "ASC"), $arFilter, false, false, $arSelect);
    $el = new CIBlockElement;

    if ($ob = $res->GetNext()) {
        updateExistingElement($el, $ob["ID"], $id, $name, $previewText, $detailText, $prop1, $prop2);
    } else {
        addNewElement($el, $id, $name, $previewText, $detailText, $prop1, $prop2);
    }
}

function updateExistingElement($el, $elementId, $id, $name, $previewText, $detailText, $prop1, $prop2)
{
    $arFields = array(
        "XML_ID" => $id,
        "NAME" => $name,
        "PREVIEW_TEXT" => $previewText,
        "DETAIL_TEXT" => $detailText,
        "PROPERTY_VALUES" => array(
            "PROP_1" => $prop1,
            "PROP_2" => $prop2,
        ),
    );
    $el->Update($elementId, $arFields);
}

function addNewElement($el, $id, $name, $previewText, $detailText, $prop1, $prop2)
{
    $arLoadProductArray = array(
        "IBLOCK_ID" => 4,
        "XML_ID" => $id,
        "NAME" => $name,
        "ACTIVE" => "Y",
        "PREVIEW_TEXT" => $previewText,
        "DETAIL_TEXT" => $detailText,
        "PROPERTY_VALUES" => array(
            "PROP_1" => $prop1,
            "PROP_2" => $prop2,
        ),
    );
    $el->Add($arLoadProductArray);
}


?>
