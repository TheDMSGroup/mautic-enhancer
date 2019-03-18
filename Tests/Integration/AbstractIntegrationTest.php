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
use PHPUnit\Framework\TestCase;

class AbstractIntegrationTest extends TestCase
{
    public function testApplyCost()
    {
        $lead              = new Lead();
        $lead->attribution = 1.00;

        $mock = $this->getMockForAbstractClass('AbstractEnhancerIntegration');
        $mock->expects($this->any())
            ->method('getCostPerEnhancement')
            ->will($this->returnValue(0.1));

        $expected = 0.9;
        $mock->applyCost($lead);
        $this->assertEquals($expected, $lead->getAttribution(), 'Unexpected attribution on lead');
    }

    public function testGetDisplayName()
    {
        $mock = $this->getMockForAbstractClass('AbstractEnhancerIntegration');
        $mock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('TestName'));

        $expected = 'Test Name Data Enhancer';

        $this->assertEquals($expected, $mock->getDisplayName(), 'Unexpected display name');
    }

    public function testGetSupportedFeatures()
    {
        $mock = $this->getMockForAbstractClass('AbstractEnhancerIntegration');

        $expected = ['push_lead'];

        $this->assertEquals($expected, $mock->getSupportedFeatures(), 'Unexpected supported features');
    }

    public function testPushLead()
    {
        $mock = $this->getMockForAbstractClass('AbstractEnhancerIntegration');
        $mock->expects($this->any())
            ->method('doEnhancement')
            ->will($this->returnValue(true))
            ->method('saveLead')
            ->will($this->returnValue(null));

        $this->assertTrue($mock->pushLead(), 'Unexpected push result');
    }

    public function testGetCostPerEnhancement()
    {
        $mock = $this->getMockForAbstractClass('AbstractEnhancerIntegration');

        $expected = null;

        $this->assertEquals($expected, $mock->getCostPerEnhancement(), 'Unexpected cost per enhancement');
    }

    public function testGetId()
    {
        $mock = $this->getMockForAbstractClass('AbstractEnhancerIntegration');
        $mock->expects($this->any())
            ->method('getIntegrationsSettings')
            ->will($this->returnValue(new Integration()));

        $expected = null;
        $this->assertEquals($expected, $mock->getId(), 'Unexpected id');
    }
}
