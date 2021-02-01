<?php
namespace Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeOptionTranslation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_optionCollection;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        CollectionFactory $optionCollection
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager);
        $this->_optionCollection = $optionCollection;
    }

    protected function _construct()
    {
        $this->_init(
            \Straker\EasyTranslationPlatform\Model\AttributeOptionTranslation::class,
            \Straker\EasyTranslationPlatform\Model\ResourceModel\AttributeOptionTranslation::class
        );
    }

    public function addOptionFilter($attributeTranslationId)
    {
        return $this->addFieldToFilter('main_table.attribute_translation_id', ['eq' => $attributeTranslationId ]);
    }

    public function massUpdate(array $data)
    {
        if (!empty($this->getData())) {

            $this->getConnection()
                ->update(
                    $this->getResource()->getMainTable(),
                    $data,
                    $this->getResource()->getIdFieldName() . ' IN(' . implode(',', $this->getAllIds()) . ')'
                );

        }
        return $this;
    }
}
