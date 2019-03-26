<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/19/19
 * Time: 8:53 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Integration\AlcazarIntegration;
use PHPUnit\Framework\TestCase;

class AlcazarIntegrationTest extends TestCase
{
    public function testDoEnhancement()
    {
        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getFeatureSettings'])
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getFeatureSettings')
            ->willReturn(['dnc' => 1, 'extended' => 1, 'output' => 'json']);

        $mockIntegration = $this->getMockBuilder(AlcazarIntegration::class)
            ->setMethods(['getIntegrationSettings', 'makeRequest', 'getKeys'])
            ->getMock();

        $mockIntegration->expects($this->exactly(3))
            ->method('getIntegrationSettings')
            ->will($this->returnValue($mockSettings));

        $mockIntegration->expects($this->once())
            ->method('getKeys')
            ->will($this->returnValue(['apikey' => 'dummy', 'server' => 'http://example.com']));

        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getPhone', 'getFieldValue'])
            ->getMock();

        $leadObserver->expects($this->any())
            ->method('getPhone')
            ->willReturn('19876543210');

        $leadObserver->expects($this->exactly(3))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['alcazar_lrn', '17546109998', null],
                ['alcazar_linetype', 'WIRELESS', null],
                ['alcazar_dnc', 'FALSE', null]
            );

        $makeRequestReturn = [
            'LRN'      => '17546109998',
            'LINETYPE' => 'WIRELESS',
            'DNC'      => 'FALSE',
        ];

        $mockIntegration->expects($this->once())
            ->method('makeRequest')
            ->willReturn($makeRequestReturn);

        $result = $mockIntegration->doEnhancement($leadObserver);
        $this->assertTrue($result, 'Unexpected result');
    }
}
