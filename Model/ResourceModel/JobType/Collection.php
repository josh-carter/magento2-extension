<?php
namespace Straker\EasyTranslationPlatform\Model\ResourceModel\JobType;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'type_id';

    protected function _construct()
    {
        $this->_init(
            \Straker\EasyTranslationPlatform\Model\JobType::class,
            \Straker\EasyTranslationPlatform\Model\ResourceModel\JobType::class
        );
    }

    /**
     * Convert items array to array for select options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('type_id', 'type_name');
    }
}
