<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Action;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Block\Adminhtml\Page\Grid\Renderer\Action\UrlBuilder as PageUrlBuilder;
use Magento\Cms\Ui\Component\Listing\Column\BlockActions;
use Magento\Cms\Ui\Component\Listing\Column\PageActions;
use Magento\Framework\DataObject;
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
     * @var Product
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
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
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

    /**
     * Render single action as link html
     *
     * @param  array $action
     * @param  DataObject $row
     * @return string|false
     */
    protected function _toLinkHtml(
        $action,
        DataObject $row
    ) {
        $text = $action['caption']->getText();
        if ($text && strcasecmp('View Details', $text) === 0) {
            if (isset($action['field']) && isset($action['url']) && is_string($action['url'])) {
                $param = "{$action['field']}/{$this->_getValue($row)}";
                $action['url'] .= $param;
                unset($action['field']);
            }
            return parent::_toLinkHtml($action, $row);
        } else {
            return $this->getLinkHtml($action, $row, $text);
        }
    }

    /**
     * @param $action
     * @return null| Job
     */
    private function getJob($action)
    {
        $urlArray = $action['url'] ?? null;
        if (is_array($urlArray)) {
            $params = $urlArray['params'] ?? null;
            if (is_array($params)) {
                $jobId = $params['job_id'] ?? null;
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

    private function getLinkHtml(
        $action,
        $row,
        $text
    ) {
        $job = $this->getJob($action);
        if ($job) {
            $entityKey = $this->getEntityIdName($job);
            $entityId = $row->getData($entityKey);
            if (is_numeric($entityId)) {
                $targetStoreId = $job->getTargetStoreId();
                $jobType = $job->getJobTypeId();
                $jobStatus = $job->getJobStatusId();
                $isPublished = $jobStatus >= JobStatus::JOB_STATUS_CONFIRMED;
                if ($isPublished && is_numeric($targetStoreId)) {
                    $storeCode = $this->_storeManager->getStore($targetStoreId)->getCode();
                    $attr = 'target="_blank"';
                    $isFront = stripos($text, 'frontend');
                    switch ($jobType) {
                        case JobModelType::JOB_TYPE_PRODUCT:
                            return $this->getLinkHtmlForProduct(
                                $entityId,
                                $targetStoreId,
                                $isFront,
                                $attr,
                                $text,
                                $storeCode
                            );
                        case JobModelType::JOB_TYPE_CATEGORY:
                            return $this->getLinkHtmlForCategory(
                                $entityId,
                                $targetStoreId,
                                $isFront,
                                $attr,
                                $storeCode,
                                $text
                            );
                        case JobModelType::JOB_TYPE_PAGE:
                            return $this->getLinkHtmlForPage(
                                $job,
                                $entityId,
                                $isFront,
                                $attr,
                                $targetStoreId,
                                $storeCode,
                                $text
                            );
                        case JobModelType::JOB_TYPE_BLOCK:
                            return $this->getLinkHtmlForBlock($job, $entityId, $isFront, $attr, $text);
                    }
                }
            }
        }

        return false;
    }

    private function getLinkHtmlForProduct(
        $entityId,
        $targetStoreId,
        $isFront,
        $attr,
        $text,
        $storeCode
    ) {
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
                        '_query' => [StoreManagerInterface::PARAM_NAME => $storeCode]
                    ]
                );
                return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
            }
        }

        return false;
    }

    /**
     * @param int $entityId
     * @param int $targetStoreId
     * @param bool $isFront
     * @param string $attr
     * @param string $storeCode
     * @param $text
     * @return string
     */
    protected function getLinkHtmlForCategory(
        int $entityId,
        int $targetStoreId,
        bool $isFront,
        string $attr,
        string $storeCode,
        $text
    ): string {
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
                    '_query' => [StoreManagerInterface::PARAM_NAME => $storeCode]
                ]
            );
        }
        return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
    }

    /**
     * @param Job $job
     * @param int $entityId
     * @param bool $isFront
     * @param string $attr
     * @param int $targetStoreId
     * @param string $storeCode
     * @param $text
     * @return false|string
     */
    protected function getLinkHtmlForPage(
        Job $job,
        int $entityId,
        bool $isFront,
        string $attr,
        int $targetStoreId,
        string $storeCode,
        $text
    ) {
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
                $url = str_replace('/?', '?', $url);
            }
            return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
        }
        return false;
    }

    /**
     * @param Job $job
     * @param int $entityId
     * @param bool $isFront
     * @param string $attr
     * @param $text
     * @return string
     */
    protected function getLinkHtmlForBlock(
        Job $job,
        int $entityId,
        bool $isFront,
        string $attr,
        $text
    ): string {
        $blockId = $job->getTranslatedBlockId($entityId);
        $blockModel = $this->_blockFactory->create();
        $blockModel->load($blockId);
        if ($isFront === false) {
            $attr .= ' title="View in Backend"';
            $url = $this->getUrl(BlockActions::URL_PATH_EDIT, ['block_id' => $blockId]);
            return sprintf('<a href="%s" %s>%s</a>', $url, $attr, $text);
        }
        return false;
    }
}
