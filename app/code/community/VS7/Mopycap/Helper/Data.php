<?php

class VS7_Mopycap_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getChildrenAnchor($categoriesIds)
    {
        $categoryCollection = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('is_anchor')
            ->addAttributeToFilter('entity_id', array('IN' => $categoriesIds));

        foreach ($categoryCollection as $category) {
            $categoryId = (int)$category->getId();
            if (!in_array($categoryId, $categoriesIds)) {
                $categoriesIds[] = $categoryId;
            }
            if ($category->getIsAnchor()) {
                $children = Mage::getResourceModel('catalog/category_collection')
                    ->addAttributeToFilter('is_active', array('in' => array(0, 1)))
                    ->addAttributeToFilter('parent_id', $category->getId())
                    ->getAllIds();

                if (!empty($children)) {
                    $categoriesIds1 = $this->getChildrenAnchor($children);
                    $categoriesIds = array_merge($categoriesIds, $categoriesIds1);
                }
            }
        }

        return $categoriesIds;
    }
}