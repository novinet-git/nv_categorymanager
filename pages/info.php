<?php

$file = rex_file::get(rex_path::addon('nv_categorymanager', 'README.md'));
$body = rex_markdown::factory()->parse($file);
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', "Info", false);
$fragment->setVar('body', $body, false);
$content = $fragment->parse('core/page/section.php');
echo $content;

$file = rex_file::get(rex_path::addon('nv_categorymanager', 'CHANGELOG.md'));
$body = rex_markdown::factory()->parse($file);
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', "Changelog", false);
$fragment->setVar('body', $body, false);
$content = $fragment->parse('core/page/section.php');
echo $content;