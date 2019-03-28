<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/15/19
 * Time: 11:46 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Doctrine\ORM\EntityManager;
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
        $leadObserver = $this->createMock(Lead::class);

        $mockIntegration = $this->getMockBuilder(AbstractEnhancerIntegration::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCampaign', 'getIntegrationSettings', 'saveLead', 'getAuthenticationType', 'getName', 'getEnhancerFieldArray', 'doEnhancement'])
            ->getMock();

        $mockCampaign = $this->createMock(Campaign::class);
        $mockIntegration->expects($this->any())
            ->method('getCampaign')
            ->willReturn($mockCampaign);

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getIsPublished'])
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getIsPublished')
            ->willReturn(true);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationSettings')
            ->willReturn($mockSettings);

        $mockIntegration->expects($this->any())
            ->method('doEnhancement')
            ->will($this->returnValue(true));

        $mockIntegration->expects($this->any())
            ->method('saveLead')
            ->will($this->returnValue(null));

        $mockLogger = $this->createmock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $mockEntityMgr = $this->createMock(EntityManager::class);
        $mockIntegration->setEntityManager($mockEntityMgr);

        $mockDispatcher = $this->createMock(EventDispatcher::class);
        $mockIntegration->setDispatcher($mockDispatcher);

        $this->assertTrue($mockIntegration->pushLead($leadObserver), 'Unexpected push result');
    }

    public function testGetCostPerEnhancement()
    {
        $mock = $this->getMockForAbstractClass(AbstractEnhancerIntegration::class);

        $expected = null;

        $this->assertEquals($expected, $mock->getCostPerEnhancement(), 'Unexpected cost per enhancement');
    }
}
