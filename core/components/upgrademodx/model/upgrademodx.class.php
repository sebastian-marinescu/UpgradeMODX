<?php
/**
 * UpgradeMODX class file for UpgradeMODX Widget snippet for  extra
 *
 * Copyright 2015-2018 Bob Ray <https://bobsguides.com>
 * Created on 08-16-2015
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
 */

/**
 * Description
 * -----------
 * UpgradeMODX Dashboard widget
 * This package was inspired by the work of a number of people and I have borrowed some of their code.
 * Dmytro Lukianenko (dmi3yy) is the original author of the MODX install script. Susan Sottwell, Sharapov,
 * Bumkaka, Inreti, Zaigham Rana, frischnetz, and AgelxNash, also contributed and I'd like to thank all
 * of them for laying the groundwork.
 *
 * Variables
 * ---------
 * @var $modx modX
 * @var $scriptProperties array
 *
 * @package upgrademodx
 **/

/* Properties

 * @property &groups textfield -- group, or commma-separated list of groups, who will see the widget; Default: (empty)..
 * @property &hideWhenNoUpgrade combo-boolean -- Hide widget when no upgrade is available; Default: No.
 * @property &interval textfield -- Interval between checks -- Examples: 1 week, 3 days, 6 hours; Default: 1 week.
 * @property &language textfield -- Two-letter code of language to user; Default: en.
 * @property &lastCheck textfield -- Date and time of last check -- set automatically; Default: (empty)..
 * @property &latestVersion textfield -- Latest version (at last check) -- set automatically; Default: (empty)..
 * @property &plOnly combo-boolean -- Show only pl (stable) versions; Default: yes.
 * @property &versionsToShow textfield -- Number of versions to show in upgrade form (not widget); Default: 5.

 */
if (!class_exists('UpgradeMODX')) {
    class UpgradeMODX {


        /** @var $versionArray string - array of versions to display if upgrade is available as a string
         *  to inject into upgrade script */
        public $versionArray = '';

        /** @var $versionListPath string - location of versionlist file */
        public $versionListPath;

        /** @var $modx modX - modx object */
        public $modx = null;

        /** @var $latestVersion string - latest version available */
        public $latestVersion = '';

        /** @var $errors array - array of error message (non-fatal errors only) */
        public $errors = array();

        /** @var $forcePclZip boolean */
        public $forcePclZip = false;

        /** @var $forceFopen boolean */
        public $forceFopen = false;

        /** @var $githubTimeout int */
        public $gitHubTimeout = 6;

        /** @var $modxTimeout int */
        public $modxTimeout = 6;

        /** @var $attempts int */
        public $attempts = 2;

        /** @var $verifyPeer int */
        public $verifyPeer = true;

        /** @var $github_username string */
            public $github_username = '';

        /** @var $github_token string */
            public $github_token = '';

        /** @var $devModx bool */
            protected $devMode = false;

        /** @var $versionsToShow bool */
        protected $versionsToShow = 5;

        /** @var $progressFilePath string */
            protected $progressFilePath = '';

        /** @var $progressFileURL string */
        protected $progressFileURL = '';


        public function __construct($modx) {
            /** @var $modx modX */
            $this->modx = $modx;
        }

        public function init($props) {
            /** @var $InstallData array */
            $language = $this->modx->getOption('language', $props, 'en', true);
            $this->modx->lexicon->load($language . ':upgrademodx:default');
            $this->forcePclZip = $this->modx->getOption('forcePclZip', $props, false);
            $this->forceFopen = $this->modx->getOption('forceFopen', $props, false);
            $this->plOnly = $this->modx->getOption('plOnly', $props);
            $this->gitHubTimeout = $this->modx->getOption('githubTimeout', $props, 6, true);
            $this->modxTimeout = $this->modx->getOption('modxTimeout', $props, 6, true);
            $this->attempts = $this->modx->getOption('attempts', $props, 2, true);
            $this->errors = array();
            $this->latestVersion = $this->modx->getOption('latestVersion', $props, '', true);
            $path = $this->modx->getOption('versionListPath', $props, MODX_CORE_PATH . 'cache/upgrademodx/', true);
            $path = str_replace('{core_path}', MODX_CORE_PATH, $path);
            $this->versionListPath = str_replace('{assets_path}', MODX_ASSETS_PATH, $path);
            $this->verifyPeer = $this->modx->getOption('ssl_verify_peer', $props, true);
            $this->devMode = (bool) $this->modx->getOption('ugm.devMode', null, false, true);
            $this->progressFilePath = MODX_ASSETS_PATH . 'components/upgrademodx/ugmprogress.txt';
            $this->mmkDir(MODX_ASSETS_PATH . 'components/upgrademodx');
            $this->progressFileURL = MODX_ASSETS_URL . 'components/upgrademodx/ugmprogress.txt';
            file_put_contents($this->progressFilePath, 'Starting Upgrade');
            $this->versionsToShow = $this->modx->getOption('versionsToShow', $props, 5, true);

            /* These use System Setting if property is empty */
            $this->github_username = $this->modx->getOption('github_username',
                $props, $this->modx->getOption('github_username', null), true);
            $this->github_token = $this->modx->getOption('github_token', $props,
                $this->modx->getOption('github_token', null), true);
        }

        public function writeScriptFile() {
            /** @var  $InstallData array */

            $fp = @fopen(MODX_BASE_PATH . 'upgrade.php', 'w');

            if ($fp) {
                @include $this->versionListPath . 'versionlist';
                $versionArray = $InstallData;

                if (! is_array($versionArray) || empty($versionArray)) {
                    $this->setError($this->modx->lexicon('ugm_no_version_list') . '@ ' . $this->versionListPath);
                } else {
                    $versionList = '$InstallData = ' .  var_export($versionArray, true) . ';';

                    $forcePclZipString = '$forcePclZip = ';
                    $forcePclZipString .= $this->forcePclZip ? 'true' : 'false';
                    $forcePclZipString .= ';';

                    $forceFopenString = '$forceFopen = ';
                    $forceFopenString .= $this->forceFopen ? 'true' : 'false';
                    $forceFopenString .= ';';

                    $devModeString = '$devMode = ';
                    $devModeString .= $this->devMode ? 'true' : 'false';
                    $devModeString .= ';';

                    $fields = array(
                        '/* [[+ForcePclZip]] */' => $forcePclZipString,
                        '/* [[+ForceFopen]] */' => $forceFopenString,
                        '/* [[+InstallData]] */' => $versionList,
                        '/* [[+devMode]] */' => $devModeString,
                        '[[+ugm_progress_path]]' => $this->progressFilePath,
                        '[[+ugm_progress_url]]' => $this->progressFileURL,
                    );

                    $fileContent = $this->modx->getChunk('UpgradeMODXSnippetScriptSource');
                    $fileContent = str_replace(array_keys($fields), array_values($fields), $fileContent);

                    if (fwrite($fp, $fileContent) === false) {
                        $this->modx->log(modX::LOG_LEVEL_ERROR, 'fwrite Failed in upgrademodx.class.php');
                    }
                    fclose($fp);
                }
            } else {
                $this->setError($this->modx->lexicon('ugm_could_not_open') . ' ' . MODX_BASE_PATH . 'upgrade.php' .
                ' ' .
                $this->modx->lexicon('ugm_for_writing'));
            }
        }

        public function getJSONFromGitHub($method, $timeout = 6, $tries = 2) {
            $this->clearErrors();
            $data = '';
            $url = 'https://api.github.com/repos/modxcms/revolution/tags';
            // ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 6.0)');
            if ($method == 'curl') {
                $data =  $this->curlGetData($url, true, $timeout, $tries);
            } else {
                $data =  $this->fopenGetData($url, true, $timeout, $tries);
            }

            $pos = strpos($data, 'API rate limit exceeded for');
            if ($pos !== false) {
                $this->setError('(GitHub -- ' . $method . ') ' . substr($data, $pos, 38));
                $data = false;
            }
            return $data === false? false : strip_tags($data);
        }

        public function finalizeVersionArray($contents, $plOnly = true, $versionsToShow = 5) {
            $currentVersion = $this->modx->getOption('settings_version', null);
            $contents = utf8_encode($contents);
            $contents = $this->modx->fromJSON($contents);
            if (empty($contents)) {
                $this->setError($this->modx->lexicon('ugm_json_decode_failed'));
                return false;
            }


             /* remove non-pl version objects if plOnly is set, and remove MODX 2.5.3 */
                foreach ($contents as $key => $content) {
                    $name = substr($content['name'], 1);
                    if ($plOnly && strpos($name, 'pl') === false) {
                        unset($contents[$key]);
                        continue;
                    }
                    if (strpos($name, '2.5.3-pl') !== false) {
                        unset($contents[$key]);
                    }
                }
                $contents = array_values($contents); // 'reindex' array


            /* GitHub won't necessarily have them in the correct order.
               Sort them with a Custom insertion sort since they will
               be almost sorted already */

            /* Make sure we don't access an invalid index */
            $versionsToShow = min($versionsToShow, count($contents));

            /* Make sure we show at least one */
            $versionsToShow = !empty($versionsToShow) ? $versionsToShow : 1;

            /* Sort by version */
            $count = count($contents);
            for ($i = 0; $i < $count; $i++) {
                $element = $contents[$i];
                $j = $i;
                while ($j > 0 && (version_compare($contents[$j - 1]['name'], $element['name']) < 0)) {
                    $contents[$j] = $contents[$j - 1];
                    $j = $j - 1;
                }
                $contents[$j] = $element;
            }

            /* Truncate to $versionsToShow but extend to show current version
               plus one previous version */

            $versionArray = array();
            $i = 1;
            $currentFound = false;
            foreach ($contents as $version) {
                $name = substr($version['name'], 1);
                $compare = version_compare($currentVersion, $name);

                $shortVersion = strtok($name, '-');
                $url = 'https://modx.s3.amazonaws.com/releases/' . $shortVersion . '/modx-' . $name . '.zip';
                // OLD  $url = 'https://modx.com/download/direct?id=modx-' . $name . '.zip';
                $versionArray[$name] = array(
                    'tree' => 'Revolution',
                    'name' => 'MODX Revolution ' . htmlentities($name),
                    'link' => $url,
                    'location' => 'setup/index.php',
                    'selected' => false,
                    'current' => $compare === 0 ? true : false,
                );

                if ($currentFound && ($i >= ($versionsToShow))) {
                    break;
                }

                if ($compare >= 0) {
                    $currentFound = true;
                    $i++;
                    continue;
                }
                $i++;
            }


            /* Select oldest X.X.0 version newer than current version or
              latest if there isn't one. */
            reset($versionArray);
            $latest = key($versionArray);

            /* Reverse array so we can stop at the first one that
               fits the criteria */
            $versionArray = array_reverse($versionArray, true);
            $selectedOne = false;
            foreach ($versionArray as $key => $value) {

                $pattern = "/\d+\.\d+\.0/";
                /* If it's a .0 version newer than the current version, select it */
                if (preg_match($pattern, $key)) {
                    if (version_compare($key, $currentVersion) > 0) {
                        $versionArray[$key]['selected'] = true;
                        $selectedOne = true;
                        break;
                    }
                }
            }

            /* No .0 version - select latest version */
            if (!$selectedOne) {
                $versionArray[$latest]['selected'] = true;
            }

            /* Un-reverse it */
            $this->versionArray = array_reverse($versionArray, true);
            return $this->versionArray;
        }

        public function updateLatestVersion($versionArray) {
            if ($this->devMode) {
                return;
            }
            $latest = reset($versionArray);
            $this->latestVersion = substr($latest['name'], 16);
        }

        public function updateSnippetProperties($lastCheck, $latestVersion ) {
            $snippet = $this->modx->getObject('modSnippet', array('name' => 'UpgradeMODXWidget'));
            if ($snippet) {
                $properties = $snippet->get('properties');
                $properties['lastCheck']['value'] = strftime('%Y-%m-%d %H:%M:%S', $lastCheck);
                $properties['latestVersion']['value'] = $latestVersion;
                $snippet->setProperties($properties);
                $snippet->save();
            }

        }
        public function updateVersionListFile() {
            $path = $this->versionListPath;
            $this->mmkDir($path);
            $versionList = var_export($this->versionArray, true);

            $fp = @fopen($this->versionListPath . 'versionlist', 'w');
            if ($fp) {
                fwrite($fp, '<' . '?p' . "hp\n" . '$InstallData = ' . $versionList . ';');
                fclose($fp);
            } else {
                $this->setError($this->modx->lexicon('ugm_could_not_open') .
                    ' ' . $path . 'versionlist ' . ' ' .
                    $this->modx->lexicon('ugm_for_writing'));
            }

        }

        public function getVersionListPath() {
            return $this->versionListPath;
        }

        public function curlGetData($url, $returnData = false, $timeout = 6, $tries = 6 ) {
            $username = $this->github_username;
            $token = $this->github_token;
            $retVal = false;
            $errorMsg = '(' . $url . ' - curl) ' . $this->modx->lexicon('failed');
            $ch = curl_init();
            if ($this->verifyPeer) {
                $certPath = MODX_CORE_PATH . 'components/upgrademodx/cacert.pem';
                curl_setopt($ch, CURLOPT_CAINFO, $certPath);
            }
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0)");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, $returnData);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_NOBODY, !$returnData);
            if (strpos($url, 'github') !== false) {

                if (!empty($username) && !empty($token)) {
                    curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $token);
                }
            }

            $i = $tries;

            while ($i--) {
                $retVal = @curl_exec($ch);
                if (!empty($retVal)) {
                    break;
                }
            }

            if (empty($retVal) || ($retVal === false)) {
                $e = curl_error($ch);
                if (!empty($e)) {
                    $errorMsg = $e;
                }
                $this->setError($errorMsg);
            } elseif (! $returnData) { /* Just checking for existence */
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $retVal = $statusCode === 200;
                if (! $retVal) {
                    $this->setError($this->modx->lexicon('ugm_no_such_version'));
                }
            }
            curl_close($ch);
            return $retVal;
        }

        public function fopenGetData($url, $returnData = false, $timeout = 6, $tries = 6) {
            $username = $this->modx->getOption('github_username');
            $token = $this->modx->getOption('github_token');
            $errorMsg = '(' . $url . ' - fopen) ' . $this->modx->lexicon('failed');
            $retVal = false;
            $opts = array(
                'http' => array(
                    'method' => 'GET',
                    'timeout' => $timeout,
                    'max_redirects' => 1,
                    'ignore_errors' => true,
                    'user_agent' => 'Mozilla/4.0 (compatible; MSIE 6.0)',

                )
            );

            if (!empty($username) && !empty($token)) {
                $opts['http']['header'] = "Authorization: Basic " . base64_encode($username . ':' . $token);
            }
            $ctx = stream_context_create($opts);

            $i = $tries;

            $old = @ini_set('default_socket_timeout', $timeout);

            while ($i--) {
                if (!$returnData) {
                    $retVal = @fopen($url, 'r');
                    // $x = $http_response_header;
                    if ($retVal) {
                        @fclose($retVal);
                        $retVal = true;
                        break;
                    } else {
                        $timeout += 2;
                        ini_set('default_socket_timeout', $timeout);
                    }
                } else {
                    $retVal = @file_get_contents($url, false, $ctx);
                    // $x = $http_response_header;
                }
            }

            @ini_set('default_socket_timeout', $old);

        if (!$retVal) {
            $this->setError($errorMsg);
        }
        return $retVal;
    }


        public function downloadable($version, $method = 'curl', $timeout = 6, $tries = 2) {
            if ($this->devMode) {
                return true;
            }
            $this->clearErrors();
            $shortVersion = strtok($version, '-');
            $downloadUrl = 'https://modx.s3.amazonaws.com/releases/' . $shortVersion . '/modx-' . $version . '.zip';
            // $downloadUrl = 'https://modx.com/download/direct/modx-' . $version . '.zip';
            if ($method == 'curl') {
                $downloadable =  $this->curlGetData($downloadUrl, false, $timeout, $tries);
            } else {
                $downloadable =  $this->fopenGetData($downloadUrl, false, $timeout, $tries);
            }

            return $downloadable;
        }


        /**
         * @param $lastCheck string = time of previous check
         * @param $interval - interval between checks
         * @return bool true if time to check, false if not
         */
        public function timeToCheck($lastCheck, $interval = '+1 week') {
            if (empty($lastCheck)) {
                $retVal = true;
            } else {
                $interval = strpos($interval, '+') === false ? '+' . $interval : $interval;
                $retVal = time() > strtotime($lastCheck . ' ' . $interval);
            }
            return $retVal;
        }

        public function clearErrors() {
            $this->errors = array();
        }

        public function getLatestVersion() {
            return $this->latestVersion;
        }

        public function setError($msg) {
            $this->errors[] = $msg;
        }

        public function getErrors() {
            return $this->errors;
        }


        public function upgradeAvailable($currentVersion, $plOnly = false, $versionsToShow = 5, $method = 'curl') {

            $retVal = $this->getJSONFromGitHub($method, $this->gitHubTimeout, $this->attempts);

            if ($retVal !== false) {
                $retVal = $this->finalizeVersionArray($retVal, $plOnly, $versionsToShow);
                if ($retVal !== false) {
                    $this->updateLatestVersion($retVal);
                    $this->updateSnippetProperties(time(), $this->latestVersion);
                    $this->updateVersionListFile();
                }
            }

            if ($retVal === false) {
                $this->setError($this->modx->lexicon('ugm_no_version_list_from_github'));
            }

            $latestVersion = $this->latestVersion;

            if (!empty($this->errors)) {
                $upgradeAvailable = false;
            } else {

                /* See if the latest version is newer than the current version */
                $newVersion = version_compare($currentVersion, $latestVersion) < 0;
                $downloadable = $this->downloadable($latestVersion, $method, $this->modxTimeout, $this->attempts);
                $upgradeAvailable = $newVersion && $downloadable;
            }

            return $upgradeAvailable;
        }

        public function mmkDir($folder, $perm = 0755) {
            if (!is_dir($folder)) {
                mkdir($folder, $perm, true);
            }
        }
    }
}
