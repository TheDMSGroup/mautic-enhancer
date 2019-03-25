<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/15/19
 * Time: 11:46 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
        $mockEnhancer = $this->getMockBuilder(AbstractEnhancerIntegration::class)
            ->setMethods(['getEnhancerFieldArray', 'getName', 'getAuthenticationType', 'doEnhancement', 'saveLead', 'getCampaign', 'getIntegrationSettings'])
            ->getMock();

        $mockLogger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('debug')
            ->will($this->returnArgument(null));

        $mockEnhancer->setLogger($mockLogger);

        $mockDispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $mockDispatcher->expects($this->any())
            ->method('dispatch')
            ->will($this->returnValue(null));

        $mockEnhancer->setDispatcher($mockDispatcher);

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getIsPublished')
            ->will($this->returnValue(true));

        $mockCampaign = $this->getMockBuilder(Campaign::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $mockEnhancer->expects($this->any())
            ->method('doEnhancement')
            ->will($this->returnValue(true));

        $mockEnhancer->expects($this->any())
            ->method('saveLead')
            ->will($this->returnValue(null));

        $mockEnhancer->expects($this->any())
            ->method('getCampaign')
            ->will($this->returnValue($mockCampaign));

        $mockEnhancer->expects($this->any())
            ->method('getIntegrationSettings')
            ->will($this->returnValue($mockSettings));

        //->method('getKeys')
        //->will($this->returnValue([]));

        $dummyLead = new Lead();
        $this->assertTrue($mockEnhancer->pushLead($dummyLead), 'Unexpected push result');
    }

    public function testGetCostPerEnhancement()
    {
        $mock = $this->getMockForAbstractClass(AbstractEnhancerIntegration::class);

        $expected = null;

        $this->assertEquals($expected, $mock->getCostPerEnhancement(), 'Unexpected cost per enhancement');
    }
}
