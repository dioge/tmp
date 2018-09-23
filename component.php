<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(CModule::IncludeModule('iblock')){
    
    $aMenuItems = array('create' => array(), 'create_section' => array(), 'edit' => array(), 'edit_section' => array());
            $APPLICATION->AddPanelButtonMenu('create', array("SEPARATOR" => true, "SORT" => 99));

    
    if ($this->StartResultCache(false, $USER->GetGroups())){
       
       // Выборка данных из инфоблока продукции
       $arOrder = array("NAME"=>"ASC", "SORT"=>"ASC");
       //Подключаем дополнительный фильтр
       $additionalFilter = array();
       if($_GET["F"]){
           $additionalFilter = array(
               "LOGIC" => "OR",
               array(
                   "<=PROPERTY_PRICE" => 1700,
                   "=PROPERTY_MATERIAL" => "Дерево, ткань"
               ),
               array(
                   "<PROPERTY_PRICE" => 1500,
                   "=PROPERTY_MATERIAL" => "Металл, пластик"
               ),
           );            
           $this->AbortResultCache();
       }
       $arFilter = array(
           "IBLOCK_ID" => $arParams["CATALOG_IBLOCK_ID"],
           "CHECK_PERMISSIONS" => "Y",
           $additionalFilter,
       );
       $arSelect = array("ID","IBLOCK_ID", "IBLOCK_SECTION_ID", "CODE", "NAME", "PROPERTY_PRICE", "PROPERTY_MATERIAL", "PROPERTY_ARTNUMBER", "PROPERTY_".$arParams["PRODUCT_PROPERTY_CODE"]);
       
       $elementResult = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
       $arResult["CATALOG_DATA"] = array();
       while ($elementRow = $elementResult->GetNext()){
           //Формирование URL детального просмотра элементов
           $elementRow["DETAIL_PAGE_URL"] = str_replace(array("#SECTION_ID#", "#ID#"), array($elementRow["IBLOCK_SECTION_ID"], $elementRow["ID"]), $arParams["DETAIL_PAGE_URL"]);
           
           $arResult["CATALOG_DATA"][] = $elementRow;
       }
      
       // Выборка данных из инфоблока классификатора с добавлением в массив arResult данных из инфоблока продукции
       $count = 0;
       $arFilter = array("IBLOCK_ID" => $arParams["CLASSIFIER_IBLOCK_ID"], "CHECK_PERMISSIONS" => "Y");
       $arSelect = array("IBLOCK_ID", "ID", "NAME");
       
       $elementResult = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
       $arResult["COMPANY_DATA"] = array(); 
       while ($elementRow = $elementResult->GetNext()){
           $count++;
           
            //Получение ссылок действий для кнопок "Эрмитажа"
            $arButtons = CIBlock::GetPanelButtons(
               $elementRow["IBLOCK_ID"],
               $elementRow["ID"],
               0,
               array("SECTION_BUTTONS"=>false, "SESSID"=>false)
           );
           $elementRow["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
           $elementRow["ADD_LINK"] = $arButtons["edit"]["add_element"]["ACTION_URL"];
           $elementRow["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];
       
           //Добавление к массиву с данными о компании данных из каталога
           foreach($arResult["CATALOG_DATA"] as $element){
               if($elementRow["ID"] == $element["PROPERTY_COMPANY_VALUE"]){
                   $elementRow["CLASSIFIER_ELEMENTS"][] = $element;
               }
           }
           $arResult["COMPANY_DATA"][] = $elementRow; 
       }
       $arResult["COUNT"] = $count;

    //Добавление пункта в выпадающее меню компонента
    if ($APPLICATION->GetShowIncludeAreas()){

        $this->AddIncludeAreaIcons(
            Array( //массив кнопок toolbar'a
                Array(
                    "ID" => "IbInAdminPanel",
                    "TITLE" => "ИБ в админке",
                    "URL" => "/bitrix/admin/iblock_element_admin.php?IBLOCK_ID={$arParams["CLASSIFIER_IBLOCK_ID"]}&type=products",
                    "IN_PARAMS_MENU" => true
                )
            )
        );
    }

    // echo "<pre>"; print_r(111); echo "</pre>";

       $this->IncludeComponentTemplate();
   }

   $APPLICATION->SetTitle("Разделов: {$arResult["COUNT"]}");
}
?>
