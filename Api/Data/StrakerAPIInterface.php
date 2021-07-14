<?php

namespace Straker\EasyTranslationPlatform\Api\Data;

interface StrakerAPIInterface
{
    public function getCountries();

    public function getLanguages();

    public function callRegister($data);

    public function callTranslate($request);

    public function getLanguageName($code);

    public function callSupport($data);

    public function getTranslatedFile($url);

    public function dbBackup();

    public function dbRestore();

    public function _callStrakerBugLog($msg, $e = '');
}
