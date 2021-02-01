<?php
namespace Straker\EasyTranslationPlatform\Block\Adminhtml\Settings\Config;

use IntlDateFormatter;
use Magento\Backend\Block\Widget\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\ResourceConnection;
use Straker\EasyTranslationPlatform\Helper\Data;
use Magento\Backup\Model\Fs\CollectionFactory as FsCollectionFactory;

/*
 * This class preform Magento database rollback. Since restore functionality has been
 * change to call portal server to restore, this class is not used but remains here in
 * case requiring the functionality switch back.
 */

class RestoreProductData extends Field
{
    const BUTTON_TEMPLATE = 'settings/config/button/restore_product_data_button.phtml';
//    const BUTTON_TEMPLATE = 'Magento_Backup::backup/dialogs.phtml';

    private $_buttonId;
    private $_buttonName;
    private $_dataHelper;
    private $_resourceConnection;
    private $_fsCollectionFactory;
    private $_backupData;

    public function __construct(
        Context $context,
        Data $dataHelper,
        ResourceConnection $resourceConnection,
        FsCollectionFactory $fsCollectionFactory,
        \Magento\Backup\Helper\Data $backupData,
        array $data = []
    ) {
        $this->_dataHelper = $dataHelper;
        $this->_resourceConnection = $resourceConnection;
        $this->_fsCollectionFactory = $fsCollectionFactory;
        $this->_backupData = $backupData;
        parent::__construct($context, $data);
    }

    /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxResetUrl()
    {
        return $this->getUrl('EasyTranslationPlatform/Settings/RestoreProductData'); //hit controller by ajax call on button click.
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->_buttonId = $element->getId();
        $this->_buttonName = $element->getName();

        return $this->_toHtml();
    }

    public function getRestoreFileList()
    {
        /** @var \Magento\Backup\Model\Fs\Collection $fsCollection */
        $fsCollection = $this->_fsCollectionFactory->create();
//        $fsCollection->setFilesFilter(
//           '/^.*_straker[a-z0-9\_]+\.sql$/mi'
//        );
//        var_dump($fsCollection->getItems());exit;
//        $items = $fsCollection->getItems();
//        if(count( $items )){
//            return reset($items);
//        }
//        return null;
        return $fsCollection->getItems();
    }

    public function getDateFormat($date)
    {
        return $this->_localeDate->formatDateTime($date, IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
    }

    public function getDeleteBackupFileUrl()
    {
        return $this->getUrl('EasyTranslationPlatform/Settings/DeleteBackup');
    }
}
