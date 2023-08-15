<?php
$oCategoryManager = new nvCategoryManager;


$form = rex_config_form::factory($oCategoryManager->addon->name);

$field = $form->addInputField('text', 'suffix', null, ["class" => "form-control"]);
$field->setLabel($this->i18n('nv_categorymanager_suffix'));


$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $this->i18n('nv_categorymanager_settings'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');


return;