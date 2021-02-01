<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Straker\EasyTranslationPlatform\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backup\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Straker\EasyTranslationPlatform\Model\Db;

/*
 * This class preform Magento database rollback. Since restore functionality has been
 * change to call portal server to restore, this class is not used but remains here in
 * case requiring the functionality switch back.
 */

class Rollback extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::backup';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Straker\EasyTranslationPlatform\Model\DbFactory
     */
    protected $_dbFactory;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \Straker\EasyTranslationPlatform\Model\BackupFactory
     */
    protected $_backupModelFactory;

    /**
     * @var \Magento\Framework\App\MaintenanceMode
     */
    protected $maintenanceMode;


    /**
     * Rollback constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Straker\EasyTranslationPlatform\Model\DbFactory $dbFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Straker\EasyTranslationPlatform\Model\BackupFactory $backupModelFactory
     * @param \Magento\Framework\App\MaintenanceMode $maintenanceMode
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Straker\EasyTranslationPlatform\Model\DbFactory $dbFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Straker\EasyTranslationPlatform\Model\BackupFactory $backupModelFactory,
        \Magento\Framework\App\MaintenanceMode $maintenanceMode
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_dbFactory = $dbFactory;
        $this->_fileFactory = $fileFactory;
        $this->_backupModelFactory = $backupModelFactory;
        $this->maintenanceMode = $maintenanceMode;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            return $this->_redirect(
                'adminhtml/system_config/edit',
                ['section' => 'demonstration']
            );
        }

        $dbBackupManager = null;

        /** @var Data $helper */
        $helper = $this->_objectManager->get(Data::class);
        $response = new \Magento\Framework\DataObject();

        try {
            $backup = $this->_backupModelFactory->create(
                $this->getRequest()->getParam('time'),
                $this->getRequest()->getParam('type')
            );

            if (!$backup->getTime() || !$backup->exists()) {
                return $this->_redirect('adminhtml/system_config/edit', ['section' => 'demonstration']);
            }

            if (!$backup->getTime()) {
                throw new \Magento\Framework\Backup\Exception\CantLoadSnapshot(__('Can\'t load snapshot archive'));
            }

            $type = $backup->getType();

            /** @var Db $dbBackupManager */
            $dbBackupManager = $this->_dbFactory->create(
                $type
            )->setBackupExtension(
                $helper->getExtensionByType($type)
            )->setTime(
                $backup->getTime()
            )->setBackupsDir(
                $helper->getBackupsDir()
            )->setName(
                $backup->getName(),
                false
            )->setResourceModel(
                $this->_objectManager->create(\Magento\Backup\Model\ResourceModel\Db::class)
            );

            $this->_coreRegistry->register('backup_manager', $dbBackupManager);
            $helper->invalidateCache();
            $dbBackupManager->rollback();
            $adminSession = $this->_getSession();
            $adminSession->destroy();
            $response->setRedirectUrl($this->getUrl('*'));
        } catch (\Magento\Framework\Backup\Exception\CantLoadSnapshot $e) {
            $errorMsg = __('We can\'t find the backup file.');
        } catch (\Magento\Framework\Backup\Exception\FtpConnectionFailed $e) {
            $errorMsg = __('We can\'t connect to the FTP right now.');
        } catch (\Magento\Framework\Backup\Exception\FtpValidationFailed $e) {
            $errorMsg = __('Failed to validate FTP.');
        } catch (\Magento\Framework\Backup\Exception\NotEnoughPermissions $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage());
            $errorMsg = __('You need more permissions to perform a rollback.');
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage());
            $errorMsg = __('Failed to rollback.');
        }

        if (!empty($errorMsg)) {
            $response->setError($errorMsg);
            $dbBackupManager->setErrorMessage($errorMsg);
        }

        if ($this->getRequest()->getParam('maintenance_mode')) {
            $this->maintenanceMode->set(false);
        }

        $this->getResponse()->representJson($response->toJson());
    }
}
