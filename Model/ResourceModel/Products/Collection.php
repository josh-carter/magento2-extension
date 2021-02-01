<?php
namespace Straker\EasyTranslationPlatform\Model\ResourceModel\Products;

use Magento\Framework\DB\Select;
use Magento\Framework\DB\SelectFactory;
use Straker\EasyTranslationPlatform\Model\JobType;

/**
 * Factory class for @see \Magento\Catalog\Model\ResourceModel\Product\Collection
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    protected $targetStoreId;

    public function isTranslated($targetStoreId)
    {
        $this->targetStoreId = $targetStoreId;

        $isTranslatedSelect = $this->_buildIsTranslatedSelect();

        $this->getSelect()
            ->joinLeft(
                ['stTrans' => $isTranslatedSelect],
                'e.entity_id=stTrans.entity_id',
                ['IF(is_translated IS NULL, 0, 1) AS is_translated']
            );

        return $this;
    }

    /**
     * Build a Select which query all the translated product from straker tables
     *
     * @return Select
     */
    private function _buildIsTranslatedSelect()
    {

        $strakerJobs = $this->_resource->getTableName('straker_job');
        $strakerTrans = $this->_resource->getTableName('straker_attribute_translation');

        $select = clone $this->getSelect();
        $select->reset();

        $select->from(
            ['stJob' => $strakerJobs],
            []
        )->join(
            ['stTrans' => $strakerTrans],
            'stTrans.job_id=stJob.job_id and stJob.target_store_id='
            . (empty($this->targetStoreId) ? 0 : $this->targetStoreId)
            . ' and stJob.job_type_id='. JobType::JOB_TYPE_PRODUCT,
            ['entity_id', 'MAX(IF((stTrans.is_published AND stJob.job_id) IS NULL, 0, 1)) as is_translated']
        )->group('stTrans.entity_id');

        return $select;
    }

    public function getSelectCountSql()
    {
        $parentSelect = parent::getSelectCountSql();

        $isTranslatedSelect = $this->_buildIsTranslatedSelect();

        $parentSelect
            ->joinLeft(
                ['stTrans' => $isTranslatedSelect],
                'e.entity_id=stTrans.entity_id',
                []
            )->reset(Select::COLUMNS)
            ->columns('COUNT(DISTINCT e.entity_id)');

        return $parentSelect;
    }

    public function getAllIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('e.' . $this->getEntity()->getIdFieldName());
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();
        $isTranslatedSelect = $this->_buildIsTranslatedSelect();

        $idsSelect->joinLeft(
            ['stTrans' => $isTranslatedSelect],
            'e.entity_id=stTrans.entity_id',
            []
        );
        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }
}
