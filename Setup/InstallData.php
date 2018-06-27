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

        foreach (Model\JobStatus::getJobStatus() as $status) {
            $data[] = ['status_name' => $status ];
        }

        $setup->getConnection()
            ->insertMultiple($setup->getTable(Model\JobStatus::ENTITY), $data);


        $data = [];

        foreach (Model\JobType::getJobTypes() as $type) {
            $data[] = ['type_name' => $type ];
        }

        $setup->getConnection()
            ->insertMultiple($setup->getTable(Model\JobType::ENTITY), $data);

        $setup->endSetup();
    }
}
