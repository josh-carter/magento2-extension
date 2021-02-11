<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Job;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Straker\EasyTranslationPlatform\Model\JobFactory;

class ViewQuote extends Container
{
    protected $_coreRegistry;
    protected $_jobFactory;
    protected $_job;
    protected $_requestData;

    public function __construct(
        Context $context,
        Registry $registry,
        JobFactory $jobFactory,
        array $data
    ) {
        $this->_jobFactory = $jobFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    public function _construct()
    {

        $this->_requestData = $this->getRequest()->getParams();
        $this->_job = $this->_jobFactory->create()->load($this->_requestData['job_id']);

        $this->addButton(
            'back',
            [
                'label' => __('Back'),
                'onclick' => 'setLocation(\'' . $this->getUrl('EasyTranslationPlatform/Jobs/Index') . '\') ',
                'class' => 'back',
                'title' => __('Go to Manage Jobs page')
            ]
        );

        parent::_construct();
    }

    protected function _prepareLayout()
    {

        $this->addChild(
            'straker-title-job-quote',
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
                    'label' => __('View Quote'),
                ]
            ]
        );

        $this->addChild(
            'straker-title-manageJob',
            \Magento\Framework\View\Element\Template::class
        )->setTemplate(
            'Straker_EasyTranslationPlatform::job/quote-frame.phtml'
        )->setData(
            'quote-url',
            $this->getQuoteFrameUrl()
        );

        return parent::_prepareLayout();
    }

    public function getQuoteFrameUrl()
    {
        $quoteUrl = $this->_coreRegistry->registry('quote_url');
        $this->_coreRegistry->unregister('quote_url');
        return $quoteUrl;
    }

    public function _toHtml()
    {
        return $this->getChildHtml();
    }
}
