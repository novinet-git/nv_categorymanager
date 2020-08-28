<?php
$oManager = new nvCategoryManager();
$csrfToken = rex_csrf_token::factory('nv_categorymanager');



if (rex_post('copycategory', 'string') == '1' && !$csrfToken->isValid()) {
    echo rex_view::error("Ein Fehler ist aufgetreten. Bitte wenden Sie sich an den Webmaster.");

} else {
    if (rex_post('copycategory', 'string') == '1') {
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
    }

    if (rex_post('deletecategory', 'string') == '1') {
        $bError = false;
        $iId = rex_request('nv_source_id', 'int');

        if (!$bError) {
            $oManager->deleteCategory($iId);
            rex_delete_cache();
            echo rex_view::success("Kategorie erfolgreich gelöscht.");
        } else {
            echo rex_view::error("Es ist ein Fehler aufgetreten.");
        }
    }
}

if(!isset($_POST["file"])) {

}

$aTree = $oManager->getTree();
$sContent = '<div class="container-fluid">';
$sContent .= $oManager->parseTreeList($aTree);
$sContent .= '</div>';


$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save" type="submit" name="save" value="Speichern">Kopieren</button>';
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
$n['field'] = '<button class="btn btn-delete" type="submit" name="delete" value="Löschen" onclick="return confirm(\'Wirklich unwiderruflich löschen?\')">Löschen</button>';
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