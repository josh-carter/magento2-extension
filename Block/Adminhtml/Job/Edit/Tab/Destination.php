<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;

class Destination extends Generic implements TabInterface
{
    protected $_Registry;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory
    ) {
        $this->_formFactory = $formFactory;
        $this->_Registry = $registry;

        parent::__construct($context, $registry, $formFactory);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('job_');

        $fieldset = $form->addFieldset(
            '',
            []
        );

        $renderer = $this->getLayout()->createBlock(
            \Straker\EasyTranslationPlatform\Block\Adminhtml\Form\Renderer\JobDestination::class
        );

        $fieldset->setRenderer($renderer);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getWebsites()
    {
        return $this->_storeManager->getWebsites();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('1. &nbsp; Select Destination');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Select Destination');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
