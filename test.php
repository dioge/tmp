<?
    if (isset($_REQUEST['work_start'])){
        define("NO_AGENT_STATISTIC", true);
        define("NO_KEEP_STATISTIC", true);
    }

    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
    CModule::IncludeModule("iblock");
    CModule::IncludeModule("catalog");
    IncludeModuleLangFile(__FILE__);

    $POST_RIGHT = $APPLICATION->GetGroupRight("main");
    if ($POST_RIGHT == "D"){
        $APPLICATION->AuthForm("Доступ запрещен");
    }

    $BID = 7;
    $limit = 3;
    if($_REQUEST['work_start'] && check_bitrix_sessid()){

        // общее количество элементов
        if(!$_REQUEST["allCnt"]){
            $allCnt = CIBlockElement::GetList(
                array(),
                array(
                    "IBLOCK_ID" => $BID,
                    array(
                        "LOGIC" => "AND",
                        array("!SECTION_ID" => 7),
                        array("!SECTION_ID" => 8),
                    )
                ),
                array(),
                false,
                array('ID', 'NAME')
            );
        }else{
            $allCnt = $_REQUEST["allCnt"];
        }
        
        // удаление элементов
        $deletedCnt = $_REQUEST["deletedCnt"];
        $rsEl = CIBlockElement::GetList(
            array(), // or
            // array("ID" => "ASC"),
            array(
                "IBLOCK_ID" => $BID,
                array(
                    "LOGIC" => "AND",
                    array("!SECTION_ID" => 7),
                    array("!SECTION_ID" => 8),
                ),
                // ">ID" => $_REQUEST["lastid"]
            ), 
            false,
            array("nTopCount" => $limit),
            array("ID", "NAME")
        );
        $deletedCnt += $rsEl->SelectedRowsCount();
        while ($arEl = $rsEl->Fetch()){
            $lastID = intval($arEl["ID"]);
            $name = $arEl["NAME"];

            if((CIBlockElement::Delete($lastID))
                // && // удаляем элемент 
            // (CCatalogProduct::Delete($lastID))
            ){ // удаляем торговое предложение
                continue;
            }else{
                echo "Ошибка при удалении";
                die();
            }
        }
        
        // процент удаленных элементов
        $percent = round(100 * $deletedCnt / $allCnt, 2);

        echo 'CurrentStatus = Array(
                '.$percent.',
                "'.($percent < 100 ? '&lastid='.$lastID : '').'",
                "Обрабатываю запись с ID #'.$lastID.'",
                "'.$name.'",
                "&deletedCnt='.$deletedCnt.'",
                "&allCnt='.$allCnt.'"
            );';
        die();
    }

    $clean_test_table = '<table id="result_table" cellpadding="0" cellspacing="0" border="0" width="100%" class="internal">'.
                            '<tr class="heading">'.
                                '<td>Текущее действие</td>'.
                                '<td width="1%">&nbsp;</td>'.
                            '</tr>'.
                        '</table>';

    $aTabs = array(array("DIV" => "edit1", "TAB" => "Обработка"));
    $tabControl = new CAdminTabControl("tabControl", $aTabs);

    $APPLICATION->SetTitle("Обработка элементов инфоблока");

    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<script>

    var bWorkFinished = false;
    var bSubmit;

    function set_start(val){
        document.getElementById('work_start').disabled = val ? 'disabled' : '';
        document.getElementById('work_stop').disabled = val ? '' : 'disabled';
        document.getElementById('progress').style.display = val ? 'block' : 'none';

        if (val){
            ShowWaitWindow();
            document.getElementById('status').innerHTML = 'Работаю...';
            document.getElementById('indicator').style.width = '0%';
            document.getElementById('percent').innerHTML = '0%';
            document.getElementById('result').innerHTML = '<?=$clean_test_table?>';

            CHttpRequest.Action = work_onload;
            CHttpRequest.Send('<?= $_SERVER["PHP_SELF"]?>?work_start=Y&lang=<?=LANGUAGE_ID?>&<?=bitrix_sessid_get()?>');
        }else{
            CloseWaitWindow();
        }
    }

    function work_onload(result){
        try{
            eval(result);

            iPercent = CurrentStatus[0];
            strNextRequest = CurrentStatus[1];
            strCurrentAction = CurrentStatus[2];
            strName = CurrentStatus[3];
            iDeletedCnt = CurrentStatus[4];
            iAllCnt = CurrentStatus[5];

            document.getElementById('status').innerHTML = 'Работаю...';
            document.getElementById('indicator').style.width = iPercent + '%';
            document.getElementById('percent').innerHTML = iPercent + '%';

            if (strCurrentAction != 'null'){
                oTable = document.getElementById('result_table');
                oRow = oTable.insertRow(-1);
                oCell = oRow.insertCell(-1);
                oCell.innerHTML = strCurrentAction + ' ' + strName;
                oCell = oRow.insertCell(-1);
                oCell.innerHTML = '';
            }

            if (strNextRequest && document.getElementById('work_start').disabled){
                CHttpRequest.Send('<?= $_SERVER["PHP_SELF"]?>?work_start=Y&lang=<?=LANGUAGE_ID?>&<?=bitrix_sessid_get()?>' + strNextRequest + iDeletedCnt + iAllCnt);
            }else{
                set_start(0);
                bWorkFinished = true;
            }

        }catch(e){
            CloseWaitWindow();
            document.getElementById('work_start').disabled = '';
            alert('Сбой в получении данных: ' + e);
            alert(result);
        }
    }

</script>

<form method="post" action="<?echo $APPLICATION->GetCurPage()?>" enctype="multipart/form-data" name="post_form" id="post_form">

<?
    echo bitrix_sessid_post();

    $tabControl->Begin();
    $tabControl->BeginNextTab();
?>

    <tr>
        <td colspan="2">

            <input id="work_start" type=button value="Старт" onclick="set_start(1)" />
            <input id="work_stop" type=button value="Стоп" disabled onclick="bSubmit=false;set_start(0)" />
            <div id="progress" style="display:none;" width="100%">
            <br>
                <div id="status"></div>
                <table border="0" cellspacing="0" cellpadding="2" width="100%">
                    <tr>
                        <td height="10">
                            <div style="border:1px solid #B9CBDF">
                                <div id="indicator" style="height:10px; width:0%; background-color:#B9CBDF"></div>
                            </div>
                        </td>
                        <td width=30>
                            &nbsp;<span id="percent">
                                0%
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="result" style="padding-top:10px"></div>

        </td>
    </tr>
    
<?
    $tabControl->End();
?>

</form>

<?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
