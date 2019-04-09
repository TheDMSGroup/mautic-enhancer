<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/20/19
 * Time: 9:07 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\PhoneToPartsIntegration;
use PHPUnit\Framework\TestCase;

class PhoneToPartsIntegrationTest extends TestCase
{
    public function testDoEnhancementWithPhone()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getPhone', 'addUpdatedField'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('getPhone')
            ->willReturn('9876543210');

        $leadObserver->expects($this->exactly(3))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['ptp_area_code', '987', null],
                ['ptp_prefix', '654', null],
                ['ptp_line_number', '3210', null]
            );

        $mockIntegration = $this->getMockBuilder(PhoneToPartsIntegration::class)
            ->setMethodsExcept(['doEnhancement'])
            ->getMock();

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Failed to split phone');
    }

    public function testDoEnhancementWithMobile()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getMobile', 'addUpdatedField'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('getMobile')
            ->willReturn('1234567890');

        $leadObserver->expects($this->exactly(3))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['ptp_area_code', '123', null],
                ['ptp_prefix', '456', null],
                ['ptp_line_number', '7890', null]
            );

        $mockIntegration   = $this->getMockBuilder(PhoneToPartsIntegration::class)
            ->setMethodsExcept(['doEnhancement'])
            ->getMock();

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Failed to split mobile number');
    }
}
