<?php
namespace Straker\EasyTranslationPlatform\Model\ResourceModel\Job;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'job_id';
    protected $_configHelper;
    protected $_mode;
    protected $_coreRegistry;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        ConfigHelper $configHelper,
        Registry $registry
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager);
        $this->_configHelper = $configHelper;
        $this->_coreRegistry = $registry;
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Straker\EasyTranslationPlatform\Model\Job', 'Straker\EasyTranslationPlatform\Model\ResourceModel\Job');
    }

    /**
     * @return Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = clone $this->getSelect();
        $countSelect->reset(Select::ORDER);
        $countSelect->reset(Select::LIMIT_COUNT);
        $countSelect->reset(Select::LIMIT_OFFSET);
        $countSelect->reset(Select::COLUMNS);

        if (!count($this->getSelect()->getPart(Select::GROUP))) {
            $countSelect->columns(new \Zend_Db_Expr('COUNT(*)'));
            return $countSelect;
        }

        $countSelect->reset(Select::GROUP);
        $group = $this->getSelect()->getPart(Select::GROUP);
        $countSelect->columns(new \Zend_Db_Expr(("COUNT(DISTINCT ".implode(", ", $group).")")));
        return $countSelect;
    }
}
