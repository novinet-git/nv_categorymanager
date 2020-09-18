<?php
$oManager = new nvCategoryManager();
$csrfToken = rex_csrf_token::factory('nv_categorymanager');



if (rex_post('addon_action', 'string')) {
   
    if(!$csrfToken->isValid())  echo rex_view::error("Ein Fehler ist aufgetreten. Bitte wenden Sie sich an den Webmaster.");
    else 
    {
        switch(rex_post('addon_action', 'string'))
        {
            case "copy":
    
                $bError = false;
                $iSourceId = rex_request('nv_source_id', 'int');
                $iTargetId = rex_request('nv_target_id', 'int');
                $iClangId = rex_request('nv_clang_id', 'int');

                if ($iSourceId == $iTargetId) {
                    $bError = true;
                }

                if (!$bError) {

                    //$oManager->copyCategory($iSourceId,$iTargetId,$iClangId,true);
                    $oManager->copyCategory($iSourceId,$iTargetId);
                    rex_delete_cache();
                    echo rex_view::success("Kategorie erfolgreich kopiert.");
                } else {
                    echo rex_view::error("Es ist ein Fehler aufgetreten.");
                }
            break;

            case "delete":
                $bError = false;
                $iId = rex_request('nv_source_id', 'int');

                if (!$bError) {
                    $oManager->deleteCategory($iId);
                    rex_delete_cache();
                    echo rex_view::success("Kategorie erfolgreich gelöscht.");
                } else {
                    echo rex_view::error("Es ist ein Fehler aufgetreten.");
                }
            break;

            case "move":
                $bError = false;
                $sql = rex_sql::factory();
                $sql->beginTransaction();

                try 
                {
                    $oManager->moveMediaManagerCategory($sql, rex_post("addon_media_cat_from", "int"), rex_post("addon_media_cat_to", "int"));
                    $sql->commit();
                    rex_delete_cache();
                    echo rex_view::success("Medien Kategorie erfolgreich verschoben.");
                }
                catch(Exception $e)
                {
                    $sql->rollBack();
                    echo rex_view::error($e->getMessage());
                }
              
            break;
            default: echo rex_view::error("Es ist ein Fehler aufgetreten.");
        }
    }
}

$aTree = $oManager->getTree();
$sContent = '<div class="container-fluid">';
$sContent .= $oManager->parseTreeList($aTree);
$sContent .= '</div>';


$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save" type="submit" name="addon_action" value="copy">Kopieren</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');
$buttons = '
<fieldset class="rex-form-action">
' . $buttons . '
</fieldset>
';

$fragment = new rex_fragment();
$fragment->setVar("class", "edit");
$fragment->setVar('title', "Kategorie kopieren", false);
$fragment->setVar('body', $sContent, false);
$fragment->setVar("buttons", $buttons, false);
$output = $fragment->parse('core/page/section.php');

$output = '<form action="' . rex_url::currentBackendPage() . '" method="post">'
. '<input type="hidden" name="copycategory" value="1" />'
. $csrfToken->getHiddenField() 
. $output 
. '</form>';

echo $output;


$sContent = '<div class="container-fluid">';
$sContent .= $oManager->parseTreeList($aTree,false);
$sContent .= '</div>';


$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-delete" type="submit" name="addon_action" value="delete" onclick="return confirm(\'Wirklich unwiderruflich löschen?\')">Löschen</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');
$buttons = '
<fieldset class="rex-form-action">
' . $buttons . '
</fieldset>
';

$fragment = new rex_fragment();
$fragment->setVar("class", "edit");
$fragment->setVar('title', "Kategorie löschen", false);
$fragment->setVar('body', $sContent, false);
$fragment->setVar("buttons", $buttons, false);
$output = $fragment->parse('core/page/section.php');

$output = '<form action="' . rex_url::currentBackendPage() . '" method="post">'
. '<input type="hidden" name="deletecategory" value="1" />'
. $csrfToken->getHiddenField() 
. $output 
. '</form>';

echo $output;

$sContent = '<div class="container-fluid">';
$sContent .= $oManager->parseMediaManagerMove();
$sContent .= '</div>';

$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save" type="submit" name="addon_action" value="move" onclick="return confirm(\'Wirklich verschieben?\')">Verschieben</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');
$buttons = '
<fieldset class="rex-form-action">
' . $buttons . '
</fieldset>
';

$fragment = new rex_fragment();
$fragment->setVar("class", "edit");
$fragment->setVar('title', "Medien Kategorie Verschieben", false);
$fragment->setVar('body', $sContent, false);
$fragment->setVar("buttons", $buttons, false);
$output = $fragment->parse('core/page/section.php');

$output = '<form action="' . rex_url::currentBackendPage() . '" method="post">'
. '<input type="hidden" name="deletecategory" value="1" />'
. $csrfToken->getHiddenField() 
. $output 
. '</form>';

echo $output;