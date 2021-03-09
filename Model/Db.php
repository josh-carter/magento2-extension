<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Straker\EasyTranslationPlatform\Model;

/**
 * Class to work with database backups
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Db extends \Magento\Framework\Backup\Db
{
    /**
     * Implements Rollback functionality for Db
     *
     * @return bool
     */
    public function rollback()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $this->_lastOperationSucceed = false;

        $archiveManager = new \Magento\Framework\Archive();
        $source = $archiveManager->unpack($this->getBackupPath(), $this->getBackupsDir());

        $file = new \Magento\Framework\Backup\Filesystem\Iterator\File($source);
        $resource = $this->getResourceModel();

        foreach ($file as $statement) {
            $resource->runCommand($statement);
        }

        $this->_lastOperationSucceed = true;

        return true;
    }
}
