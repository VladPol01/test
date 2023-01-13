<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('iblock')) {
    return;
}

$arIBlockType = CIBlockParameters::GetIBlockTypes();

$arInfoBlocks = array();
$arFilterInfoBlocks = array('ACTIVE' => 'Y');
// сортируем по озрастанию поля сортировка
$arOrderInfoBlocks = array('SORT' => 'ASC');
// если уже выбран тип инфоблока, выбираем инфоблоки только этого типа
$www = $arCurrentValues;
if (!empty($arCurrentValues['IBLOCK_TYPE'])) {
    $arFilterInfoBlocks['TYPE'] = $arCurrentValues['IBLOCK_TYPE'];
}
// метод выборки информационных блоков
$rsIBlock = CIBlock::GetList($arOrderInfoBlocks, $arFilterInfoBlocks);
// перебираем и выводим в адмику доступные информационные блоки
while ($obIBlock = $rsIBlock->Fetch()) {
    $arInfoBlocks[$obIBlock['ID']] = '[' . $obIBlock['ID'] . '] ' . $obIBlock['NAME'];
}
$arComponentParameters = array(
    // название кастомной группы не обязательный параметр
    "GROUPS" => array(
        "TEST" => array(
            "NAME" => 'Тест'
        ),
    ),
    // основной массив с параметрами
    'PARAMETERS' => array(
        // выбор типа инфоблока
        'IBLOCK_TYPE' => array(                  // ключ массива $arParams в component.php
            'PARENT' => 'BASE',                  // название группы
            'NAME' => 'Выберите тип инфоблока',  // название параметра
            'TYPE' => 'LIST',                    // тип элемента управления, в котором будет устанавливаться параметр
            'VALUES' => $arIBlockType,           // входные значения
            'REFRESH' => 'Y',                    // перегружать настройки или нет после выбора (N/Y)
            'DEFAULT' => 'news',                 // значение по умолчанию
            'MULTIPLE' => 'N',                   // одиночное/множественное значение (N/Y)
        ),
        // выбор самого инфоблока
        'IBLOCK_ID' => array(
            'PARENT' => 'BASE',
            'NAME' => 'Выберите родительский инфоблок',
            'TYPE' => 'LIST',
            'VALUES' => $arInfoBlocks,
            'REFRESH' => 'Y',
            "DEFAULT" => '',
            "ADDITIONAL_VALUES" => "Y",
        ),
		"FILTER_NAME" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_FILTER"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		),
		"NEWS_COUNT" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("T_IBLOCK_DESC_LIST_CONT"),
			"TYPE" => "STRING",
			"DEFAULT" => "10",
		),
        // настройки кэширования
        'CACHE_TIME' => array(
            'DEFAULT' => 3600
        ),
    ),
);
?>