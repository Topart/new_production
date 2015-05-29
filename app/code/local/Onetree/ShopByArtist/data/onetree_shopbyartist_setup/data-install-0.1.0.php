<?php
    $installer      = $this;
    $inMenuObj      = Mage::getSingleton('catalog/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY,'include_in_menu');
    $parentId       = Mage::helper('onetree_shopbyartist')->getArtistCategoryId();
    $artists        = Mage::getResourceModel('catalog/category_collection')
                                    ->addAttributeToFilter('parent_id',$parentId)
                                    ->load();

    $inMenuId       = $inMenuObj->getId();
    $table          = $inMenuObj->getBackend()->getTable();

    $entityIdWhere  = implode(',',array_keys($artists->getItems()));
    $writeAdapter   = $installer->getConnection();
    $writeAdapter->update($table,array('value'=>0),'attribute_id ='.$inMenuId.' AND entity_id in('.$entityIdWhere.')');
