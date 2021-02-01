<?php
namespace Straker\EasyTranslationPlatform\Setup;

use Magento\Eav\Model\EavCustomAttributeTypeLocator;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Store\Model\Store;
use Straker\EasyTranslationPlatform\Model;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        //START straker_job_type setup
        if (!$installer->tableExists(Model\JobType::ENTITY)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable(Model\JobType::ENTITY)
            )->addColumn(
                'type_id',
                Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Entity ID'
            )->addColumn(
                'type_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Name'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Modification Time'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Is Active'
            )->addIndex(
                $installer->getIdxName(Model\JobType::ENTITY, ['type_id'], AdapterInterface::INDEX_TYPE_UNIQUE),
                'type_id',
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );
            $installer->getConnection()->createTable($table);
        }
        //END   table setup

        //START straker_job_status setup
        if (!$installer->tableExists(Model\JobStatus::ENTITY)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable(Model\JobStatus::ENTITY)
            )->addColumn(
                'status_id',
                Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Entity ID'
            )->addColumn(
                'status_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Name'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Modification Time'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Is Active'
            )->addIndex(
                $installer->getIdxName(Model\JobStatus::ENTITY, ['status_id'], AdapterInterface::INDEX_TYPE_UNIQUE),
                'status_id',
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );
            $installer->getConnection()->createTable($table);
        }
        //END   table setup

        //setup straker_job
        if (!$installer->tableExists(Model\Job::ENTITY)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable(Model\Job::ENTITY)
            )->addColumn(
                'job_id',
                Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Job Id'
            )->addColumn(
                'job_type_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Job Type Id'
            )->addColumn(
                'source_store_id',
                Table::TYPE_SMALLINT,
                5,
                ['nullable' => false],
                'Source Store Id'
            )->addColumn(
                'target_store_id',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false],
                'Target Store Id'
            )->addColumn(
                'job_number',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Job Number'
            )->addColumn(
                'sl',
                Table::TYPE_TEXT,
                100,
                ['nullable' => false],
                'Source Language'
            )->addColumn(
                'tl',
                Table::TYPE_TEXT,
                100,
                ['nullable' => false],
                'Target Language'
            )->addColumn(
                'job_key',
                Table::TYPE_TEXT,
                100,
                ['nullable' => false],
                'Job Key'
            )->addColumn(
                'job_status_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Job Status Id'
            )->addColumn(
                'source_file',
                Table::TYPE_TEXT,
                255,
                [],
                'Source File'
            )->addColumn(
                'translated_file',
                Table::TYPE_TEXT,
                255,
                [],
                'Translated File'
            )->addColumn(
                'download_url',
                Table::TYPE_TEXT,
                255,
                [],
                'Download Url'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Modification Time'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Is Active'
            )->addColumn(
                'is_test_job',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => '0'],
                'Is Test Job'
            )->addIndex(
                $installer->getIdxName(Model\Job::ENTITY, ['job_id'], AdapterInterface::INDEX_TYPE_UNIQUE),
                'job_id',
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->setComment(
                'Job table'
            )->addForeignKey(
                $installer->getFkName(Model\Job::ENTITY, 'target_store_id', Store::ENTITY, 'store_id'),
                'target_store_id',
                $installer->getTable(Store::ENTITY),
                'store_id',
                Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName(Model\Job::ENTITY, 'job_type_id', Model\JobType::ENTITY, 'type_id'),
                'job_type_id',
                $installer->getTable(Model\JobType::ENTITY),
                'type_id',
                Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName(Model\Job::ENTITY, 'job_status_id', Model\JobStatus::ENTITY, 'status_id'),
                'job_status_id',
                $installer->getTable(Model\JobStatus::ENTITY),
                'status_id',
                Table::ACTION_CASCADE
            );

            $installer->getConnection()->createTable($table);
        }

        //START straker_attribute_translation setup
        if (!$installer->tableExists(Model\AttributeTranslation::ENTITY)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable(Model\AttributeTranslation::ENTITY)
            )->addColumn(
                'attribute_translation_id',
                Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Translation ID'
            )->addColumn(
                'job_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Job Id'
            )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity Id'
            )->addColumn(
                'attribute_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Id'
            )->addColumn(
                'has_option',
                Table::TYPE_BOOLEAN,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Option Id'
            )->addColumn(
                'is_label',
                Table::TYPE_BOOLEAN,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Label'
            )->addColumn(
                'original_value',
                Table::TYPE_TEXT,
                Table::MAX_TEXT_SIZE,
                ['nullable' => true],
                'Original Text'
            )->addColumn(
                'translated_value',
                Table::TYPE_TEXT,
                Table::MAX_TEXT_SIZE,
                ['nullable' => true],
                'Translation done by Straker'
            )->addColumn(
                'is_imported',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => true],
                'Is Imported'
            )->addColumn(
                'imported_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Import Time'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Modification Time'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Is Active'
            )->addIndex(
                $installer->getIdxName(
                    Model\AttributeTranslation::ENTITY,
                    ['attribute_translation_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                'attribute_translation_id',
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addForeignKey(
                $installer->getFkName(Model\AttributeTranslation::ENTITY, 'job_id', Model\Job::ENTITY, 'job_id'),
                'job_id',
                $installer->getTable(Model\Job::ENTITY),
                'job_id',
                Table::ACTION_CASCADE
            );

            $installer->getConnection()->createTable($table);
        }
        //END   table setup

        //START straker_attribute_translation setup
        if (!$installer->tableExists(Model\AttributeOptionTranslation::ENTITY)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable(Model\AttributeOptionTranslation::ENTITY)
            )->addColumn(
                'attribute_option_translation_id',
                Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Attribute Option Translation ID'
            )->addColumn(
                'attribute_translation_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Id'
            )->addColumn(
                'option_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Option Id'
            )->addColumn(
                'original_value',
                Table::TYPE_TEXT,
                Table::MAX_TEXT_SIZE,
                ['nullable' => true],
                'Original Text'
            )->addColumn(
                'translated_value',
                Table::TYPE_TEXT,
                Table::MAX_TEXT_SIZE,
                ['nullable' => true],
                'Translation done by Straker'
            )->addColumn(
                'is_imported',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => true],
                'Is Imported'
            )->addColumn(
                'imported_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Import Time'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Modification Time'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Is Active'
            )->addIndex(
                $installer->getIdxName(
                    Model\AttributeOptionTranslation::ENTITY,
                    ['attribute_option_translation_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                'attribute_option_translation_id',
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addForeignKey(
                $installer->getFkName(
                    Model\AttributeOptionTranslation::ENTITY,
                    'attribute_translation_id',
                    Model\AttributeTranslation::ENTITY,
                    'attribute_translation_id'
                ),
                'attribute_translation_id',
                $installer->getTable(Model\AttributeTranslation::ENTITY),
                'attribute_translation_id',
                Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName(
                    Model\AttributeOptionTranslation::ENTITY,
                    'option_id',
                    'eav_attribute_option',
                    'option_id'
                ),
                'option_id',
                $installer->getTable('eav_attribute_option'),
                'option_id',
                Table::ACTION_CASCADE
            );
            $installer->getConnection()->createTable($table);
        }
        //END   table setup

        $installer->endSetup();
    }
}
