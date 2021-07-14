<?php
/**
 * Created by PhpStorm.
 * User: rakeshmistry
 * Date: 17/11/16
 * Time: 11:56 AM
 */

namespace Straker\EasyTranslationPlatform\Model;

use Magento\Framework\DB\Select;

class PageCollection extends \Magento\Cms\Model\ResourceModel\Page\Collection
{

    public function isTranslated($target_store_id = 1)
    {
        $strakerJobs = $this->_resource->getTable('straker_job');
        $strakerTrans = $this->_resource->getTable('straker_attribute_translation');

        $this->getSelect()
            ->reset(Select::COLUMNS)
            ->columns(
                [
                    'main_table.page_id',
                    'main_table.title',
                    'MAX(IF((stTrans.is_published AND stJob.job_id) IS NULL, 0, 1)) AS is_translated'
                ]
            )->joinLeft(
                ['stTrans' => $strakerTrans],
                'main_table.page_id=stTrans.entity_id',
                []
            )->joinLeft(
                ['stJob' => $strakerJobs],
                'stTrans.job_id=stJob.job_id and stJob.target_store_id='
                . $target_store_id
                . ' and stJob.job_type_id='. JobType::JOB_TYPE_PAGE,
                []
            );

        return $this;
    }

    public function getSelectCountSql()
    {
        $this->_renderFilters();
        $countSelect = clone $this->getSelect();

        $select = clone $this->getSelect();
        $select
            ->reset(Select::ORDER)
            ->reset(Select::LIMIT_COUNT)
            ->reset(Select::LIMIT_OFFSET);

        $countSelect
            ->reset()
            ->from(['s' => $select])
            ->reset(Select::COLUMNS)
            ->columns('COUNT(DISTINCT page_id)');

        return $countSelect;
    }

    public function getAllIds()
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

        $idsSelect->columns($this->getResource()->getIdFieldName(), 'main_table');
        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }
}
