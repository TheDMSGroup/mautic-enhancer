<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:08 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractNeustarIntegration;
use PHPUnit\Framework\TestCase;

class AbstractNeustarIntegrationTest extends TestCase
{
    public function testDoEnhancement()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue'])
            ->getMock();

        $mockIntegration = $this->getMockBuilder(AbstractNeustarIntegration::class)
            ->setMethods(['getKeys', 'getNeustarElementId', 'getNeustarServiceKeys', 'getServiceIdData', 'getIntegrationSettings', 'processResponse', 'getNeustarIntegrationName', 'getEnhancerFieldArray'])
            ->getMock();

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getFeatureSettings'])
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getFeatureSettings')
            ->willReturn(['endpoint' => 'https://example.com']);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationSettings')
            ->willReturn($mockSettings);

        $keys = ['username' => 'test', 'password' => 'test', 'serviceId' => 'test'];
        $mockIntegration->expects($this->once())
            ->method('getKeys')
            ->willReturn($keys);

        $mockIntegration->expects($this->once())
            ->method('getNeustarElementId')
            ->willReturn(7);

        $mockIntegration->expects($this->once())
            ->method('getNeustarServiceKeys')
            ->willReturn([]);

        $mockIntegration->expects($this->any())
            ->method('getServiceIdData')
            ->willReturn([]);

        $mockIntegration->expects($this->once())
            ->method('processResponse')
            ->willReturn(true);

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }
}
