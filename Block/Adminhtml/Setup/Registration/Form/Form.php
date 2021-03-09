<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Setup\Registration\Form;

use Magento\Backend\Block\Widget\Form\Generic;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;

class Form extends Generic
{
    protected $_Registry;
    protected $_strakerAPIInterface;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        StrakerAPIInterface $strakerAPIInterface,
        array $data = []
    ) {
        $this->_strakerAPIInterface = $strakerAPIInterface;
        $this->_formFactory = $formFactory;
        $this->_Registry = $registry;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('item_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => ' ', 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'first_name',
            'text',
            [
                'name' => 'first_name',
                'label' => __('First Name'),
                'title' => __('first name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'last_name',
            'text',
            [
                'name' => 'last_name',
                'label' => __('Last Name'),
                'title' => __('last name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'email',
            'text',
            [
                'name' => 'email',
                'label' => __('Email'),
                'title' => __('Email'),
                'required' => true,
                'class'=>'validate-email'
            ]
        );

        $fieldset->addField(
            'country',
            'select',
            [
                'label' => __('Country'),
                'title' => __('Country'),
                'name' => 'country',
                'required' => true,
                'options' => $this->_getOptions()
            ]
        );

        $fieldset->addField(
            'company_name',
            'text',
            [
                'name' => 'company_name',
                'label' => __('Company Name'),
                'title' => __('Company Name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'company_size',
            'select',
            [
                'name' => 'company_size',
                'label' => __('Company Size'),
                'title' => __('Company Size'),
                'required' => true,
                'options' => $this->_getCompanySizeOptions()
            ]
        );

        $fieldset->addField(
            'phone_number',
            'text',
            [
                'name' => 'phone_number',
                'label' => __('Phone Number'),
                'title' => __('Phone Number')
            ]
        );

        $fieldset->addField(
            'url',
            'text',
            [
                'name' => 'url',
                'label' => __('Website Url'),
                'title' => __('Website Url'),
                'class'=>'validate-clean-url'
            ]
        );

        $fieldset->addField(
            'terms',
            'checkbox',
            [
                'label' => '',
                'name' => 'terms',
                'after_element_html' => '<span>&nbsp;&nbsp;'
                    . __(
                        'I have read and agreed to the %1 terms and conditions %2',
                        '</span><a href="https://www.strakertranslations.com/terms-conditions/" target="_blank">',
                        '</a>'
                    ),
                'class'=>'checkbox required'
            ]
        );

        $fieldset->addField(
            'from_action',
            'hidden',
            [
                'name' => 'from_action',
                'value' => $this->_request->getParam('from')
            ]
        );

        $form->setUseContainer(true);

//        $form->setValues($this->_session->getData('form_data'));

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getOptions()
    {
        $aCountries = [];
        $aCountries[null] = 'Select a country';
        foreach ($this->_strakerAPIInterface->getCountries() as $key => $value) {
            $aCountries[$value->code] = $value->name;
        }

        return $aCountries;
    }

    private function _getCompanySizeOptions()
    {
        return [
            ''          => '-',
            '1 - 10'    => '1 - 10',
            '11 - 50'   => '11 - 50',
            '51 - 250'  => '51 - 250',
            '250+'      => '250+'
        ];
    }
}
