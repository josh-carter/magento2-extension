<?php

namespace Straker\EasyTranslationPlatform\Api\Data;

interface SetupInterface
{
    const SITE_MODE_SANDBOX = 0;
    const SITE_MODE_LIVE = 1;

    public function saveAppKey($appKey);

    public function saveAccessToken($accessToken);

    public function saveStoreSetup($storeId, $source_store, $source_language, $destination_language);

    public function saveClientData($data);

    public function saveAttributes($attributes);

    public function setSiteMode($mode);

    public function isTestingStoreViewExist();

    public function deleteTestingStoreView($siteMode);

    public function createTestingStoreView($storeName = '', $siteMode = 'sandbox');
}
