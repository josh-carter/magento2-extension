<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Setup\LanguagePairs;

use Magento\Framework\View\Element\Template;

class FormContainer extends \Magento\Backend\Block\Widget\Container
{
    protected $_objectId = 'id';
    protected $_template = 'Straker_EasyTranslationPlatform::widget/form/container.phtml';

    protected function _construct()
    {
        parent::_construct();
        
        $this->addButton(
            'back',
            [
                'label' => __('Back'),
                'onclick' => 'setLocation(\'' . $this->getBackUrl() . '\')',
                'class' => 'back'
            ],
            -1
        );

        $objId = $this->getRequest()->getParam($this->_objectId);

        if (!empty($objId)) {
            $this->addButton(
                'delete',
                [
                    'label' => __('Delete'),
                    'class' => 'delete',
                    'onclick' => 'deleteConfirm(\'' . __(
                        'Are you sure you want to do this?'
                    ) . '\', \'' . $this->getDeleteUrl() . '\')'
                ]
            );
        }

        $this->addButton(
            'save',
            [
                'label' => __('Next'),
                'class' => 'save primary',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#edit_form']],
                ]
            ],
            1
        );
    }

    protected function _prepareLayout()
    {

        $this->addChild(
            'form',
            \Straker\EasyTranslationPlatform\Block\Adminhtml\Setup\LanguagePairs\Form\Form::class
        )->setTemplate('Straker_EasyTranslationPlatform::setup/languagepairs.phtml');

        return parent::_prepareLayout();
    }

    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/save');
    }

    public function getFormHtml()
    {
        return $this->getChildHtml('form');
    }
}
