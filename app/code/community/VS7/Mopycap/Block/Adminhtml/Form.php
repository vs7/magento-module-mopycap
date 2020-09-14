<?php

class VS7_Mopycap_Block_Adminhtml_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id' => $this->getFormId(),
                'action' => $this->getData('action'),
                'method' => 'post',
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $helper = Mage::helper('vs7_mopycap');
        $fieldset = $form->addFieldset('display', array(
            'legend' => $helper->__('Enter category IDs')
        ));


        $fieldset->addField('from_ids', 'text', array(
            'label' => $helper->__('Categories From'),
            'name' => 'from_ids',
            'after_element_html' => '<a id="category_link" href="javascript:void(0)" onclick="toggleCategories()"><img src="' . $this->getSkinUrl('images/rule_chooser_trigger.gif') . '" alt="" class="v-middle rule-chooser-trigger" title="Select Categories"></a>
                <div id="categories_check" style="display:none">
                    <a href="javascript:toggleCategories(1)">Check All</a> / <a href="javascript:toggleCategories(2)">Uncheck All</a>
                </div>
                <div id="categories_select" style="display:none"></div>
                    <script type="text/javascript">
                    function toggleCategories(check){
                        if($("categories_select").style.display == "none" || (check ==1) || (check == 2)){
                            $("categories_check").style.display ="";
                            var url = "' . $this->getUrl('adminhtml/mopycap/chooserCategories') . '";
                            if(check == 1){
                                $("from_ids").value = $("category_all_ids").value;
                            }else if(check == 2){
                                $("from_ids").value = "";
                            }
                            var params = $("from_ids").value.split(", ");
                            var parameters = {"form_key": FORM_KEY,"selected[]":params };
                            var request = new Ajax.Request(url,
                                {
                                    evalScripts: true,
                                    parameters: parameters,
                                    onComplete:function(transport){
                                        $("categories_check").update(transport.responseText);
                                        $("categories_check").style.display = "block";
                                    }
                                }
                            );
                        }else{
                              $("categories_select").style.display = "none";
                              $("categories_check").style.display ="none";
                        }
                    };
		            </script>
            '
        ));

        $fieldset->addField('to_id', 'text', array(
            'name' => 'to_id',
            'label' => $helper->__('Categories To'),
        ));

        $fieldset->addField('mopy', 'radios', array(
            'name' => 'mopy',
            'value' => 'copy',
            'values' => array(
                array('value' => 'copy', 'label' => $helper->__('Copy')),
                array('value' => 'move', 'label' => $helper->__('Move')),
            )
        ));

        $fieldset->addField('submit', 'submit', array(
            'name' => 'submit',
            'class' => 'form-button'
        ))->setValue($helper->__('Process'));

        if (Mage::registry('vs7_mopycap')) {
            $form->setValues(Mage::registry('vs7_mopycap')->getData());
        }

        return parent::_prepareForm();
    }
}