<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:07 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\GenderFromNameIntegration;
use MauticPlugin\MauticEnhancerBundle\Model\GenderNameModel;
use PHPUnit\Framework\TestCase;

class GenderFromNameIntegrationTest extends TestCase
{
    public function testDoEnhancement()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue', 'getFirstname'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('getFieldValue')
            ->with('gender');

        $leadObserver->expects($this->once())
            ->method('getfirstname')
            ->willReturn('first');

        $leadObserver->expects($this->once())
            ->method('addupdatedField')
            ->with('gender', 'X', null);

        $mockIntegration = $this->getMockBuilder(GenderFromNameIntegration::class)
            ->setMethods(['getIntegrationModel'])
            ->getMock();

        $mockModel = $this->getMockBuilder(GenderNameModel::class)
            ->setMethods(['getGender'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getGender')
            ->willReturn('X');

        $mockIntegration->expects($this->any())
            ->method('getIntegrationModel')
            ->willReturn($mockModel);

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }
}
