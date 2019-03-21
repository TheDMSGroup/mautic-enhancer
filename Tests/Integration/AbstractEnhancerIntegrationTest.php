<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/15/19
 * Time: 11:46 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

class AbstractEnhancerIntegrationTest extends TestCase
{
    public function testApplyCost()
    {
        $lead              = new Lead();
        $lead->attribution = 1.00;

        $mock = $this->getMockForAbstractClass(AbstractEnhancerIntegration::class);


        $expected = 1.0;
        $mock->applyCost($lead);
        $this->assertEquals($expected, $lead->getAttribution(), 'Unexpected attribution on lead');
    }

    public function testGetDisplayName()
    {
        $mock = $this->getMockForAbstractClass(AbstractEnhancerIntegration::class);
        $mock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('TestName'));

        $expected = 'Test Name Data Enhancer';

        $this->assertEquals($expected, $mock->getDisplayName(), 'Unexpected display name');
    }

    public function testGetSupportedFeatures()
    {
        $mock = $this->getMockForAbstractClass(AbstractEnhancerIntegration::class);

        $expected = ['push_lead'];

        $this->assertEquals($expected, $mock->getSupportedFeatures(), 'Unexpected supported features');
    }

    public function testPushLead()
    {
        $this->markTestSkipped('Trouble setting up underlying objects');

        $lead = new Lead();
        $mock = $this->getMockForAbstractClass(AbstractEnhancerIntegration::class);
        $mock->expects($this->any())
            ->method('doEnhancement')
            ->will($this->returnValue(false))
            ->method('getKeys')
            ->will($this->returnValue([]));
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->setLogger($logger);
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->setDispatcher($dispatcher);
        $settings = new Integration();
        $settings->setIsPublished(true);
        $mock->setIntegrationSettings($settings);

        $this->assertTrue($mock->pushLead($lead), 'Unexpected push result');
    }

    public function testGetCostPerEnhancement()
    {
        $mock = $this->getMockForAbstractClass(AbstractEnhancerIntegration::class);

        $expected = null;

        $this->assertEquals($expected, $mock->getCostPerEnhancement(), 'Unexpected cost per enhancement');
    }
}
