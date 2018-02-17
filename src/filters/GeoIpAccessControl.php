<?php

namespace degordian\geofencing\filters;

use degordian\geofencing\enums\GeoIpFilterMode;
use lysenkobv\GeoIP\GeoIP;
use lysenkobv\GeoIP\Result;
use Yii;
use yii\base\ActionFilter;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;

class GeoIpAccessControl extends ActionFilter
{
    /**
     * @var array|string A list of ISO 3166-1 alpha-2 ISO codes. You can find a full reference here:
     *     https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
     */
    public $isoCodes = [];

    /**
     * @var string The selected filter mode
     */
    public $filterMode = GeoIpFilterMode::ALLOW;

    /**
     * @var callable Provide a custom function to get the clients IP address (for example, from a database).
     * The signature of the callable should be:
     * ```php
     * function(): string { }
     * ``
     * The default is Yii::$app->request->getUserIP()
     */
    public $getIp;

    /** @var callable Provide a custom function to get the iso code from a given IP address.
     * The signature of the callable should be:
     * ```php
     * function(string $ip): string {
     *   //$ip - The ip address returned by $getIp
     * }
     * ```
     * The default uses https://github.com/lysenkobv/yii2-geoip
     */
    public $getIsoCode;

    public $message;

    public function init()
    {
        parent::init();
        if (is_string($this->isoCodes)) {
            $this->isoCodes = [$this->isoCodes];
        }
        if (is_array($this->isoCodes) === false) {
            throw new InvalidConfigException('Public property allowedIsoCodes must be a string or array');
        }
        if (empty($this->isoCodes)) {
            throw new InvalidConfigException('Public property $isoCodes must have at least one iso code');
        }
        if ($this->filterMode !== GeoIpFilterMode::ALLOW && $this->filterMode !== GeoIpFilterMode::DENY) {
            throw new InvalidConfigException('The $filterMode must be either GeoIpFilterMode::ALLOW or GeoIpFilterMode::DENY');
        }
        if ($this->message === null) {
            $this->message = 'This feature is not available in your country';
        }
        $this->isoCodes = array_map('strtoupper', $this->isoCodes);
    }

    public function beforeAction($action)
    {
        if ($this->getIp === null) {
            $ip = Yii::$app->request->getUserIP();
        } else {
            $ip = call_user_func($this->getIp);
        }

        if ($this->getIsoCode === null) {
            /** @var GeoIP $geoIp */
            $geoIp = Yii::createObject(GeoIP::class);
            /** @var Result $ipInfo */
            $ipInfo = $geoIp->ip($ip);
            $isoCode = $ipInfo->isoCode;
        } else {
            $isoCode = call_user_func($this->getIsoCode, $ip);
        }

        if ($this->filterMode === GeoIpFilterMode::ALLOW && in_array($isoCode, $this->isoCodes, true) === false) {
            throw new ForbiddenHttpException($this->message);
        }

        if ($this->filterMode === GeoIpFilterMode::DENY && in_array($isoCode, $this->isoCodes, true)) {
            throw new ForbiddenHttpException($this->message);
        }

        return parent::beforeAction($action);
    }
}
