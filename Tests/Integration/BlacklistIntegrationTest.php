<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:03 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerBlacklist;
use MauticPlugin\MauticEnhancerBundle\Integration\BlacklistIntegration;
use MauticPlugin\MauticEnhancerBundle\Model\BlacklistModel;
use PHPUnit\Framework\TestCase;

class BlacklistIntegrationTest extends TestCase
{
    public function testDoEnhancement()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getPhone', 'getMobile', 'addUpdatedField'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('getPhone')
            ->willReturn('9876543210');

        $leadObserver->expects($this->never())
            ->method('getMobile');

        $leadObserver->expects($this->exactly(3))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['blacklist_result', 'test result value', null],
                ['blacklist_code', 'test code value', null],
                ['blacklist_wireless', 'test wireless value', null]
            );

        $mockIntegration = $this->getMockBuilder(BlacklistIntegration::class)
            ->setMethods(['getModel', 'getIntegrationSettings', 'phoneValidate'])
            ->getMock();

        $mockModel = $this->getMockBuilder(BlacklistModel::class)
            ->setMethods(['getRecord'])
            ->getMock();

        $mockRecord = $this->getMockBuilder(PluginEnhancerBlacklist::class)
            ->setMethods(['getCode', 'getResult', 'getWireless'])
            ->getMock();

        $mockRecord->expects($this->once())
            ->method('getCode')
            ->willReturn('test code value');

        $mockRecord->expects($this->once())
            ->method('getResult')
            ->willReturn('test result value');

        $mockRecord->expects($this->once())
            ->method('getWireless')
            ->willReturn('test wireless value');

        $mockModel->expects($this->once())
            ->method('getRecord')
            ->willReturn($mockRecord);

        $mockIntegration->expects($this->any())
            ->method('getModel')
            ->willReturn($mockModel);

        $mockSettings = $this->getMockBuilder(Integration::class)
            ->setMethods(['getFeatureSettings'])
            ->getMock();

        $mockSettings->expects($this->any())
            ->method('getFeatureSettings')
            ->willReturn(['age' => 300]);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationSettings')
            ->willReturn($mockSettings);

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }
}
