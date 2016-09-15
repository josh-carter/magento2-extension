<?php

namespace Straker\EasyTranslationPlatform\Plugin\Registration;

use Straker\EasyTranslationPlatform\Controller\Adminhtml\Jobs\NewAction;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Backend\Model\View\Factory;
use Closure;


class Plugin
{
    public function __construct(
        ConfigHelper $configHelper,
        UrlInterface $url
    ) {
        $this->_configHelper = $configHelper;
        $this->_url = $url;
    }

    public function aroundDispatch(
        NewAction $subject,
        Closure $proceed,
        RequestInterface $request
    )
    {

        $url = false;

        (count($this->_configHelper->getDefaultAttributes()) < 2 ) ? $url = $this->_url->getUrl("*/Setup_productattributes/index/") : false;

        (!$this->_configHelper->getAccessToken()) ? $url = $this->_url->getUrl("*/setup_registration/index/") : false;


        if($url) {

            $resultRedirect = $subject->resultRedirectFactory->create();

            $resultRedirect->setUrl($url);

            return $resultRedirect;

        }else{

            return $proceed($request);
        }
    }
}