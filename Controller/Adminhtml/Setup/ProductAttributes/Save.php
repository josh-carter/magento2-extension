<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Setup\ProductAttributes;

use Exception;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use RuntimeException;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Api\Data\SetupInterface;
use Straker\EasyTranslationPlatform\Logger\Logger;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Backend\App\Action\Context;

class Save extends \Magento\Backend\App\Action
{

    protected $_config;
    protected $_reinitConfig;
    protected $_strakerAPI;
    protected $_setup;
    protected $_logger;

    public function __construct(
        Context $context,
        Config $config,
        ReinitableConfigInterface $reinitableConfigInterface,
        StrakerAPIInterface $strakerAPIInterface,
        SetupInterface $setupInterface,
        Logger $logger
    ) {
    
        $this->_config = $config;
        $this->_reinitConfig = $reinitableConfigInterface;
        $this->_strakerAPI = $strakerAPIInterface;
        $this->_setup = $setupInterface;
        $this->_logger = $logger;

        parent::__construct($context);
    }


    public function execute()
    {

        $data = $this->getRequest()->getParams();

        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            try {
                $attributes = $this->sortData($data);

                $this->_setup->saveAttributes($attributes);

                $resultRedirect->setPath('*/setup_testingstoreview/index/');

                return $resultRedirect;
            } catch (LocalizedException $e) {
                $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
                $this->_strakerAPI->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->messageManager->addError('There was an error saving Product Attributes');
            } catch (RuntimeException $e) {
                $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
                $this->_strakerAPI->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->messageManager->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
                $this->_strakerAPI->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
                $this->messageManager->addException($e, __('Something went wrong while saving the product attributes.'));
            }

            $resultRedirect->setPath('*/jobs/index/');
        }

        return $resultRedirect;
    }

    protected function sortData($data)
    {
        $attributes = [];

        if (!empty($data['custom'])) {
            asort($data['custom']);

            $attributes['custom'] = implode(",", $data['custom']);
        }

        if (!empty($data['default'])) {
            asort($data['default']);

            $attributes['default'] = implode(",", $data['default']);
        }

        if (!empty($data['custom'])) {
            asort($data['custom']);

            $attributes['custom'] = implode(",", $data['custom']);
        }

        if (!empty($data['category'])) {
            asort($data['category']);

            $attributes['category'] = implode(",", $data['category']);
        }

        return $attributes;
    }
}
