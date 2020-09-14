<?php

class VS7_Mopycap_Block_Adminhtml_Categories extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_removeButton('save');
        $this->_removeButton('reset');
        $this->_blockGroup = 'vs7_mopycap';
        $this->_controller = 'adminhtml';
        $this->_mode = 'categories';
        $this->_headerText = Mage::helper('vs7_mopycap')->__('Move/copy Categories by IDs');
    }

    public function getFormActionUrl()
    {
        if ($this->hasFormActionUrl()) {
            return $this->getData('form_action_url');
        }
        return $this->getUrl('*/mopycap/mopyCategories');
    }
}