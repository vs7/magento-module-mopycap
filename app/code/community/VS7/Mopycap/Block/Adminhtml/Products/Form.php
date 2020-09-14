<?php

class VS7_Mopycap_Block_Adminhtml_Products_Form extends VS7_Mopycap_Block_Adminhtml_Form
{
    protected function _prepareForm()
    {
        $this->setFormId('move_products_form');
        return parent::_prepareForm();
    }
}