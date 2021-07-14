<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Magento\Backup\Helper\Data as BackupHelper;

class RestoreBackup extends Action
{
    protected $_api;
    protected $_jsonFactory;
    protected $_backupHelper;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        StrakerAPIInterface $api,
        BackupHelper $backupHelper
    ) {
        $this->_api = $api;
        $this->_jsonFactory = $jsonFactory;
        $this->_backupHelper = $backupHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $response = $this->_api->dbRestore();
        $this->_backupHelper->invalidateCache();
        $adminSession = $this->_getSession();
        $adminSession->destroy();
        return $this->_jsonFactory->create()->setData($response);
    }
}
