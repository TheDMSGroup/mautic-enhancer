<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/18/19
 * Time: 1:46 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\AgeFromBirthdateIntegration;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class AgeFromBirthdareIntegrationTest extends TestCase
{
    public function testDoEnhancementWithDOB()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getFieldValue', 'addUpdatedField'])
            ->getMock();

        $leadObserver->method('getFieldValue')
            ->will($this->returnValueMap([
                ['dob', null, '1970-01-01'],
                ['dob_year', null, null],
                ['dob_month', null, null],
                ['dob_day', null, null],
                ['afb_age', null, null],
            ]));

        $today = getdate();
        $age   = $today['year'] - 1970;

        $leadObserver->expects($this->exactly(4))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['dob_day', 1, null],
                ['dob_month', 1, null],
                ['dob_year', 1970, null],
                ['afb_age', $age, null]
            );

        $mockIntegration = $this->getMockBuilder(AgeFromBirthdateIntegration::class)
            ->setMethodsExcept(['doEnhancement', 'setLogger'])
            ->getMock();

        $mockLogger = $this->createMock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $result = $mockIntegration->doEnhancement($leadObserver);
        $this->assertTrue($result, 'Unexpected enhancement result');
    }

    public function testDoEnhancementWithYMD()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['getFieldValue', 'addUpdatedField'])
            ->getMock();

        $leadObserver->method('getFieldValue')
            ->will($this->returnValueMap([
                ['dob', null, null],
                ['dob_year', null, 1970],
                ['dob_month', null, 1],
                ['dob_day', null, 1],
                ['afb_age', null, null],
            ]));

        $today = getdate();
        $age   = $today['year'] - 1970;

        $leadObserver->expects($this->exactly(2))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['dob', '1970-01-01', null],
                ['afb_age', $age, null]
            );

        $mockIntegration = $this->getMockBuilder(AgeFromBirthdateIntegration::class)
            ->setMethodsExcept(['doEnhancement', 'setLogger'])
            ->getMock();

        $mockLogger = $this->createMock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $result = $mockIntegration->doEnhancement($leadObserver);
        $this->assertTrue($result, 'Unexpected enhancement result');
    }

    public function testDoEnhancementWithout()
    {
        $leadObserver    = $this->createMock(Lead::class);
        $mockIntegration = $this->getMockBuilder(AgeFromBirthdateIntegration::class)
            ->setMethodsExcept(['doEnhancement', 'setLogger'])
            ->getMock();

        $mockLogger = $this->createMock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $this->assertFalse($mockIntegration->doEnhancement($leadObserver), 'Unexpected enhancement result');
    }
}
