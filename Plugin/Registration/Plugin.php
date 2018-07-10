<?php

namespace Straker\EasyTranslationPlatform\Plugin\Registration;

use Magento\Backend\App\AbstractAction;
use Magento\Framework\Controller\Result\RedirectFactory;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class Plugin
{
    private $_redirectFactory;
    private $_configHelper;
    private $_url;

    public function __construct(
        ConfigHelper $configHelper,
        UrlInterface $url,
        RedirectFactory $redirectFactory
    ) {
        $this->_configHelper = $configHelper;
        $this->_url = $url;
        $this->_redirectFactory = $redirectFactory;
    }

    public function aroundDispatch(
        AbstractAction $subject,
        callable $proceed,
        RequestInterface $request
    ) {
        if (!$this->_configHelper->getAccessToken()) {
            $resultRedirect = $this->_redirectFactory->create();
            $resultRedirect->setUrl($this->_url->getUrl("*/setup_registration/index/"));
            return $resultRedirect;
        }

        if (empty($this->_configHelper->getDefaultAttributes())) {
            $resultRedirect = $this->_redirectFactory->create();
            $resultRedirect->setUrl($this->_url->getUrl("*/setup_productattributes/index/"));
            return $resultRedirect;
        }

        return $proceed($request);
    }
}
