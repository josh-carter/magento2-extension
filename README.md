# Straker Translations Magento 2 Extension
[![](https://www.strakertranslations.com/assets/images/logo.png "Straker Translations")](https://www.strakertranslations.com)

This extension will add the ability to translate products, categories, cms pages and blocks into different languages via Straker Translations API. 

## Installation

### Option A
* Download zip file of this extension
* Place all the files of the extension in your Magento 2 installation in the folder `app/code/Straker/EasyTranslationPlatform`
* Enable the extension: `php bin/magento --clear-static-content module:enable Straker_EasyTranslationPlatform`
* Upgrade db scheme: `php bin/magento setup:upgrade`
* Clear cache

### Option B
Install latest on master branch: `composer require strakertranslations/straker-magento2:dev-master`
or get latest release `composer require strakertranslations/straker-magento2`

## SandBox Mode
* To enable sandbox mode go to the configuration page and select the Straker Translations configuration tab to set the environment for testing. 
