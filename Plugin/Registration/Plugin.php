<?php

namespace Straker\EasyTranslationPlatform\Plugin\Registration;

use Magento\Backend\App\AbstractAction;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Api\Data\SetupInterface;

class Plugin
{
    private $_redirectFactory;
    private $_configHelper;
    private $_setupApi;
    private $_url;

    public function __construct(
        ConfigHelper $configHelper,
        UrlInterface $url,
        RedirectFactory $redirectFactory,
        SetupInterface $setupInterface
    ) {
        $this->_configHelper = $configHelper;
        $this->_url = $url;
        $this->_redirectFactory = $redirectFactory;
        $this->_setupApi = $setupInterface;
    }

    public function aroundDispatch(
        AbstractAction $subject,
        callable $proceed,
        RequestInterface $request
    ) {
        $actionName = empty($request->getActionName()) ? 'new' : $request->getActionName();

        if (!$this->_configHelper->getAccessToken()) {
            $resultRedirect = $this->_redirectFactory->create();
            $resultRedirect->setPath('*/setup_registration/index/', array('from' => $actionName));
            return $resultRedirect;
        }

        if ($actionName === 'new') {
            if (empty($this->_configHelper->getDefaultAttributes())) {
                $resultRedirect = $this->_redirectFactory->create();
                $resultRedirect->setPath("*/setup_productattributes/index/", array('from' => $actionName));
                return $resultRedirect;
            }

            if($this->_configHelper->isSandboxMode() && !$this->_setupApi->isTestingStoreViewExist()->getId()){
                $resultRedirect = $this->_redirectFactory->create();
                $resultRedirect->setPath("*/setup_testingstoreview/index/", array('from' => $actionName));
                return $resultRedirect;
            }
        }

        return $proceed($request);
    }
}
