<?php

namespace tests;

use degordian\geofencing\enums\GeoIpFilterMode;
use degordian\geofencing\filters\GeoIpAccessControl;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;

class GeoIpAccessControlTest extends \Codeception\Test\Unit
{
    /**
     * @var \tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testFilter_ModeAllow_WhenLocationIsAllowed()
    {
        $filter = $this->createFilter([
            'isoCodes' => ['SI', 'HR'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIp' => function () {
                return '161.53.72.120'; //HR ip
            },
        ]);

        $this->assertTrue($filter->beforeAction(null));
    }

    public function testFilter_ModeDeny_WhenLocationIsNotAllowed()
    {
        $filter = $this->createFilter([
            'isoCodes' => ['SI', 'HR'],
            'filterMode' => GeoIpFilterMode::DENY,
            'getIp' => function () {
                return '161.53.72.120'; //HR ip
            },
        ]);

        $this->expectException(ForbiddenHttpException::class);
        $filter->beforeAction(null);
    }

    public function testFilter_WhenIsoCodesIsString_ExpectsAllow()
    {
        $filter = $this->createFilter([
            'isoCodes' => 'HR',
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIp' => function () {
                return '161.53.72.120'; //HR ip
            },
        ]);

        $this->assertTrue($filter->beforeAction(null));
    }

    public function testFilter_WhenIsoCodesIsString_ExpectsDeny()
    {
        $filter = $this->createFilter([
            'isoCodes' => 'HR',
            'filterMode' => GeoIpFilterMode::DENY,
            'getIp' => function () {
                return '161.53.72.120'; //HR ip
            },
        ]);

        $this->expectException(ForbiddenHttpException::class);
        $filter->beforeAction(null);
    }

    public function testFilter_WhenNoIsoCodesAreGiven_ExpectsException()
    {
        $this->expectException(InvalidConfigException::class);

        $filter = $this->createFilter();
    }

    public function testFilter_WhenGivenInvalidFilterMode_ExpectsException()
    {
        $this->expectException(InvalidConfigException::class);

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => 'foobar',
        ]);
    }

    public function testFilter_WhenUsingCustomGetIsoCodeFunction_ModeAllow_ExpectsAllow()
    {
        $filter = $this->createFilter([
            'isoCodes' => 'HR',
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIsoCode' => function ($ip) {
                return 'HR';
            },
        ]);

        $this->assertTrue($filter->beforeAction(null));
    }

    public function testFilter_WhenUsingCustomGetIsoCodeFunction_ModeAllow_ExpectsDeny()
    {
        $filter = $this->createFilter([
            'isoCodes' => 'HR',
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIsoCode' => function ($ip) {
                return 'SI';
            },
        ]);

        $this->expectException(ForbiddenHttpException::class);
        $filter->beforeAction(null);
    }

    public function testFilter_WhenUsingCustomGetIsoCodeFunction_ModeDeny_ExpectsAllow()
    {
        $filter = $this->createFilter([
            'isoCodes' => 'HR',
            'filterMode' => GeoIpFilterMode::DENY,
            'getIsoCode' => function ($ip) {
                return 'SI';
            },
        ]);

        $this->assertTrue($filter->beforeAction(null));
    }

    public function testFilter_WhenUsingCustomGetIsoCodeFunction_ModeDeny_ExpectsDeny()
    {
        $filter = $this->createFilter([
            'isoCodes' => 'SI',
            'filterMode' => GeoIpFilterMode::DENY,
            'getIsoCode' => function ($ip) {
                return 'SI';
            },
        ]);

        $this->expectException(ForbiddenHttpException::class);
        $filter->beforeAction(null);
    }

    public function testFilter_InvalidIsoCodes()
    {
        $filter = $this->createFilter([
            'isoCodes' => 12345,
        ]);

        $this->expectException(ForbiddenHttpException::class);
        $filter->beforeAction(null);
    }

    public function testFilter_NoFilterModeSet_DefaultFilterModeShouldBeAllow()
    {
        $filter = $this->createFilter([
            'isoCodes' => 'SI',
            'getIsoCode' => function ($ip) {
                return 'SI';
            },
        ]);

        $this->assertSame(GeoIpFilterMode::ALLOW, $filter->filterMode);
    }

    private function createFilter(array $config = []): GeoIpAccessControl
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::createObject(GeoIpAccessControl::class, [$config]);
    }
}
