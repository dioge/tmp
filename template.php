<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

Фильтр: <a href="<?$APPLICATION->GetCurPage();?>.?F=Y&clear_cache=Y">ex2/simplecomp2/?F=Y</a>
<ul>
<?foreach($arResult["COMPANY_DATA"] as $data):?>
    <?
    $this->AddEditAction($data['ID'], $data['ADD_LINK'], CIBlock::GetArrayByID($data["IBLOCK_ID"], "ELEMENT_ADD"));
    $this->AddEditAction($data['ID'], $data['EDIT_LINK'], CIBlock::GetArrayByID($data["IBLOCK_ID"], "ELEMENT_EDIT"));
    $this->AddDeleteAction($data['ID'], $data['DELETE_LINK'], CIBlock::GetArrayByID($data["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => "Вы уверены в этом?"));
    ?>
    <div id="<?=$this->GetEditAreaId($data['ID']);?>">
        <li>
            <b><?=$data["NAME"]?></b>
            <ul>
            <?foreach($data["CLASSIFIER_ELEMENTS"] as $element):?>
                <li>
                <?=$element["NAME"];?> - 
                <?=$element["PROPERTY_PRICE_VALUE"];?> - 
                <?=$element["PROPERTY_MATERIAL_VALUE"];?> - 
                <?=$element["PROPERTY_ARTNUMBER_VALUE"];?> 
                <a href="<?=$element["DETAIL_PAGE_URL"];?>">(Детальный просмотр)</a>
                </li>
            <?endforeach;?>
            </ul>
        </li>
    </div>
<?endforeach;?>
</ul>
