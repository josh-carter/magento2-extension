<?php

namespace Straker\EasyTranslationPlatform\Model\Config\Source;

class SourceLanguage implements \Magento\Framework\Option\ArrayInterface
{
    protected $_jobCollectionFactory;

    public function __construct(
        \Straker\EasyTranslationPlatform\Model\ResourceModel\Job\CollectionFactory $jobCollectionFactory
    ) {
    
        $this->_jobCollectionFactory = $jobCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        /** @var \Straker\EasyTranslationPlatform\Model\ResourceModel\Job\Collection $collection */
        $collection = $this->_jobCollectionFactory->create();
        $collection->distinct(true)->addFieldToSelect('sl');
        $languages = [];
        foreach ($collection->getItems() as $job) {
            array_push($languages, ['value' => $job->getSl(), 'label'=> $job->getSl()]);
        }
        return $languages;
    }
}
