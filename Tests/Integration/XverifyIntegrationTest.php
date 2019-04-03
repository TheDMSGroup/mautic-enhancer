<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:10 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\XverifyIntegration;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class XverifyIntegrationTest extends TestCase
{
    protected function setup()
    {
        defined('MAUTIC_ENV') || define('MAUTIC_ENV', 'test');
    }

    public function testDoEnhancement()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue'])
            ->getMock();

        $leadObserver->expects($this->any())
            ->method('getFieldValue')
            ->willReturnMap([
                ['email', null, 'test@example.com'],
                ['mobile', null, '9876543210'],
                ['phone', null, '1234567890'],
                ['companyphone', null, null],
                ['email_valid', null, null],
                ['homephone_valid', null, null],
                ['cellphone_valid', null, null]
            ]);

        $leadObserver->expects($this->exactly(3))
            ->method('addUpdatedField')
            ->withConsecutive(
                [$this->equalTo('email_valid'), $this->equalTo(1), $this->equalTo(null)],
                [$this->equalTo('cellphone_valid'), $this->equalTo(1), $this->equalTo(null)],
                [$this->equalTo('homephone_valid'), $this->equalTo(0), $this->equalTo(null)]
            );

        $mockIntegration = $this->getMockBuilder(XverifyIntegration::class)
            ->setMethods(['getIntegrationSettings', 'getKeys', 'makeCall', 'applyCost', 'getResponseStatus'])
            ->getMock();

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getFeatureSettings'])
            ->getMock();

        $featureSettings = [
            'leadFields' => ['email' => 'email', 'cellphone' => 'mobile', 'homephone' => 'phone', 'workphone' => 'companyphone'],
            'update_mautic' => ['email' => 1, 'phone' => 1, 'companyphone' => 1, 'mobile' => 1],
            'cost_per_enhancement' => 0,
            'installed' => ['email_valid', 'workphone_valid', 'cellphone_valid', 'homephone_valid']
        ];
        $mockSettings->expects($this->any())
            ->method('getFeatureSettings')
            ->willReturn($featureSettings);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationSettings')
            ->willReturn($mockSettings);

        $keys = ['apikey' => '', 'server' => ''];
        $mockIntegration->expects($this->any())
            ->method('getKeys')
            ->willReturn($keys);

        $params = $keys;
        $params['type'] = 'json';
        $mockIntegration->expects($this->any())
            ->method('makeCall')
            ->willReturnOnConsecutiveCalls(
                '{"email":{"status":"valid"}}',
                '{"phone":{"status":"valid"}}',
                '{"phone":{"status":"invalid"}}'
            );

        $mockIntegration->expects($this->exactly(3))
            ->method('getResponseStatus')
            ->willReturnMap([
                ['{"email":{"status":"valid"}}', 'email', 1],
                ['{"phone":{"status":"valid"}}', 'phone', 1],
                ['{"phone":{"status":"invalid"}}', 'phone', 0]
            ]);

        $mockIntegration->expects($this->any())
            ->method('applyCost')
            ->willReturn(true);

        $mockLogger = $this->createMock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }
}
