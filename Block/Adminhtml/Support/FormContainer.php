<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Support;

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
        $this->_controller = 'adminhtml_support';

        $this->buttonList->update('save', 'label', __('Send'));
        $this->buttonList->remove('reset');
        $this->buttonList->remove('back');
    }

    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
