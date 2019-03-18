<?php

namespace Straker\EasyTranslationPlatform\Helper;

use Exception;
use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Category;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Collection;

use Magento\Framework\Message\ManagerInterface;
use Straker\EasyTranslationPlatform\Api\Data\StrakerAPIInterface;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Helper\ProductHelper;
use Straker\EasyTranslationPlatform\Helper\CategoryHelper;
use Straker\EasyTranslationPlatform\Helper\PageHelper;
use Straker\EasyTranslationPlatform\Helper\BlockHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\JobType;
use Straker\EasyTranslationPlatform\Model\JobStatus;
use Straker\EasyTranslationPlatform\Model\ResourceModel\JobType\CollectionFactory as JobTypeCollection;
use Straker\EasyTranslationPlatform\Model\ResourceModel\JobStatus\CollectionFactory as JobStatusCollection;

class JobHelper extends AbstractHelper
{
    protected $jobModel;
    protected $jobData;
    protected $jobFileName;
    protected $productData;
    protected $_directoryList;
    protected $_jobTypeCollection;
    protected $_jobFactory;
    protected $_configHelper;
    protected $_productHelper;
    protected $_categoryHelper;
    protected $_pageHelper;
    protected $_blockHelper;
    protected $_jobStatusCollection;
    protected $_messageManager;
    protected $_strakerApi;

    const SEPARATOR = '--';

    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        ProductHelper $productHelper,
        CategoryHelper $categoryHelper,
        PageHelper $pageHelper,
        ConfigHelper $configHelper,
        BlockHelper $blockHelper,
        JobTypeCollection $jobTypeCollectionFactory,
        JobStatusCollection $jobStatusFactory,
        ManagerInterface $messageManager,
        StrakerAPIInterface $strakerApi
    ) {

        $this->_jobFactory = $jobFactory;
        $this->_configHelper = $configHelper;
        $this->_productHelper = $productHelper;
        $this->_categoryHelper = $categoryHelper;
        $this->_pageHelper = $pageHelper;
        $this->_blockHelper = $blockHelper;
        $this->_jobTypeCollection = $jobTypeCollectionFactory;
        $this->_jobStatusCollection = $jobStatusFactory;
        $this->_messageManager = $messageManager;
        $this->_strakerApi = $strakerApi;
        parent::__construct($context);
    }

    /**
     * @param $data
     * @return $this
     */
    public function createJob($data)
    {

        try{

            $this->jobData = $data;

            $this->jobModel = $this->_jobFactory->create();

            $jobData = [
                'job_status_id'=> JobStatus::JOB_STATUS_INIT,
                'source_store_id'=>$this->_configHelper->getStoreInfo($this->jobData['magento_destination_store'])['straker/general/source_store'],
                'target_store_id'=>$this->jobData['magento_destination_store'],
                'sl'=>$this->_configHelper->getStoreInfo($this->jobData['magento_destination_store'])['straker/general/source_language'],
                'tl'=>$this->_configHelper->getStoreInfo($this->jobData['magento_destination_store'])['straker/general/destination_language']
            ];

            if ($this->_configHelper->isSandboxMode()) {
                $jobData['is_test_job'] = true;
            }

            $this->jobModel->setData($jobData);

        }catch (Exception $e){
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
            $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
            $this->_messageManager->addError($e->getMessage());
        }

        return $this;

    }

    public function generateProductJob()
    {

        try{

            $this->jobModel->addData(['job_type_id'=> JobType::JOB_TYPE_PRODUCT]);

            $this->jobModel->save();

            $jobFile = $this->_productHelper->getProducts($this->jobData['products'], $this->jobModel->getData('source_store_id'))
                ->getSelectedProductAttributes()
                ->saveProductData($this->jobModel->getId())
                ->generateProductXML($this->jobModel);

            $this->jobModel->addData([
                'source_file'   =>  $jobFile,
                'summary'       =>  json_encode($this->_productHelper->getSummary())
            ]);

            $this->jobModel->save();


        }catch (Exception $e){
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
            $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
            $this->_messageManager->addError($e->getMessage());
        }

        return $this->jobModel;

    }

    /**
     * @return $this
     */

    public function generateCategoryJob()
    {

        try{

            $this->jobModel->addData(['job_type_id'=> JobType::JOB_TYPE_CATEGORY]);

            $this->jobModel->save();

            $jobFile = $this->_categoryHelper->getCategories($this->jobData['categories'], $this->jobModel->getData('source_store_id'))
                ->getSelectedCategoryAttributes()
                ->saveCategoryData($this->jobModel->getId())
                ->generateCategoryXML($this->jobModel);

            $this->jobModel->addData([
                'source_file'   =>  $jobFile,
                'summary'       =>  json_encode($this->_categoryHelper->getSummary())
            ]);

            $this->jobModel->save();


        }catch (Exception $e){
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
            $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
            $this->_messageManager->addError($e->getMessage());
        }


        return $this->jobModel;
    }

    public function generatePageJob()
    {

        try{

            $this->jobModel->addData(['job_type_id'=> JobType::JOB_TYPE_PAGE]);
            $this->jobModel->save();

            $jobFile = $this->_pageHelper->getPages($this->jobData['pages'], $this->jobModel->getData('source_store_id'))
                ->getSelectedPageAttributes()
                ->savePageData($this->jobModel->getId())
                ->generatePageXML($this->jobModel);

            $this->jobModel->addData([
                'source_file'   =>  $jobFile,
                'summary'       =>  json_encode($this->_pageHelper->getSummary())
            ]);

            $this->jobModel->save();

        }catch (Exception $e){
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
            $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
            $this->_messageManager->addError($e->getMessage());
        }

        return $this->jobModel;
    }

    public function generateBlockJob()
    {

        try{

            $this->jobModel->addData(['job_type_id'=> JobType::JOB_TYPE_BLOCK]);

            $this->jobModel->save();

            $jobFile = $this->_blockHelper->getBlocks($this->jobData['blocks'], $this->jobModel->getData('source_store_id'))
                ->getSelectedBlockAttributes()
                ->saveBlockData($this->jobModel->getId())
                ->generateBlockXML($this->jobModel);

            $this->jobModel->addData([
                'source_file'   =>  $jobFile,
                'summary'       =>  json_encode($this->_blockHelper->getSummary())
            ]);

            $this->jobModel->save();

        }catch (Exception $e){
            $this->_logger->error('error' . __FILE__ . ' ' . __LINE__ . ' ' . $e->getMessage(), array($e));
            $this->_strakerApi->_callStrakerBugLog(__FILE__ . ' ' . __METHOD__ . ' ' . $e->getMessage(), $e->__toString());
            $this->_messageManager->addError($e->getMessage());
        }

        return $this->jobModel;
    }

    /**
     * @return mixed
     */
    public function getJobInfo()
    {

        return $this->jobModel->getData();
    }

    /**
     * @return mixed
     */
    public function getJob()
    {

        return $this->jobModel;
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->jobModel->save();

        return $this;
    }

//    /**
//     * @param $jobType
//     * @return mixed
//     */
//    protected function getJobTypeId($jobType){
//
//        $collection = $this->_jobTypeCollection->create()
//            ->addFieldToFilter('type_name',array('eq'=>$jobType))
//            ->getFirstItem();
//
//        return $collection->getData('type_id');
//    }

//    /**
//     * @param $jobStatus
//     * @return mixed
//     */
//    protected function getJobStatusId($jobStatus){
//
//        $collection = $this->_jobStatusCollection->create()
//            ->addFieldToFilter('status_name',array('eq'=>$jobStatus))
//            ->getFirstItem();
//
//        return $collection->getData('status_id');
//    }
}
