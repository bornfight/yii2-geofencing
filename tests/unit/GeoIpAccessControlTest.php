<?php

namespace tests;

use Codeception\Util\Stub;
use degordian\geofencing\enums\GeoIpFilterMode;
use degordian\geofencing\filters\GeoIpAccessControl;
use lysenkobv\GeoIP\GeoIP;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;
use yii\web\Request;

class GeoIpAccessControlTest extends \Codeception\Test\Unit
{
    /**
     * @var \tests\UnitTester
     */
    protected $tester;

    const HR_IP = '161.53.72.120'; //Faculty of Electrical Engineering and Computing, University of Zagreb

    protected function _before()
    {
    }

    protected function _after()
    {
        //reset the DI container
        Yii::$app->set('request', null);
        Yii::$container->set(GeoIP::class, null);
    }

    private function stubGetIp()
    {
        $requestStub = Stub::make(Request::class, [
            'getUserIP' => Stub::once(function () {
                return self::HR_IP;
            }),
        ]);
        Yii::$app->set('request', $requestStub);
    }

    private function stubGetIsoCode()
    {
        $geoIpStub = Stub::make(GeoIP::class, [
            'ip' => Stub::once(function ($ip) {
                $result = new \stdClass();
                if ($ip === self::HR_IP) {
                    $result->isoCode = 'HR';
                } else {
                    $result->isoCode = 'SI';
                }

                return $result;
            }),
        ]);
        Yii::$container->set(GeoIP::class, $geoIpStub);
    }

    public function testFilter_WhenUsingDefaultFunctoins()
    {
        $this->stubGetIp();
        $this->stubGetIsoCode();

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => GeoIpFilterMode::ALLOW,
        ]);

        $this->assertTrue($filter->beforeAction(null));
    }

    public function testFilter_WhenUsingDefaultGetIsoCode()
    {
        $this->stubGetIsoCode();

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => GeoIpFilterMode::ALLOW,
        ]);

        $this->assertTrue($filter->beforeAction(null));
    }

    public function testFilter_WhenUsingCustomGetIp()
    {
        $requestStub = Stub::make(Request::class, [
            'getUserIP' => Stub::never(function () {
                return self::HR_IP;
            }),
        ]);
        Yii::$app->set('request', $requestStub);

        $filter = $this->createFilter([
            'isoCodes' => ['SI', 'HR'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIp' => function () {
                return self::HR_IP;
            },
        ]);

        $this->stubGetIsoCode();

        $this->assertTrue($filter->beforeAction(null));
    }

    public function testFilter_ModeAllow_LocationIsAllowed()
    {
        $filter = $this->createFilter([
            'isoCodes' => ['SI', 'HR'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIp' => function () {
                return self::HR_IP;
            },
            'getIsoCode' => function ($ip) {
                if ($ip === self::HR_IP) {
                    return 'HR';
                }

                return 'NA';
            },
        ]);

        $this->assertTrue($filter->beforeAction(null));
    }

    public function testFilter_ModeDeny_LocationIsNotAllowed()
    {
        $filter = $this->createFilter([
            'isoCodes' => ['SI', 'HR'],
            'filterMode' => GeoIpFilterMode::DENY,
            'getIp' => function () {
                return self::HR_IP;
            },
            'getIsoCode' => function ($ip) {
                if ($ip === self::HR_IP) {
                    return 'HR';
                }

                return 'NA';
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
                return self::HR_IP;
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
        $this->expectException(InvalidConfigException::class);

        $filter = $this->createFilter([
            'isoCodes' => 12345,
        ]);

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
