<?php

class nvCategoryManager
{
    public function __construct()
    {
        $this->addon = rex_addon::get('nv_categorymanager');
    }

    public function getTree($iParentId = 0, $iLevel = 0)
    {
        $aItems = array();
        $oItems = rex_sql::factory();
        $sQuery = "SELECT catname,id,parent_id,priority,catpriority FROM " . rex::getTablePrefix() . "article WHERE parent_id = '$iParentId' && startarticle = '1' && clang_id = '" . $this->getDefaultClangId() . "'  ORDER BY catpriority ASC";
        $oItems->setQuery($sQuery);

        foreach ($oItems as $oItem) {
            array_push($aItems, array('name' => $oItem->getValue('catname'), 'level' => $iLevel, 'priority' => $oItem->getValue('catpriority'), 'id' => $oItem->getValue('id'), 'parent_id' => $oItem->getValue('parent_id'), 'children' => $this->getTree($oItem->getValue('id'), $iLevel + 1)));
        }

        return $aItems;
    }

    public function getDefaultClangId()
    {
        $oItems = rex_sql::factory();
        $sQuery = "SELECT id,code,name FROM " . rex::getTablePrefix() . "clang ORDER BY priority ASC Limit 1";
        $oItems->setQuery($sQuery);
        return $oItems->getValue('id');
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
            $aOut[] = '<option value="' . $aItem['id'] . '" ';
            if ($sCheckValue == $aItem['id']) {
                $aOut[] = 'selected';
            }
            $aOut[] = '>';
            for ($x = 0; $x < $aItem['level']; $x++) {
                $aOut[] = '&nbsp;&nbsp;';
            }

			$aOut[] = $aItem['name'] . ' (' . $aItem['id'] . ')</option>';
            if (count($aItem['children'])) {
                $aOut[] = $this->parseTreeSelection($sFieldname, $aItem['children']);
            }
        }
        $sOut = implode("\n", $aOut);
        return $sOut;
    }

    public function parseMediaManagerMove()
    {
        $tree =
        $content = [];

        $content[] = '<div class="row">';
        $content[] = '<div class="col-sm-6">';
        $content[] = '<strong>Quelle</strong><br><select class="form-control selectpicker" data-live-search="true" name="addon_media_cat_from">';
        $content[] = '<option id="0">ROOT</option>';
        $content[] = $this->getMediaManagerTreeRecAsOptions(0, 0, rex_post("addon_media_cat_from", "int"));
        $content[] = '</select>';
        $content[] = '</div>';
        $content[] = '<div class="col-sm-6">';
        $content[] = '<strong>Ziel</strong><br><select class="form-control selectpicker" data-live-search="true" name="addon_media_cat_to">';
        $content[] = '<option id="0">ROOT</option>';
        $content[] = $this->getMediaManagerTreeRecAsOptions(0, 0, rex_post("addon_media_cat_to", "int"));
        $content[] = '</select>';
        $content[] = '</div>';
        $content[] = '</div>';

        return implode("", $content);
    }

    public function deleteCategory($iId)
    {
        $oCategory = rex_sql::factory()->setQuery("SELECT * FROM " . rex::getTablePrefix() . "article WHERE id = '$iId' && clang_id = '" . $this->getDefaultClangId() . "' Limit 1");
        $iParentId = $oCategory->getValue('parent_id');
        $aArticles = $this->getArticles($iId);
        foreach ($aArticles as $iArticleId) {
            rex_article_service::_deleteArticle($iArticleId);
        }

        $aChildrenCategories = $this->getChildrenCategories($iId);
        foreach ($aChildrenCategories as $iCategoryId) {
            $this->deleteCategory($iCategoryId);
        }

        rex_article_service::_deleteArticle($iId);
        foreach (rex_clang::getAllIds() as $iClangId) {
            rex_category_service::newCatPrio($iParentId, $iClangId, 0, 1);
        }
    }

    public function copyCategory($iSourceId, $iTargetId)
    {
        $iNewCategoryId = rex_article_service::copyArticle($iSourceId, $iTargetId);
        rex_article_service::article2category($iNewCategoryId);
        
        $sql = rex_sql::factory();
        $query = "SELECT catname FROM " . rex::getTablePrefix . "article WHERE id = :sourceId AND clang_id = :clang_id";
        $sql->setQuery($query, ["sourceId" => $iSourceId, "clang_id" => $this->getDefaultClangId()]);

        $query = "UPDATE " . rex::getTablePrefix . "article SET catname = :catname WHERE id = :new_id AND clang_id = :clang_id";
        $sCatname = $sql->getValue("catname");
        if ($this->addon->getConfig("suffix") != "") {
            $sCatname .= " ".$this->addon->getConfig("suffix");
        }
        $sql->setQuery($query, ["catname" => $sCatname, "new_id" => $iNewCategoryId, "clang_id" => $this->getDefaultClangId()]);

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
    
    
    
    public function copyArticle($iSourceId, $iTargetId)
    {
      
      rex_content_service::copyContent($iSourceId, $iTargetId, $this->getDefaultClangId(), $this->getDefaultClangId());
      
    }

    public function moveMediaManagerCategory(rex_sql $sql, int $from = null, int $to = null) : void
    {
        if ($from === null || $to === null) {
            throw new Exception("Ungenügende Parameteranzahl");
        }
        if (!$this->validateMove($from, $to)) {
            throw new Exception("Kategorie kann nicht in sich selbst verschoben werden.");
        }

        $querySelectRootCategories = "SELECT id FROM " . rex::getTablePrefix . "media_category WHERE parent_id='0'";

        //$sql->setQuery("INSERT INTO rex_config (rex_config.namespace, rex_config.key, rex_config.value) VALUES ('test', 'test', 'test')");

        if ($from === 0) {
            $query = "UPDATE " . rex::getTablePrefix . "media SET category_id = :pid WHERE category_id = :fid";
            $sql->setQuery($query, ["pid" => $to, "fid" => $from]);

            $sql->setQuery($querySelectRootCategories);
            if (!$sql->getRows()) {
                throw new Exception("Keine Rootkategorie vorhanden.");
            }

            $rows = $sql->getArray();

            $sql->prepareQuery("UPDATE " . rex::getTablePrefix . "media_category SET parent_id=:pid WHERE id=:id");

            $toCat = rex_media_category::get($to);
            $toPath = $toCat->getPathAsArray();

            foreach ($rows as $row) {
                if (!in_array($row["id"], $toPath) && $row["id"] != $to) {
                    $sql->execute(["pid" => $to, "id" => $row["id"]]);
                }
            }
        } else {
            $query = "UPDATE " . rex::getTablePrefix . "media_category SET parent_id = :pid WHERE id = :fid";
            $sql->setQuery($query, ["pid" => $to, "fid" => $from]);
        }
    
        $sql->setQuery($querySelectRootCategories);
        if (!$sql->getRows()) {
            throw new Exception("Keine Rootkategorie vorhanden.");
        }
    
        foreach ($sql->getArray() as $row) {
            $category = rex_media_category::get($row["id"]);
            $this->recBuildMediaCategoryPaths($sql, $category);
        }
    }

    public function recBuildMediaCategoryPaths(rex_sql $sql, rex_media_category $category = null, string $toPath = "|") : void
    {
        $children = $category->getChildren();
        $query = "UPDATE " . rex::getTablePrefix . "media_category SET path=:path WHERE id=:id";
        $sql->setQuery($query, ["path" => $toPath, "id" => $category->getId()]);
        foreach ($children as $child) {
            $this->recBuildMediaCategoryPaths($sql, $child, $toPath . $category->getId() . "|");
        }
    }

    public function validateMove(int $from, int $to) : bool
    {
        if ($from === 0 && $to === 0) {
            return false;
        }
        if ($from == $to) {
            return false;
        }
        $toCat = rex_media_category::get($to);
        $path = $to === 0 ? [] : $toCat->getPathAsArray();

        foreach ($path as $sId) {
            $iId = intval($sId);
            if (!$iId || $iId == $from) {
                return false;
            }
        }

        return true;
    }

    public function getChildrenCategories($iParentId)
    {
        $aCategories = array();
        $oSql = rex_sql::factory();
        $oSql->setQuery("SELECT * FROM " . rex::getTablePrefix() . "article WHERE parent_id = '$iParentId' && clang_id = '" . $this->getDefaultClangId() . "' && catpriority != '0' ORDER BY catpriority ASC");
        foreach ($oSql as $oCategories) {
            array_push($aCategories, $oCategories->getValue('id'));
        }
        return $aCategories;
    }

    public function getArticles($iParentId)
    {
        $aArticles = array();
        $oSql = rex_sql::factory();
        $oSql->setQuery("SELECT * FROM " . rex::getTablePrefix() . "article WHERE parent_id = '$iParentId' && clang_id = '" . $this->getDefaultClangId() . "' && catpriority = '0' ORDER BY priority ASC");
        foreach ($oSql as $oArticles) {
            array_push($aArticles, $oArticles->getValue('id'));
        }
        return $aArticles;
    }

    public function getMediaManagerTreeRecAsOptions(int $catId=0, int $level=0, int $selected) : string
    {
        $sql = rex_sql::factory();
        $query = "SELECT id, parent_id, name FROM rex_media_category WHERE parent_id=:id";
        $sql->setQuery($query, ["id" => $catId]);
        if (!$sql->getRows()) {
            return "";
        }

        $data = "";
        foreach ($sql as $row) {
            $s = $selected==$row->getValue("id")?'selected':'';
            $data .= '<option ' . $s . ' value="' . $row->getValue("id") . '">' . $this->getLevelIntendation($level) . $row->getValue("name") . '</option>';
            $data .= $this->getMediaManagerTreeRecAsOptions($row->getValue("id"), $level + 1, $selected);
        }
        return $data;
    }

    public function getLevelIntendation(int $level) : string
    {
        $data = "";
        for ($i = 0; $i < $level; $i++) {
            $data .= '&nbsp;&nbsp;';
        }
        return $data;
    }
}