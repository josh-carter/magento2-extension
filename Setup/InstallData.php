<?php
namespace Straker\EasyTranslationPlatform\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Straker\EasyTranslationPlatform\Model;

class InstallData implements InstallDataInterface
{
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $data = [];

        foreach (Model\JobStatus::getJobStatus() as $id => $status) {
            $data[] = ['status_id' => ++$id, 'status_name' => $status ];
        }

        $setup->getConnection()
            ->insertOnDuplicate($setup->getTable(Model\JobStatus::ENTITY), $data, ['status_name']);

        $data = [];

        foreach (Model\JobType::getJobTypes() as $id => $type) {
            $data[] = ['type_id' => ++$id, 'type_name' => $type ];
        }

        $setup->getConnection()
            ->insertOnDuplicate($setup->getTable(Model\JobType::ENTITY), $data, ['type_name']);

        $setup->endSetup();
    }
}
