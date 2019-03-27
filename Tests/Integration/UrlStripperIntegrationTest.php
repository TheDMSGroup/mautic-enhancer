<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/20/19
 * Time: 9:37 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\UrlStripperIntegration;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class UrlStripperIntegrationTest extends TestCase
{
    protected function setup()
    {
        defined('MAUTIC_ENV') || define('MAUTIC_ENV', 'test');
    }

    public function testDoEnhancementNew()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getFieldValue', 'addUpdatedField'])
            ->getMock();

        $leadObserver->expects($this->any())
            ->method('getFieldValue')
            ->will($this->returnValueMap([
                ['consent_url_clean', null, null],
                ['consent_url_dirty', null, 'https://www.example.com?querystring='],
            ]));

        $leadObserver->expects($this->once())
            ->method('addUpdatedField')
            ->with('consent_url_clean', 'https://www.example.com', null);

        $mockIntegration = $this->getMockBuilder(UrlStripperIntegration::class)
            ->setMethods(['getIntegrationSettings'])
            ->getMock();

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getFeatureSettings'])
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getFeatureSettings')
            ->willReturn(['original_consent_url' => 'consent_url_dirty']);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationSettings')
            ->willReturn($mockSettings);

        $mockLogger = $this->createMock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result');
    }

    public function testDoEnhancementCleaned()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getFieldValue', 'addUpdatedField'])
            ->getMock();

        $leadObserver->expects($this->any())
            ->method('getFieldValue')
            ->willReturnMap([
                ['consent_url_clean', null, 'https://www.example.com'],
                ['consent_url_dirty', null, 'https://www.example.com?querystring='],
            ]);

        $leadObserver->expects($this->never())
            ->method('addUpdatedField');

        $mockIntegration = $this->getMockBuilder(UrlStripperIntegration::class)
            ->setMethods(['getIntegrationSettings'])
            ->getMock();

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getFeatureSettings'])
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getFeatureSettings')
            ->willReturn(['original_consent_url' => 'consent_url_dirty']);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationSettings')
            ->willReturn($mockSettings);

        $mockLogger = $this->createMock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $this->assertFalse($mockIntegration->doEnhancement($leadObserver), 'Unexpected result');
    }
}
