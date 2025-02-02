<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job;

use Magento\Framework\View\Element\Template;
use Straker\EasyTranslationPlatform\Model;

class ViewJob extends Template
{
    const BLOCK_TEMPLATE = 'job/view-job-detail-grid.phtml';
    protected $_childName;

    public function _construct()
    {
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BLOCK_TEMPLATE);
        }
        parent::_construct();
    }

    protected function _prepareLayout()
    {
        $requestData = $this->getRequest()->getParams();
        if (isset($requestData['job_type_id'])) {
            $jobType = $requestData['job_type_id'];

            switch ($jobType) {
                case Model\JobType::JOB_TYPE_ATTRIBUTE:
                    $this->addChild(
                        'view_job_attribute_grid',
                        \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Attribute::class,
                        [
                            'id' => 'view-job-attribute-grid'
                        ]
                    );
                    $this->_childName = 'view_job_attribute_grid';
                    break;
                case Model\JobType::JOB_TYPE_PRODUCT:
                    $this->addChild(
                        'view_job_product_block',
                        \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Product::class,
                        [
                            'id' => 'view-job-product-grid'
                        ]
                    );
                    $this->_childName = 'view_job_product_block';
                    break;
                case Model\JobType::JOB_TYPE_CATEGORY:
                    $this->addChild(
                        'view_job_category_grid',
                        \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Category::class,
                        [
                            'id' => 'view-job-category-grid'
                        ]
                    );
                    $this->_childName = 'view_job_category_grid';
                    break;
                case Model\JobType::JOB_TYPE_PAGE:
                    $this->addChild(
                        'view_job_page_grid',
                        \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Page::class,
                        [
                            'id' => 'view-job-page-grid'
                        ]
                    );
                    $this->_childName = 'view_job_page_grid';
                    break;
                case Model\JobType::JOB_TYPE_BLOCK:
                    $this->addChild(
                        'view_job_block_grid',
                        \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Block::class,
                        [
                            'id' => 'view-job-block-grid'
                        ]
                    );
                    $this->_childName = 'view_job_block_grid';
                    break;
                default:
                    $this->addChild(
                        'view_job_type_grid',
                        \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Type::class,
                        [
                            'id' => 'view-job-type-grid'
                        ]
                    );
                    $this->_childName = 'view_job_type_grid';
                    break;
            }
        }
        return parent::_prepareLayout();
    }

    public function getHtml()
    {
        return  $this->getChildHtml($this->_childName);
    }
}
