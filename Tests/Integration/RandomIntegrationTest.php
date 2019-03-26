<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/20/19
 * Time: 8:06 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\RandomIntegration;
use PHPUnit\Framework\TestCase;

class RandomIntegrationTest extends TestCase
{
    public function testDoEnhancementNew()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('addUpdatedField')
            ->with('random', $this->greaterThan(0) && $this->lessThan(101), null);

        $mockIntegration = $this->getMockBuilder(RandomIntegration::class)
            ->setMethodsExcept(['doEnhancement'])
            ->getMock();

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getFeatureSettings'])
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getFeatureSettings')
            ->willReturn(['random_field_name' => 'random']);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationSettings')
            ->will($this->returnValue($mockSettings));

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result');
    }

    public function testDoEnhancementExisting()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getFieldValue'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('getFieldValue')
            ->willReturn(49);

        $mockIntegration = $this->getMockBuilder(RandomIntegration::class)
            ->setMethodsExcept(['doEnhancement'])
            ->getMock();

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getFeatureSettings'])
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getFeatureSettings')
            ->willReturn(['random_field_name' => 'random']);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationSettings')
            ->will($this->returnValue($mockSettings));

        $this->assertFalse($mockIntegration->doEnhancement($leadObserver), 'Unexpected result');
    }
}
