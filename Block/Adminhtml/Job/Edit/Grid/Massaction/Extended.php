<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Grid\Massaction;


class Extended extends \Magento\Backend\Block\Widget\Grid\Massaction\Extended {
    public function getGridIdsJson()
    {
//        code in 2.2.x
//        if (!$this->getUseSelectAll()) {
//            return '';
//        }
//
//        /** @var \Magento\Framework\Data\Collection $allIdsCollection */
//        $allIdsCollection = clone $this->getParentBlock()->getCollection();
//
//        if ($this->getMassactionIdField()) {
//            $massActionIdField = $this->getMassactionIdField();
//        } else {
//            $massActionIdField = $this->getParentBlock()->getMassactionIdField();
//        }
//
//        $gridIds = $allIdsCollection->setPageSize(0)->getColumnValues($massActionIdField);
//
//        if (!empty($gridIds)) {
//            return join(",", $gridIds);
//        }
        if (!$this->getUseSelectAll()) {
            return '';
        }

        /** @var \Magento\Framework\Data\Collection $allIdsCollection */
        $allIdsCollection = clone $this->getParentBlock()->getCollection();
        $gridIds = $allIdsCollection->clear()->setPageSize(0)->getAllIds();

        if (!empty($gridIds)) {
            return join(",", $gridIds);
        }

        return '';
    }
}