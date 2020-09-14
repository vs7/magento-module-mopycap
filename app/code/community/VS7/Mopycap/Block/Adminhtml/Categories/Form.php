<?php

class VS7_Mopycap_Block_Adminhtml_Categories_Form extends VS7_Mopycap_Block_Adminhtml_Form
{
    protected function _prepareForm()
    {
        $this->setFormId('move_categories_form');
        return parent::_prepareForm();
    }
}