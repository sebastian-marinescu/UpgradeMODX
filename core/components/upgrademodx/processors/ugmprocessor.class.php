<?php
/**
 * Processor file for UpgradeMODX extra
 *
 * Copyright 2015-2018 by Bob Ray <https://bobsguides.com>
 * Created on 07-16-2018
 *
 * UpgradeMODX is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * UpgradeMODX is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * UpgradeMODX; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package upgrademodx
 * @subpackage processors
 */

/* @var $modx modX */
/* For lexicon helper:
$this->modx->lexicon->load('upgrademodx:default');
*/

abstract class UgmProcessor extends modProcessor {

   public $props;
   public $languageTopics = array('upgrademodx:default');
   public $devMode = false;
   public $errors = array();
   public $name = '';
   public $corePath ='';
   public $tempDir = '';
   public $unzippedDir = '';
   public $testDir = null;
   public $logFilePath = MODX_CORE_PATH . 'cache/logs/upgrademodx.log';
   public $zipFileName = '';
   public $certPath = '';
   public $sslVerifyPeer = true;

    public function initialize() {
        /** @var $props array() */
        $this->props = $this->getProperty('props');
        $this->devMode = $this->modx->getOption('ugm.devMode', null, false, true);
        $this->corePath = $this->modx->getOption('ugm.core_path', null, $this->modx->getOption('core_path') . 'components/upgrademodx/');
        $this->tempDir = $this->modx->getOption('ugm_temp_dir', null, MODX_BASE_PATH . 'ugmtemp/');
        if ($this->devMode) {
            $this->tempDir = 'c:/dummy/ugmtemp/';
            $this->mmkDir('c:/dummy/ugmtemp/test');
            $this->testDir = 'c:/dummy/ugmtemp/test/';
            $this->logFilePath = 'C:/dummy/ugmtemp/upgrademodx.log';
        }
        $this->zipFileName = $this->getProperty('version');
        $this->unzippedDir = $this->tempDir . 'unzipped';
        if (!is_dir($this->tempDir)) {
            $this->mmkDir($this->unzippedDir);
        }

        $this->certPath = $this->modx->getOption('ugm_cert_path', null, '', true);
        $this->sslVerifyPeer = $this->modx->getOption('ugm_ssl_verify_peer', null, true);
        return parent::initialize();
    }

    public function checkPermissions() {
        return parent::checkPermissions(); // ToDo: Change the autogenerated stub?
    }

    public function addError($message) {
        // $this->modx->log(modX::LOG_LEVEL_ERROR, print_r($message, true));
        $this->errors[] = $this->removeDoc($message);
    }

    public function hasErrors() {
        return count($this->errors) > 0 ? true : false;
    }

    public function getErrors() {
        return $this->errors;
    }

    /* Remove Guzzle Documentation link in Exception error messages */
    public function removeDoc($msg) {
        return preg_replace('#,\s*"documentation_url": "https://developer.github.com/v3"#', '', $msg);
    }

    public function mmkDir($folder, $perm = 0755) {
        $success = true;
        if (!is_dir($folder)) {
            @mkdir($folder, $perm, true);
        }
        if (!is_dir($folder)) {
            $success = false;
        }
        return $success;
    }

    public function log($msg, $suppressLineFeed = false) {
        $fp = fopen($this->logFilePath, 'a');
        $lf = $suppressLineFeed ? '' : PHP_EOL;
        if ($fp) {
            fwrite($fp, $lf . $msg);
            fclose($fp);
        }
    }

    public function prepareResponse($msg, $object = null) {
        if ($this->hasErrors()) {
            $this->log(implode(', ' , $this->getErrors()));
            $msg = '<p class="ugm_error"> [' .
                $this->name . '] Error: ' . implode("<br>", $this->getErrors()) . '</p>';

            return $this->failure($msg, $object);
        } else {
            return $this->success($msg, $object);
        }
    }


}

return 'UgmProcessor';
