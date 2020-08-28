<?php

class nvCategoryManager
{

	public function __construct()
	{
	}

	public function getTree($iParentId = 0, $iLevel = 0)
	{

		$aItems = array();
		$oItems = rex_sql::factory();
		$sQuery = "SELECT catname,id,parent_id,priority FROM " . rex::getTablePrefix() . "article WHERE parent_id = '$iParentId' && startarticle = '1' && clang_id = '" . $this->getDefaultClangId() . "'  ORDER BY catpriority ASC";
		$oItems->setQuery($sQuery);

		foreach ($oItems as $oItem) {
			array_push($aItems, array(name => $oItem->getValue(catname), level => $iLevel, priority => $oItem->getValue(catpriority), id => $oItem->getValue(id), parent_id => $oItem->getValue(parent_id), children => $this->getTree($oItem->getValue(id), $iLevel + 1)));
		}

		return $aItems;
	}

	public function getDefaultClangId()
	{
		$oItems = rex_sql::factory();
		$sQuery = "SELECT id,code,name FROM " . rex::getTablePrefix() . "clang ORDER BY priority ASC Limit 1";
		$oItems->setQuery($sQuery);
		return $oItems->getValue(id);
	}

	public function parseTreeList($aItems, $bIsActionCopy = true)
	{
		$aOut = array();

		$aOut[] = '<div class="row">';
		$aOut[] = '<div class="col-sm-6 mr-3"><strong>Quelle</strong><br><select class="form-control selectpicker" data-live-search="true" name="nv_source_id">' . $this->parseTreeSelection("nv_source_id", $aItems) . '</select></div>';
		if ($bIsActionCopy) {
			$aOut[] = '<div class="col-sm-6"><strong>Ziel</strong><br><select class="form-control selectpicker" data-live-search="true" name="nv_target_id"><option value="0">Kein Elternelement</option>' . $this->parseTreeSelection("nv_target_id", $aItems) . '</select></div>';
		}
		$aOut[] = '</div><br>';

		$sOut = implode("\n", $aOut);
		return $sOut;
	}

	public function parseTreeSelection($sFieldname, $aItems)
	{
		//print_r($aItems);
		$aOut = array();
		$sCheckValue = rex_request($sFieldname, 'int');
		foreach ($aItems as $aItem) {
			$aOut[] = '<option value="' . $aItem[id] . '" ';
			if ($sCheckValue == $aItem[id]) $aOut[] = 'selected';
			$aOut[] = '>';
			for ($x = 0; $x < $aItem[level]; $x++) {
				$aOut[] = '&nbsp;&nbsp;';
			}

			$aOut[] = $aItem[name] . '</option>';
			if (count($aItem[children])) {
				$aOut[] = $this->parseTreeSelection($sFieldname, $aItem[children]);
			}
		}
		$sOut = implode("\n", $aOut);
		return $sOut;
	}

	public function deleteCategory($iId)
	{
		$oCategory = rex_sql::factory()->setQuery("SELECT * FROM " . rex::getTablePrefix() . "article WHERE id = '$iId' && clang_id = '" . $this->getDefaultClangId() . "' Limit 1");
		$iParentId = $oCategory->getValue(parent_id);
		$aArticles = $this->getArticles($iId);
		foreach ($aArticles as $iArticleId) {
			rex_article_service::_deleteArticle($iArticleId);
		}

		$aChildrenCategories = $this->getChildrenCategories($iId);
		foreach ($aChildrenCategories as $iCategoryId) {
			$this->deleteCategory($iCategoryId);
		}

		rex_article_service::_deleteArticle($iId);
		foreach(rex_clang::getAllIds() as $iClangId) {
			rex_category_service::newCatPrio($iParentId, $iClangId, 0, 1);
		}
	}

	public function copyCategory($iSourceId, $iTargetId)
	{
		$aArticles = $this->getArticles($iSourceId);
		$iNewCategoryId = rex_article_service::copyArticle($iSourceId, $iTargetId);
		rex_article_service::article2category($iNewCategoryId);

		// get articles
		$aArticles = $this->getArticles($iSourceId);
		foreach ($aArticles as $iArticleId) {
			rex_article_service::copyArticle($iArticleId, $iNewCategoryId);
		}

		// get children categories
		$aChildrenCategories = $this->getChildrenCategories($iSourceId);
		foreach ($aChildrenCategories as $iCategoryId) {
			$this->copyCategory($iCategoryId, $iNewCategoryId);
		}
	}

	function getChildrenCategories($iParentId)
	{
		$aCategories = array();
		$oSql = rex_sql::factory();
		$oSql->setQuery("SELECT * FROM " . rex::getTablePrefix() . "article WHERE parent_id = '$iParentId' && clang_id = '" . $this->getDefaultClangId() . "' && catpriority != '0' ORDER BY catpriority ASC");
		foreach ($oSql as $oCategories) {
			array_push($aCategories, $oCategories->getValue(id));
		}
		return $aCategories;
	}

	function getArticles($iParentId)
	{
		$aArticles = array();
		$oSql = rex_sql::factory();
		$oSql->setQuery("SELECT * FROM " . rex::getTablePrefix() . "article WHERE parent_id = '$iParentId' && clang_id = '" . $this->getDefaultClangId() . "' && catpriority = '0' ORDER BY priority ASC");
		foreach ($oSql as $oArticles) {
			array_push($aArticles, $oArticles->getValue(id));
		}
		return $aArticles;
	}
}
