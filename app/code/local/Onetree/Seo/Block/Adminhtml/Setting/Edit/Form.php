<?php
/**
 * Examples
 *
 * PHP Version 5
 *
 * @category  Examples
 * @package   Examples_AdminGridAndForm
 * @author    Mike Whitby <me@mikewhitby.co.uk>
 * @copyright Copyright (c) 2012 Mike Whitby (http://www.mikewhitby.co.uk)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      N/A
 */

/**
 * Thing form
 *
 * This class gets instantiated by it's container, which is of type
 * {@link Examples_AdminGridAndForm_Block_Adminhtml_Thing_Edit}. This class is
 * responsible for creating the actual HTML form, with all the fieldsets and
 * inputs etc, so this is the actual <form></form> and everything in it
 *
 * What might not be obvious is that this form is used for both addition and
 * editing of whatever entity type it is you are working with. You won't get to
 * see this though as this relies on the controller registering certain data
 * so this form will act as though it is adding a new entity all the time,
 * whereas in reality you would code the controller to register some data to
 * allow it to work as an 'edit', rather than a 'new' form.
 *
 * @category Examples
 * @package  Examples_AdminGridAndForm
 * @author   Mike Whitby <me@mikewhitby.co.uk>
 */
class Onetree_Seo_Block_Adminhtml_Setting_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('seo/information')->getCollection();
        $this->setCollection($collection);
//  return parent::_prepareCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }
    /**
     * Prepare form before rendering HTML
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {


        $eId = (int)$this->getRequest()->getParam('id');
        $model = Mage::getModel('seo/information')->load($eId);

        $seo_name = $model->getName();
        $seo_information = $model->getSeoInformation();
        $short_description = $model->getShortDescription();
        $active = $model->getActive();

        # create the form with the essential information, such as DOM ID, action
        # attribute, method and the enc type (this is needed if you have image
        # inputs in your form, and doesn't hurt to use otherwise)

        $form = new Varien_Data_Form(
            array(
                'id'      => 'edit_form',
                'action'  => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method'  => 'post',
                'enctype' => 'multipart/form-data'
            )
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        # you can add fields direct to the form, without a fieldset
        $form->addField(
            'fake_note',
            'note',
            array(
                'text' => '<ul class="messages"><li class="notice-msg"><ul><li>'
                    .  Mage::helper('onetree_seo')->__('This form is edit seo, so the data in the grid')
                    . '</li></ul></li></ul>',
            )
        );

        # add a fieldset, this returns a Varien_Data_Form_Element_Fieldset object
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array(
                'legend' => Mage::helper('onetree_seo')->__('General Information'),
            )
        );
        # now add fields on to the fieldset object, for more detailed info
        # see https://makandracards.com/magento/12737-admin-form-field-types
        $fieldset->addField(
            'name', # the input id
            'text', # the type
            array(
                'label'    => Mage::helper('onetree_seo')->__('Name'),
                'class'    => 'required-entry',
                'required' => true,
                'name'     => 'name',
                'value' => $seo_name
            )
        );
        $fieldset->addField(
            'seo_information',
            'textarea',
            array(
                'label' => Mage::helper('onetree_seo')->__('Information Seo'),
                'name'  => 'seo_information',
                'value' => $seo_information
            )
        );
        $fieldset->addField(
            'short_description',
            'textarea',
            array(
                'label' => Mage::helper('onetree_seo')->__('Short Description'),
                'name'  => 'short_description',
                'value' => $short_description
            )

        );
        # we can use multiple fieldsets
        $fieldset = $form->addFieldset(
            'active_fieldset',
            array(
                'legend' => Mage::helper('onetree_seo')->__('Active'),
            )
        );
        $fieldset->addField(
            'active_note',
            'note',
            array(
                'text' => Mage::helper('onetree_seo')->__('You can enable/disable seo for this meta'),
            )
        );
        $fieldset->addField(
            'active',
            'text',
            array(
                'label'    => Mage::helper('onetree_seo')->__('Active'),
                'class'    => 'required-entry',
                'required' => true,
                'name'     => 'active',
                'value' => $active
            )
        );

        return parent::_prepareForm();
    }
}
