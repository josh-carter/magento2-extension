<?php

namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Support\Form;

use Magento\Backend\Block\Widget\Form\Generic;
use Straker\EasyTranslationPlatform\Model\ResourceModel\Job\CollectionFactory as JobCollection;
use Straker\EasyTranslationPlatform\Helper\ConfigHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;

class Form extends Generic
{

    protected $_jobCollection;
    protected $_Registry;
    protected $_configHelper;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        ConfigHelper $configHelper,
        JobCollection $jobCollection,
        array $data = []
    ) {
        $this->_formFactory = $formFactory;
        $this->_Registry = $registry;
        $this->_jobCollection = $jobCollection;
        $this->_configHelper = $configHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }


    protected function _construct()
    {
        parent::_construct();
    }

    protected function _prepareForm()
    {

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('item_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => ' ', 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'name',
            'text',
            [
                'name'      => 'name',
                'label'     => __('Name'),
                'title'     => __('Name'),
                'required'  => true
            ]
        );


        $fieldset->addField(
            'email',
            'text',
            [
                'name'      => 'email',
                'label'     => __('Email Address'),
                'title'     => __('Email Address'),
                'required'  => true,
                'class'     =>'validate-email'
            ]
        );

        $fieldset->addField(
            'job_id',
            'select',
            [
                'label' => __('Job Number'),
                'title' => __('Job Number'),
                'name' => 'job_id',
                'options' => $this->_getTJNumbers()
            ]
        );

        $fieldset->addField(
            'category',
            'select',
            [
                'label'     => __('Category'),
                'title'     => __('Category'),
                'name'      => 'category',
                'required'  => true,
                'options'   => [
                    ''=>'',
                    'delivery'  =>  __('Delivery'),
                    'quality'   =>  __('Quality'),
                    'payment'   =>  __('Payment'),
                    'job'       =>  __('Job'),
                    'technical' =>  __('Technical'),
                    'invoice'   =>  __('Invoice'),
                    'messages'  =>  __('Messages')
                ]
            ]
        );



        $fieldset->addField(
            'detail',
            'textarea',
            [
                'name'      => 'detail',
                'label'     => __('Detail'),
                'title'     => __('Detail'),
                'required'  => true
            ]
        );

        $fieldset->addField(
            'url',
            'hidden',
            [
                'name'  => 'url',
                'label' => __('Website Url'),
                'title' => __('Website Url')
            ]
        );

        $fieldset->addField(
            'module_version',
            'hidden',
            [
                'name'  => 'module_version',
                'label' => __('Module Version'),
                'title' => __('Module Version')
            ]
        );

        $fieldset->addField(
            'app_version',
            'hidden',
            [
                'name'  => 'app_version',
                'label' => __('App Version'),
                'title' => __('App Version')
            ]
        );


        $form->setUseContainer(true);

        $form->setValues($this->getRequest()->getParams());

        $form->setValues(
            [
                'url'           =>$this->_storeManager->getStore()->getBaseUrl(),
                'app_version'   => $this->_configHelper->getMagentoVersion(),
                'module_version'=>$this->_configHelper->getModuleVersion(),
                'name'          =>$this->getRequest()->getParam('name'),
                'email'         =>$this->getRequest()->getParam('email'),
                'job_id'        =>$this->getRequest()->getParam('job_id'),
                'category'      =>$this->getRequest()->getParam('category'),
                'detail'        =>$this->getRequest()->getParam('detail')
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getTJNumbers()
    {

        $options = [];

        $options[] = '';

        $jobs = $this->_jobCollection->create()
            ->addFieldToSelect(['job_number']);

        foreach ($jobs->toArray()['items'] as $item) {
            if (empty($item['job_number'])) {
                continue;
            }
            $options[$item['job_number']] = $item['job_number'];
        }

        return $options;
    }
}
