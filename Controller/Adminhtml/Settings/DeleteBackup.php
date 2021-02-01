<?php

namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Backup\Model\BackupFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\MaintenanceMode;
use \Magento\Framework\Controller\Result\Json;

class DeleteBackup extends Action
{
    /**
     * @var Json
     */
    protected $_jsonResult;
    /**
     * @var BackupFactory
     */
    protected $_backupModelFactory;

    /**
     * @var MaintenanceMode
     */
    protected $maintenanceMode;

    public function __construct(
        Context $context,
        BackupFactory $backupModelFactory,
        Json $jsonResult
    ) {
        $this->_backupModelFactory = $backupModelFactory;
        $this->_jsonResult = $jsonResult;
        parent::__construct($context);
    }


    public function execute()
    {
        $result = ['Success' => false, 'Message' => __('We can\'t delete one or more backups.')];
        $id = $this->getRequest()->getParam('id');

        if (!empty($id)) {
            try {
                list($time, $type) = explode('_', $id);
                $backupModel = $this->_backupModelFactory->create($time, $type)->deleteFile();

                if (!$backupModel->exists()) {
                    $result['Success'] = true;
                    $result['Message'] = __('successful');
                }

                $this->messageManager->addSuccessMessage(__('You deleted the selected backup(s).'));

            } catch (\Exception $e) {
                $result['Success'] = false;
                $result['Message'] = __($e->getMessage());
                $this->messageManager->addErrorMessage($result['Message']);
            }

        }

        return $this->_jsonResult->setData($result);
    }
}
