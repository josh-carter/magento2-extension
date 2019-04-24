<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\About;

use Magento\Backend\Block\Template\Context;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;

class Index extends \Magento\Backend\Block\Template
{
    const TEMPLATE =  'Straker_EasyTranslationPlatform::about/about.phtml';

    /** @var $_configHelper \Straker\EasyTranslationPlatform\Helper\ConfigHelper */
    private $_configHelper;

    /**
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        array $data = []
    )
    {
        $this->_configHelper = $configHelper;
        $this->setTemplate(self::TEMPLATE);
        parent::__construct($context, $data);
    }

    public function getModuleVersion(){
        return $this->_configHelper->getModuleVersion();
    }
}
