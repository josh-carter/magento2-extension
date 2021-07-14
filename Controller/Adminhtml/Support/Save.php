<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Support;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use RuntimeException;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Api\Data\SetupInterface;
use Straker\EasyTranslationPlatform\Logger\Logger;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Backend\App\Action\Context;

class Save extends Action
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
                $oSupport = $this->_strakerAPI->callSupport($data);

                if (property_exists($oSupport, 'success')) {
                    if ($oSupport->success) {
                        $this->messageManager->addSuccessMessage(
                            __('Your support request was submitted successfully.')
                        );
                        $resultRedirect->setPath('*/Jobs/index/');
                        return $resultRedirect;
                    } else {
                        $this->messageManager->addError(__('Your support request could not be submitted'));
                        $this->_logger->error('error'.__FILE__.' '.__LINE__.$oSupport->message, []);
                        $this->_strakerAPI->_callStrakerBugLog(
                            __FILE__ . ' ' . __METHOD__ . ' Your support request could not be submitted',
                            $oSupport->message
                        );
                        $resultRedirect->setPath('*/*/index/', $data);
                    }
                } elseif (property_exists($oSupport, 'message')) {
                    if ($oSupport->message === 'Authentication failed') {
                        $this->messageManager->addErrorMessage(
                            __('Since you have not registered an account, the support request could not be submitted.')
                        );
                    } else {
                        $this->messageManager->addErrorMessage(__($oSupport->message));
                    }
                    $resultRedirect->setPath('*/*/index/');
                }
            } catch (RuntimeException $e) {
                $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
                $this->_strakerAPI->_callStrakerBugLog(
                    __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                    $e->__toString()
                );
                $this->messageManager->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_logger->error('error'.__FILE__.' '.__LINE__, [$e]);
                $this->_strakerAPI->_callStrakerBugLog(
                    __FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(),
                    $e->__toString()
                );
                $this->messageManager->addException($e, __('Something went wrong while saving your support request.'));
            }

            $resultRedirect->setPath('*/*/index/');
        }

        return $resultRedirect;
    }
}
