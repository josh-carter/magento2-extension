<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Straker\EasyTranslationPlatform\Model\JobFactory;
use Straker\EasyTranslationPlatform\Model\JobStatus;

class Page extends Container
{
    protected $_jobFactory;
    /** @var  \Straker\EasyTranslationPlatform\Model\Job */
    protected $_job;
    protected $_requestData;

    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        array $data = []
    ) {
    
        $this->_jobFactory = $jobFactory;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        $this->_requestData = $this->getRequest()->getParams();
        $this->_job = $this->_jobFactory->create()->load($this->_requestData['job_id']);

        if ($this->_job->getJobStatusId() == JobStatus::JOB_STATUS_COMPLETED) {
            $this->addButton(
                'publish',
                [
                    'label' => __('Publish'),
                    'onclick' => 'setLocation(\'' . $this->getUrl('EasyTranslationPlatform/Jobs/Confirm', [
                            'job_id' => $this->_job->getId(),
                            'job_key' => $this->_job->getJobKey(),
                            'job_type_id' => $this->_job->getJobTypeId()
                        ]) . '\') ',
                    'class' => 'primary',
                    'title' => __('Publish the job of 1%', $this->_job->getJobNumber())
                ],
                0,
                50
            );
        }

        $this->addButton(
            'job_type',
            [
                'label' => __('Back'),
                'onclick' => 'setLocation(\''
                    . $this->getUrl(
                        'EasyTranslationPlatform/Jobs/ViewJob',
                        [
                            'job_id' => $this->_requestData['job_id'],
                            'job_key'=> $this->_requestData['job_key'],
                            'job_type_id' => 0,
                            'source_store_id' => $this->_requestData['source_store_id']
                        ]
                    ) . '\') ',
                'class' => 'back',
                'title' => __('View content type details')
            ],
            0,
            20
        );

        parent::_construct();
    }

    protected function _prepareLayout()
    {
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
                    'label' => empty($this->_job->getJobNumber()) ? __('Sub-job') : $this->_job->getJobNumber(),
                    'url' => $this->getUrl(
                        'EasyTranslationPlatform/Jobs/ViewJob',
                        [
                            'job_id' => $this->_requestData['job_id'],
                            'job_key'=> $this->_requestData['job_key'],
                            'job_type_id' => 0,
                            'source_store_id' => $this->_requestData['source_store_id']
                        ]
                    ),
                    'title' => __('View content type details')
                ],
                [
                    'label' => ('Page List'),
                ]
            ]
        );

        $this->addChild(
            'straker_job_page_grid',
            \Straker\EasyTranslationPlatform\Block\Adminhtml\Job\ViewJob\Page\Grid::class
        );

        return parent::_prepareLayout();
    }

    public function _toHtml()
    {
        return $this->getChildHtml();
    }
}
