<?php
/**
 * Created by PhpStorm.
 * User: Paul
 * Date: 31/10/16
 * Time: 09:24
 */

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Action;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Block\Adminhtml\Page\Grid\Renderer\Action\UrlBuilder as PageUrlBuilder;
use Magento\Cms\Ui\Component\Listing\Column\BlockActions;
use Magento\Cms\Ui\Component\Listing\Column\PageActions;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Url;
use Magento\Store\Model\StoreManagerInterface;
use Straker\EasyTranslationPlatform\Model\Job;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\JobStatus;
use Straker\EasyTranslationPlatform\Model\JobType as JobModelType;

class MultiAction extends Action
{
    protected $_frontendUrl;
    protected $_storeManager;
    protected $_jobModel;
    protected $_pageUrlBuilder;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    private $_productFactory;
    /**
     * @var BlockFactory
     */
    private $_blockFactory;
    /**
     * @var PageFactory
     */
    private $_pageFactory;
    /**
     * @var CategoryFactory
     */
    private $_categoryFactory;

    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Url $url,
        ProductFactory $productFactory,
        CategoryFactory $categoryFactory,
        PageFactory $pageFactory,
        BlockFactory $blockFactory,
        StoreManagerInterface $storeManager,
        JobFactory $jobFactory,
        PageUrlBuilder $pageUrlBuilder,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $data);
        $this->_frontendUrl = $url;
        $this->_productFactory = $productFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_pageFactory = $pageFactory;
        $this->_blockFactory = $blockFactory;
        $this->_jobModel = $jobFactory->create();
        $this->_storeManager = $storeManager;
        $this->_pageUrlBuilder = $pageUrlBuilder;
    }

    /**
     * Renders column
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $html = '';
        $actions = $this->getColumn()->getActions();
        if (!empty($actions) && is_array($actions)) {
            $links = [];
            foreach ($actions as $action) {
                if (is_array($action)) {
                    $link = $this->_toLinkHtml($action, $row);
                    if ($link) {
                        $links[] = $link;
                    }
                }
            }
            $html = implode('<br />', $links);
        }

        if ($html == '') {
            $html = '&nbsp;';
        }

        return $html;
    }

    //phpcs:disable
    /**
     * Render single action as link html
     *
     * @param  array $action
     * @param  \Magento\Framework\DataObject $row
     * @return string|false
     */
    protected function _toLinkHtml(
        $action,
        \Magento\Framework\DataObject $row
    ) {
        $text = $action['caption']->getText();
        if (key_exists('caption', $action) && strcasecmp('View Details', $text) == 0) {
            return parent::_toLinkHtml($action, $row);
        } else {
            $job = $this->getJob($action);
            if ($job) {
                $entityKey = $this->getEntityIdName($job);
                $entityId = $row->getData($entityKey);
                if (is_numeric($entityId)) {
                    $targetStoreId = $job->getTargetStoreId();
                    $jobType = $job->getJobTypeId();
                    $jobStatus = $job->getJobStatusId();
                    $isPublished = $jobStatus >= JobStatus::JOB_STATUS_CONFIRMED;
                    if ($isPublished) {
                        if (is_numeric($targetStoreId)) {
                            $storeCode = $this->_storeManager->getStore($targetStoreId)->getCode();
                            $attr = 'target="_blank"';
                            $isFront = stripos($text, 'frontend');
                            $url = '';
                            switch ($jobType) {
                                case JobModelType::JOB_TYPE_PRODUCT:
                                    $productModel = $this->_productFactory->create();
                                    $productModel->load($entityId)->setStoreId($targetStoreId);
                                    if ($isFront === false) {
                                        $attr .= ' title="View in Backend"';

                                        $url = $this->getUrl(
                                            'catalog/product/edit',
                                            ['id' => $entityId, 'store' => $targetStoreId]
                                        );
                                        return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
                                    } else {
                                        if ($productModel->isVisibleInSiteVisibility()
                                            && !$productModel->isDisabled()
                                        ) {
                                            $attr .= ' title="View in Frontend"';
                                            $url = $this->_frontendUrl->getUrl(
                                                'catalog/product/view',
                                                [
                                                    'id' => $entityId,
                                                    '_nosid' => true,
                                                    '_query' => ['___store' => $storeCode]
                                                ]
                                            );
                                            return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
                                        }
                                    }
                                    break;
                                case JobModelType::JOB_TYPE_CATEGORY:
                                    $categoryModel = $this->_categoryFactory->create();
                                    $categoryModel->load($entityId)->setStoreId($targetStoreId);
                                    if ($isFront === false) {
                                        $attr .= ' title="View in Backend"';
                                        $url = $this->getUrl(
                                            'catalog/category/edit',
                                            ['id' => $entityId, 'store' => $targetStoreId]
                                        );
                                    } else {
                                        $attr .= ' title="View in Frontend"';
                                        $url = $this->_frontendUrl->getUrl(
                                            'catalog/category/view',
                                            [
                                                'id' => $entityId,
                                                '_nosid' => true,
                                                '_query' => ['___store' => $storeCode]
                                            ]
                                        );
                                    }
                                    return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
                                case JobModelType::JOB_TYPE_PAGE:
                                    $pageId = $job->getTranslatedPageId($entityId);
                                    if ($pageId) {
                                        $pageModel = $this->_pageFactory->create();
                                        $pageModel->load($pageId);
                                        if ($isFront === false) {
                                            $attr .= ' title="View in Backend"';
                                            $url = $this->getUrl(
                                                PageActions::CMS_URL_PATH_EDIT,
                                                ['page_id' => $pageId]
                                            );
                                        } else {
                                            $attr .= ' title="View in Frontend"';
                                            $url = $this->_pageUrlBuilder->getUrl(
                                                $pageModel->getIdentifier(),
                                                $targetStoreId,
                                                $storeCode
                                            );
                                        }
                                        return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
                                    }
                                    break;
                                case JobModelType::JOB_TYPE_BLOCK:
                                    $blockId = $job->getTranslatedBlockId($entityId);
                                    $blockModel = $this->_blockFactory->create();
                                    $blockModel->load($blockId);
                                    if ($isFront === false) {
                                        $attr .= ' title="View in Backend"';
                                        $url = $this->getUrl(BlockActions::URL_PATH_EDIT, ['block_id' => $blockId]);
                                    }
                                    return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
                            }
                        }
                    }
                }
            }
        }
    }
    //phpcs:enable

    /**
     * @param $action
     * @return null| Job
     */
    private function getJob($action)
    {
        $urlArray = key_exists('url', $action) ? $action['url'] : null;
        if (is_array($urlArray)) {
            $params = key_exists('params', $urlArray) ? $urlArray['params'] : null;
            if (is_array($params)) {
                $jobId = key_exists('job_id', $params) ? $params['job_id'] : null;
                if (is_numeric($jobId)) {
                    $this->_jobModel->load($jobId);
                    return $this->_jobModel->getId() ? $this->_jobModel : null;
                }
            }
        }
        return null;
    }

    private function getEntityIdName($job)
    {
        $jobType = $job->getJobTypeId();
        switch ($jobType) {
            case JobModelType::JOB_TYPE_PAGE:
                return 'page_id';
            case JobModelType::JOB_TYPE_BLOCK:
                return 'block_id';
            default:
                return 'entity_id';
        }
    }
}
