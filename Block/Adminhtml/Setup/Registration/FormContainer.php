<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Setup\Registration;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class FormContainer extends \Magento\Backend\Block\Widget\Form\Container
{
    protected $_mode = 'form';

    public function __construct(
        Context $context,
        Registry $registry
    ) {
    
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->_blockGroup = 'Straker_EasyTranslationPlatform';
        $this->_controller = 'adminhtml_setup_registration';
        $this->buttonList->update('save', 'label', __('Next'));
        $this->buttonList->remove('reset');
        $this->buttonList->remove('back');
    }

    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
