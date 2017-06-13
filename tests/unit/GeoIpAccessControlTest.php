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
    const SI_IP = '46.122.0.0';

    protected function _after()
    {
        //reset the DI container
        Yii::$app->set('request', null);
        Yii::$container->set(GeoIP::class, null);
    }

    private function stubGetIp(int $count)
    {
        $requestStub = Stub::make(Request::class, [
            'getUserIP' => Stub::exactly($count, function () {
                return self::HR_IP;
            }),
        ]);
        Yii::$app->set('request', $requestStub);
    }

    private function stubGetIsoCode(int $count)
    {
        $geoIpStub = Stub::make(GeoIP::class, [
            'ip' => Stub::exactly($count, function ($ip) {
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

    private function expectAllow(GeoIpAccessControl $filter)
    {
        $this->assertTrue($filter->beforeAction(null));
    }

    private function expectDeny(GeoIpAccessControl $filter)
    {
        $this->expectException(ForbiddenHttpException::class);
        $filter->beforeAction(null);
    }

    #region NPath testing

    public function testFilter_DefaultGetIp_DefaultGetIsoCode_FilterAllow_ExpectsAllowed()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => GeoIpFilterMode::ALLOW,
        ]);

        $this->expectAllow($filter);
    }

    public function testFilter_DefaultGetIp_DefaultGetIsoCode_FilterAllow_ExpectsDenied()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => ['SI'],
            'filterMode' => GeoIpFilterMode::ALLOW,
        ]);

        $this->expectDeny($filter);
    }

    public function testFilter_CustomGetIp_DefaultGetIsoCode_FilterAllow_ExpectsAllowed()
    {
        $this->stubGetIp(0);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIp' => function () {
                return self::HR_IP;
            },
        ]);

        $this->expectAllow($filter);
    }

    public function testFilter_CustomGetIp_DefaultGetIsoCode_FilterAllow_ExpectsDenied()
    {
        $this->stubGetIp(0);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => ['SI'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIp' => function () {
                return self::HR_IP;
            },
        ]);

        $this->expectDeny($filter);
    }


    public function testFilter_DefaultGetIp_CustomGetIsoCode_FilterAllow_ExpectsAllowed()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(0);

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIsoCode' => function ($ip) {
                return 'HR';
            },
        ]);

        $this->expectAllow($filter);
    }

    public function testFilter_DefaultGetIp_CustomGetIsoCode_FilterAllow_ExpectsDenied()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(0);

        $filter = $this->createFilter([
            'isoCodes' => ['SI'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIsoCode' => function ($ip) {
                return 'HR';
            },
        ]);

        $this->expectDeny($filter);
    }

    public function testFilter_CustomGetIp_CustomGetIsoCode_FilterAllow_ExpectsAllowed()
    {
        $this->stubGetIp(0);
        $this->stubGetIsoCode(0);

        $filter = $this->createFilter([
            'isoCodes' => ['US'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIp' => function () {
                return '127.0.0.1';
            },
            'getIsoCode' => function ($ip) {
                if ($ip === '127.0.0.1') {
                    return 'US';
                }

                return 'RU';
            },
        ]);

        $this->expectAllow($filter);
    }

    public function testFilter_CustomGetIp_CustomGetIsoCode_FilterAllow_ExpectsDenied()
    {
        $this->stubGetIp(0);
        $this->stubGetIsoCode(0);

        $filter = $this->createFilter([
            'isoCodes' => ['RU'],
            'filterMode' => GeoIpFilterMode::ALLOW,
            'getIp' => function () {
                return '127.0.0.1';
            },
            'getIsoCode' => function ($ip) {
                if ($ip === '127.0.0.1') {
                    return 'US';
                }

                return 'RU';
            },
        ]);

        $this->expectDeny($filter);
    }


    public function testFilter_DefaultGetIp_DefaultGetIsoCode_FilterDeny_ExpectsAllowed()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => ['US'],
            'filterMode' => GeoIpFilterMode::DENY,
        ]);

        $this->expectAllow($filter);
    }

    public function testFilter_DefaultGetIp_DefaultGetIsoCode_FilterDeny_ExpectsDenied()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => GeoIpFilterMode::DENY,
        ]);

        $this->expectDeny($filter);
    }

    public function testFilter_CustomGetIp_DefaultGetIsoCode_FilterDeny_ExpectsAllowed()
    {
        $this->stubGetIp(0);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => ['US'],
            'filterMode' => GeoIpFilterMode::DENY,
            'getIp' => function () {
                return self::HR_IP;
            },
        ]);

        $this->expectAllow($filter);
    }

    public function testFilter_CustomGetIp_DefaultGetIsoCode_FilterDeny_ExpectsDenied()
    {
        $this->stubGetIp(0);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => GeoIpFilterMode::DENY,
            'getIp' => function () {
                return self::HR_IP;
            },
        ]);

        $this->expectDeny($filter);
    }


    public function testFilter_DefaultGetIp_CustomGetIsoCode_FilterDeny_ExpectsAllowed()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(0);

        $filter = $this->createFilter([
            'isoCodes' => ['RU'],
            'filterMode' => GeoIpFilterMode::DENY,
            'getIsoCode' => function ($ip) {
                return 'HR';
            },
        ]);

        $this->expectAllow($filter);
    }

    public function testFilter_DefaultGetIp_CustomGetIsoCode_FilterDeny_ExpectsDenied()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(0);

        $filter = $this->createFilter([
            'isoCodes' => ['HR'],
            'filterMode' => GeoIpFilterMode::DENY,
            'getIsoCode' => function ($ip) {
                return 'HR';
            },
        ]);

        $this->expectDeny($filter);
    }

    public function testFilter_CustomGetIp_CustomGetIsoCode_FilterDeny_ExpectsAllowed()
    {
        $this->stubGetIp(0);
        $this->stubGetIsoCode(0);

        $filter = $this->createFilter([
            'isoCodes' => ['RU'],
            'filterMode' => GeoIpFilterMode::DENY,
            'getIp' => function () {
                return '127.0.0.1';
            },
            'getIsoCode' => function ($ip) {
                if ($ip === '127.0.0.1') {
                    return 'US';
                }

                return 'RU';
            },
        ]);

        $this->expectAllow($filter);
    }

    public function testFilter_CustomGetIp_CustomGetIsoCode_FilterDeny_ExpectsDenied()
    {
        $this->stubGetIp(0);
        $this->stubGetIsoCode(0);

        $filter = $this->createFilter([
            'isoCodes' => ['US'],
            'filterMode' => GeoIpFilterMode::DENY,
            'getIp' => function () {
                return '127.0.0.1';
            },
            'getIsoCode' => function ($ip) {
                if ($ip === '127.0.0.1') {
                    return 'US';
                }

                return 'RU';
            },
        ]);

        $this->expectDeny($filter);
    }

    #endregion

    public function testFilter_WhenIsoCodesIsString()
    {
        $this->stubGetIp(1);
        $this->stubGetIsoCode(1);

        $filter = $this->createFilter([
            'isoCodes' => 'HR',
            'filterMode' => GeoIpFilterMode::ALLOW,
        ]);

        $this->assertSame(['HR'], $filter->isoCodes);
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

    public function testFilter_InvalidIsoCodesType()
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
        ]);

        $this->assertSame(GeoIpFilterMode::ALLOW, $filter->filterMode);
    }

    private function createFilter(array $config = []): GeoIpAccessControl
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::createObject(GeoIpAccessControl::class, [$config]);
    }
}
