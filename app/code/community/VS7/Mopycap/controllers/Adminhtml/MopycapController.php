<?php

class VS7_Mopycap_Adminhtml_MopycapController extends Mage_Adminhtml_Controller_Action
{

    public function categoriesAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->_setActiveMenu('catalog/categories/mopycap');
        $this->_addBreadcrumb(Mage::helper('vs7_mopycap')->__('Move Categories'), Mage::helper('vs7_mopycap')->__('Move Categories'));
        $this->_addContent($this->getLayout()->createBlock('vs7_mopycap/adminhtml_categories'));
        $this->renderLayout();
    }

    public function productsAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->_setActiveMenu('catalog/categories/moveproducts');
        $this->_addBreadcrumb(Mage::helper('vs7_mopycap')->__('Move Products'), Mage::helper('vs7_mopycap')->__('Move Products'));
        $this->_addContent($this->getLayout()->createBlock('vs7_mopycap/adminhtml_products'));
        $this->renderLayout();
    }

    public function mopyProductsAction()
    {
        $helper = Mage::helper('vs7_mopycap');

        $fromIds = $this->getRequest()->getParam('from_ids');
        $toId = $this->getRequest()->getParam('to_id');
        $mode = $this->getRequest()->getParam('mopy');

        $storeId = Mage::getResourceModel('core/store_collection')->getFirstItem()->getId();

        $_separator = ',';
        $coreResource = Mage::getSingleton('core/resource');
        $writeConnection = $coreResource->getConnection('core_write');

        try {
            if (empty($fromIds) || empty($toId)) {
                throw new Exception('Missing data');
            }

            if (!preg_match('/^\d+$/', $toId)) {
                throw new Exception('Incorrect Id');
            } else {
                $toId = (int)$toId;
            }

            if (preg_match('/^(\d+\,)+\d+$/', $fromIds)) {
                $delimeter = ',';
            } elseif (preg_match('/^(\d+\s)+\d+$/', $fromIds)) {
                $delimeter = ' ';
            } elseif (preg_match('/^\d+$/', $fromIds)) {
                $fromIds = array((int)$fromIds);
            } else {
                throw new Exception('Incorrect Id');
            }
            if (isset($delimeter)) {
                $fromIds = explode($delimeter, $fromIds);
            }

            $category = Mage::getModel('catalog/category')->load($toId);
            if ($category->getId() < 1) {
                throw new Exception('New parent category doesn`t exist');
            }

            $fromIds = Mage::helper('vs7_mopycap')->getChildrenAnchor($fromIds); // Append all sub is_anchor categories

            $productCollection = Mage::getModel('catalog/product')->getCollection() // Get (fill) products with corresponding category ids (separated by comma)
                ->setStoreId($storeId);
            $subQuery = 'SELECT product_id, GROUP_CONCAT(`category_id` SEPARATOR "' . $_separator . '") AS category_ids FROM ' . $coreResource->getTableName('catalog/category_product') . ' WHERE (category_id IN (' . implode(', ', $fromIds) . ')) GROUP BY product_id';
            $productCollection->getSelect()
                ->joinLeft(
                    new Zend_Db_Expr('(' . $subQuery . ')'),
                    'product_id=e.entity_id'
                )
                ->where('category_ids IS NOT NULL');

            foreach ($productCollection as $product) {
                $categoryIds = $product->getCategoryIds();
                $categoryIds = explode($_separator, $categoryIds[0]);
                if ($mode == 'copy') { // INSERT if not exists
                    if (!in_array($toId, $categoryIds)) {
                        $writeConnection->insert(
                            $coreResource->getTableName('catalog/category_product'),
                            array('product_id' => $product->getId(), 'category_id' => $toId)
                        );
                    }
                } else if ($mode == 'move') { //DELETE all and INSERT
                    if (
                        count($categoryIds) > 1
                        || (
                            count($categoryIds) == 1
                            && !in_array($toId, $categoryIds)
                        )
                    ) {
                        $writeConnection->delete(
                            $coreResource->getTableName('catalog/category_product'),
                            array('product_id IN(?)' => array($product->getId()))
                        );
                        $writeConnection->insert(
                            $coreResource->getTableName('catalog/category_product'),
                            array('product_id' => $product->getId(), 'category_id' => $toId)
                        );
                    }
                }
            }

            $process = Mage::getModel('index/indexer')->getProcessByCode('catalog_category_product');
            $process->reindexAll();
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addWarning($helper->__($e->getMessage()));
            $this->_redirect('*/*/products');
            return;
        }

        if ($mode == 'move') {
            Mage::getSingleton('adminhtml/session')->addNotice($helper->__('Successfully moved!'));
        } else if ($mode == 'copy') {
            Mage::getSingleton('adminhtml/session')->addNotice($helper->__('Successfully copied!'));
        }

        $this->_redirect('*/*/products');
    }

    public function mopyCategoriesAction()
    {
        $helper = Mage::helper('vs7_mopycap');

        $fromIds = $this->getRequest()->getParam('from_ids');
        $toId = $this->getRequest()->getParam('to_id');
        $mode = $this->getRequest()->getParam('mopy');

        try {
            if (empty($fromIds) || empty($toId)) {
                throw new Exception('Missing data');
            }

            if (!preg_match('/^\d+$/', $toId)) {
                throw new Exception('Incorrect Id');
            } else {
                $toId = (int)$toId;
            }

            if (preg_match('/^(\d+\,)+\d+$/', $fromIds)) {
                $delimeter = ',';
            } elseif (preg_match('/^(\d+\s)+\d+$/', $fromIds)) {
                $delimeter = ' ';
            } elseif (preg_match('/^\d+$/', $fromIds)) {
                $fromIds = array((int)$fromIds);
            } else {
                throw new Exception('Incorrect Id');
            }
            if (isset($delimeter)) {
                $fromIds = explode($delimeter, $fromIds);
            }

            $category = Mage::getModel('catalog/category')->load($toId);
            $parentPathIds = explode('/', $category->getPath());
            if ($category->getId() < 1) {
                throw new Exception('New parent category doesn`t exist');
            }

            foreach ($fromIds as $fromId) {
                $category = Mage::getModel('catalog/category')->load($fromId);
                if ($category->getId() > 0) {
                    if ($mode == 'move') {
                        if (in_array($fromId, $parentPathIds) || $fromId == $toId) {
                            throw new Exception('Can`t move/copy to self');
                        }
                        $category->move($toId, null);
                    } else if ($mode == 'copy') {
                        //TODO
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addWarning('Category with ID: ' . $fromId . ' doesn`t exist');
                }
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addWarning($helper->__($e->getMessage()));
            $this->_redirect('*/*/categories');
            return;
        }

        if ($mode == 'move') {
            Mage::getSingleton('adminhtml/session')->addNotice($helper->__('Successfully moved!'));
        } else if ($mode == 'copy') {
            Mage::getSingleton('adminhtml/session')->addNotice($helper->__('Successfully copied!'));
        }

        $this->_redirect('*/*/categories');
    }

    public function chooserCategoriesAction()
    {
        $request = $this->getRequest();
        $ids = $request->getParam('selected', array());
        $check = $request->getParam('check');
        if ($check == 1) {
            $ids = Mage::getResourceModel('catalog/category_collection')->getAllIds();
        } elseif ($check == 2) {
            $ids = array();
        } else {
            if (is_array($ids)) {
                foreach ($ids as $key => &$id) {
                    $id = (int)$id;
                    if ($id <= 0) {
                        unset($ids[$key]);
                    }
                }

                $ids = array_unique($ids);
            } else {
                $ids = array();
            }
        }

        $block = $this->getLayout()
            ->createBlock(
                'vs7_mopycap/adminhtml_catalog_category_tree',
                'categories',
                array('js_form_object' => $request->getParam('form'))
            )
            ->setCategoryIds($ids);
        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    public function categoriesJsonAction()
    {
        if ($categoryId = (int)$this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $categoryId);

            if (!$category = $this->_initCategory()) {
                return;
            }
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('adminhtml/catalog_category_tree')
                    ->getTreeJson($category)
            );
        }
    }

    protected function _initCategory()
    {
        $categoryId = (int)$this->getRequest()->getParam('id', false);
        $storeId = (int)$this->getRequest()->getParam('store');

        $category = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    $this->_redirect('*/*/', array('_current' => true, 'id' => null));
                    return false;
                }
            }
        }

        Mage::register('category', $category);
        Mage::register('current_category', $category);

        return $category;
    }
}