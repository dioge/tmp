<?

    // 1. Регистрация события при деактивации элемента
    // 2. Регистрация события при изменении инфоблока "Услуги"
    AddEventHandler(
        "iblock",
        "OnBeforeIBlockElementUpdate",
        Array(
            "MyClass",
            "OnBeforeIBlockElementUpdateHandler"
        )
    );
    
    // Регистрация события при изменении макроса в почтовом сообщении
    AddEventHandler(
        "main",
        "OnBeforeEventAdd",
        array(
            "MyClass",
            "OnBeforeEventAddHandler"
        )
    );    
    
    // Регистрация события при изменении административного меню
    AddEventHandler(
        "main",
        "OnBuildGlobalMenu",
        array(
            "MyClass",
            "OnBuildGlobalMenuHandler"
        )
    );
    
    // Регистрация события при загрузке страницы
    AddEventHandler(
        "main",
        "OnEpilog",
        array(
            "MyClass",
            "OnEpilogHandler"
        )
    );

    class MyClass{

        function OnBeforeIBlockElementUpdateHandler(&$arFields){
            
            // 1. Регистрация события при деактивации элемента
            if($arFields["IBLOCK_ID"] == 2 && $arFields["ACTIVE"] !== "Y"){
                $result = CIBlockElement::GetByID($arFields["ID"]) -> Fetch();
                
                if($result){
                    
                    if($result['SHOW_COUNTER'] > 2){
                        global $APPLICATION;
                        $APPLICATION->throwException(
                            "Товар невозможно деактивировать, у него [".$show_counter."] просмотров"
                        );
                        return false;                
                    }
                }
            }

            // 2. Регистрация события при изменении инфоблока "Услуги"
            if ($arFields['IBLOCK_ID'] == 3) {
                CBitrixComponent::clearComponentCache("my_components:simplecomp2.exam");
            }
        }
        
        function OnBeforeEventAddHandler(&$event, &$lid, &$arFields){
            
            //Регистрация события при изменении макроса в почтовом сообщении
            if($event == "FEEDBACK_FORM"){
                
                $author = $arFields["AUTHOR"];
                global $USER;
                
                if ($USER->IsAuthorized()){
                    $arFields["AUTHOR"] = "Пользователь авторизован: ".$USER->GetID()." (".$USER->GetLogin().") ".$USER->GetFullName().", данные из формы: ".$author;
                    
                }else{
                    
                    $arFields["AUTHOR"] = "Пользователь не авторизован, данные из формы: ".$author;
                }

                CEventLog::Add(array(
                    "AUDIT_TYPE_ID" => "Замена данных в отсылаемом письме",
                    "DESCRIPTION" => "Замена данных в отсылаемом письме – ".$arFields["AUTHOR"],
                ));
            }
        }

        function OnBuildGlobalMenuHandler(&$aGlobalMenu, &$aModuleMenu){
            
            $contentManagerGroupId = 5;
            global $USER;
            if(in_array($contentManagerGroupId, $USER->GetUserGroupArray()) && !$USER->IsAdmin()){
                
                foreach($aGlobalMenu as $menuItem => $menuItemArray){

                    if($menuItem != "global_menu_content"){

                        unset($aGlobalMenu[$menuItem]);   
                    }
                }
                
                foreach($aModuleMenu as $menuItem => $menuItemArray){

                    if($aModuleMenu[$menuItem]["parent_menu"] !== "global_menu_content" ||
                        $aModuleMenu[$menuItem]["items_id"] !== "menu_iblock_/news"){
                        
                        unset($aModuleMenu[$menuItem]);
                    }
                }
            }
        }
        
        function OnEpilogHandler(){
            
            if(CModule::IncludeModule("iblock")){
                global $APPLICATION;
                $arFilter = array("IBLOCK_ID" => 6, "NAME" => $APPLICATION->GetCurPage());
                $arSelectFields = array("IBLOCK_ID", "ID", "PROPERTY_TITLE", "PROPERTY_DESCRIPTION");

                $result = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelectFields) -> Fetch();
                
                if($result){

                    $APPLICATION->SetPageProperty("title", $result["PROPERTY_TITLE_VALUE"]);
                    $APPLICATION->SetPageProperty("description", $result["PROPERTY_DESCRIPTION_VALUE"]);
                }
            }
        }
    }
    
    //Функция подсчета новых пользователей
    function CheckUserCount(){
        
        $checkPeriod = 30;
        $currenCheckTime = date('d.m.Y  H:i:s');
        $previousCheckTime = date('d.m.Y  H:i:s', strtotime("-$checkPeriod day"));

        // echo "<pre>"; print_r("от " . $previousCheckTime); echo "</pre>";
        // echo "<pre>"; print_r("до " . $currenCheckTime); echo "</pre>";

        $filter = array(
           "DATE_REGISTER_1" => $previousCheckTime,
           "DATE_REGISTER_2" => $currenCheckTime,
        );

        $count = 0;
        $resultUsers = CUser::GetList(($by="ID"), ($order="ASC"), $filter);
        while ($resultUser = $resultUsers->Fetch()){
            // echo "<pre>"; print_r($resultUser["LOGIN"] . " - " . $resultUser["DATE_REGISTER"]); echo "</pre>";
            $count++;
        }
        
        //Email'ы администраторов
        $userBy = "ID";
        $userOrder = "ASC";
        $userFilter = array("GROUPS_ID" => 1);
        $userParams = array("SELECT" => array(), "NAV_PARAMS" => array(),"FIELDS" => array(
                "EMAIL",
            ),
        );
        $resultUsers = CUser::GetList($userBy, $userOrder, $userFilter, $userParams);
        while ($resultUser = $resultUsers->Fetch()){
            $adminUserEmails[] = $resultUser["EMAIL"];
        }

        //Отправка сообщений
        $arEventFields = array(
            "EMAIL" => implode(", ", $adminUserEmails),
            "COUNT" => $count,
            "DAYS" => $checkPeriod,
        );
        // echo (CEvent::Send("AGENT_USER_COUNT", "s1", $arEventFields));
        // echo "<pre>"; print_r($arEventFields["EMAIL"]); echo "</pre>";
        
        CEventLog::Add(array(
            "AUDIT_TYPE_ID" => "Подсчет пользователей",
            "DESCRIPTION" => "На сайте зарегистрировано [{$arEventFields["COUNT"]}] пользователей за [{$arEventFields["DAYS"]}] дней, email'ы: {{$arEventFields["EMAIL"]}}",
        ));
        
        return "CheckUserCount();";
    }
?>
