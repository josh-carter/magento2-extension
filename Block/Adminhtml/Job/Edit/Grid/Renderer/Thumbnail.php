<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\Edit\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Catalog\Helper\Image;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;

class Thumbnail extends AbstractRenderer
{
    protected $imageHelper;
    protected $productRepositoryInterfaceFactory;

    public function __construct(
        Context $context,
        Image $imageHelper,
        ProductRepositoryInterfaceFactory $productRepositoryInterfaceFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->imageHelper = $imageHelper;
        $this->productRepositoryInterfaceFactory = $productRepositoryInterfaceFactory;
    }

    public function render(DataObject $row)
    {
        $index = $this->getColumn()->getIndex();
        $product = $this->productRepositoryInterfaceFactory->create()->getById($row->getData('entity_id'));
        $url = $this->imageHelper->init($product, $index)->getUrl();
        return '<img src="'. $url . '" width="97px" />';
    }
}
