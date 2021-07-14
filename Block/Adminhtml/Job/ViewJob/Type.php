<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\JobStatus;

class Type extends Container
{
    protected $_jobFactory;
    /** @var  \Straker\EasyTranslationPlatform\Model\Job */
    protected $_job;
    protected $_configHelper;

    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->_jobFactory = $jobFactory;
        $this->_configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $params = $this->getRequest()->getParams();
        $this->_job = $this->_jobFactory->create()->load($params['job_id']);

        //go back button
        $goBackButton = [
            'label' => __('Back'),
            'onclick' => 'setLocation(\'' . $this->getUrl('EasyTranslationPlatform/Jobs/') . '\') ',
            'class' => 'back',
            'title' => __('Go to Manage Jobs page')
        ];

        $exportButtonData = [
            'label' => __('Export'),
            'onclick' => 'setLocation(\'' . $this->getUrl('EasyTranslationPlatform/Jobs/Export', [
                    'job_id' => $this->_job->getId(),
                    'job_key' => $this->_job->getJobKey(),
                    'source_store_id' => $this->_job->getSourceStoreId()
                ]) . '\') ',
            'class' => 'action-default',
            'title' => __('Export source content to XML file.')
        ];

        $importButtonData = [
            'label' => __('Import'),
            'class' => 'action-secondary',
            'title' => __('Import Translation')
        ];

        $publishButtonData = [
            'label' => __('Publish'),
            'onclick' => 'setLocation(\'' . $this->getUrl('EasyTranslationPlatform/Jobs/Confirm', [
                    'job_id' => $this->_job->getId(),
                    'job_key' => $this->_job->getJobKey(),
                    'job_type_id' => $this->_job->getJobTypeId()
                ]) . '\') ',
            'class' => 'primary',
            'title' => __('Publish the job of 1%', $this->_job->getJobNumber())
        ];

        $this->addButton('manage_job', $goBackButton, 0, 10);

        $statusId = $this->_job->_getLowestJobStatusId();

        if ($statusId >= JobStatus::JOB_STATUS_INIT) {
            $this->addButton('export_source_file', $exportButtonData, 0, 20);
        }

        if ($statusId >= JobStatus::JOB_STATUS_COMPLETED) {
            $this->addButton('import_translated_file', $importButtonData, 0, 30);
        }

        if ($statusId == JobStatus::JOB_STATUS_COMPLETED) {
            $this->addButton('publish', $publishButtonData, 0, 40);
        }

        parent::_construct();
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'straker-title-manageJob',
            \Magento\Framework\View\Element\Template::class
        )->setTemplate('Straker_EasyTranslationPlatform::job/viewJobTitle.phtml')->setData('title', 'Manage Jobs');

        $this->addChild(
            'straker-breadcrumbs',
            \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Widget\Breadcrumbs::class,
            [
                [
                    'label' => __('Manage Jobs'),
                    'url' => $this->getUrl('EasyTranslationPlatform/Jobs/'),
                    'title' => __('Go to Manage Jobs page')
                ],
                [
                    'label' => empty($this->_job->getJobNumber()) ? __('Sub-job') : $this->_job->getJobNumber()
                ]
            ]
        );

        $this->addChild(
            'straker_job_type_grid',
            \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Type\Grid::class
        );

        return parent::_prepareLayout();
    }

    public function _toHtml()
    {
        return $this->getChildHtml();
    }

    public function getButtonHtml($label, $onclick, $class = '', $buttonId = null, $dataAttr = [])
    {
        return $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => $label, 'onclick' => $onclick, 'class' => $class, 'type' => 'button', 'id' => $buttonId]
        )->setDataAttribute(
            $dataAttr
        )->toHtml();
    }

    public function getJobId()
    {
        return $this->_job->getId();
    }

    public function getJobKey()
    {
        return $this->_job->getJobKey();
    }

    public function getSourceStoreId()
    {
        return $this->_job->getSourceStoreId();
    }

    public function getImportUrl()
    {
        return $this->getUrl('EasyTranslationPlatform/Jobs/Import');
    }
}
