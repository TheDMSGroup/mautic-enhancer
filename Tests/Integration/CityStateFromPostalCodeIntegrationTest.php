<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 3/21/19
 * Time: 8:04 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Tests\Integration;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStatePostalCode;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStatePostalCodeRepository;
use MauticPlugin\MauticEnhancerBundle\Integration\CityStateFromPostalCodeIntegration;
use MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class CityStateFromPostalCodeIntegrationTest extends TestCase
{
    public function testDoEnhancementCityStateFromZipcode()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue', 'getCity', 'getState', 'getCountry', 'getZipcode', 'setCity', 'setState'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('getCity')
            ->willReturn(null);

        $leadObserver->expects($this->once())
            ->method('getState')
            ->willReturn(null);

        $leadObserver->expects($this->once())
            ->method('getFieldValue')
            ->willReturn(null);

        $leadObserver->expects($this->once())
            ->method('getZipcode')
            ->willReturn('13579');

        $leadObserver->expects($this->once())
            ->method('getCountry')
            ->willReturn('US');

        $leadObserver->expects($this->once())
            ->method('setCity')
            ->with('City');

        $leadObserver->expects($this->once())
            ->method('setState')
            ->with('ST');

        $leadObserver->expects($this->once())
            ->method('addUpdatedField')
            ->with('county', 'County', null);

        $mockIntegration = $this->getMockBuilder(CityStateFromPostalCodeIntegration::class)
            ->setMethods(['getIntegrationModel'])
            ->getMock();

        $mockModel = $this->getMockBuilder(CityStatePostalCodeModel::class)
            ->setMethods(['getRepository'])
            ->getMock();

        $mockRepository = $this->getMockBuilder(PluginEnhancerCityStatePostalCodeRepository::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockEntity = $this->getMockBuilder(PluginEnhancerCityStatePostalCode::class)
            ->setMethods(['getCity', 'getStateProvince', 'getCounty'])
            ->getMock();

        $mockEntity->expects($this->any())
            ->method('getCity')
            ->willReturn('City');

        $mockEntity->expects($this->any())
            ->method('getStateProvince')
            ->willReturn('ST');

        $mockEntity->expects($this->any())
            ->method('getCounty')
            ->willReturn('County');

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($mockEntity);

        $mockModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($mockRepository);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationModel')
            ->willReturn($mockModel);

        $mockLogger = $this->createMock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $mockEntityMgr = $this->createMock(EntityManager::class);
        $mockIntegration->setEntityManager($mockEntityMgr);

        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }

    public function testDoEnhancementZipcodeFromCityState()
    {
        $leadObserver = $this->getMockBuilder(Lead::class)
            ->setMethods(['addUpdatedField', 'getFieldValue', 'getCity', 'getState', 'getCountry', 'getZipcode', 'setZipcode'])
            ->getMock();

        $leadObserver->expects($this->once())
            ->method('getCity')
            ->willReturn('City');

        $leadObserver->expects($this->once())
            ->method('getState')
            ->willReturn('State');

        $leadObserver->expects($this->once())
            ->method('getFieldValue')
            ->willReturn('County');

        $leadObserver->expects($this->once())
            ->method('getZipcode')
            ->willReturn(null);

        $leadObserver->expects($this->once())
            ->method('getCountry')
            ->willReturn('US');

        $leadObserver->expects($this->once())
            ->method('setZipcode')
            ->with('97531');

        $mockIntegration = $this->getMockBuilder(CityStateFromPostalCodeIntegration::class)
            ->setMethods(['getIntegrationModel'])
            ->getMock();

        $mockModel = $this->getMockBuilder(CityStatePostalCodeModel::class)
            ->setMethods(['getRepository'])
            ->getMock();

        $mockRepository = $this->getMockBuilder(PluginEnhancerCityStatePostalCodeRepository::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockEntity = $this->getMockBuilder(PluginEnhancerCityStatePostalCode::class)
            ->setMethods(['getPostalCode'])
            ->getMock();

        $mockEntity->expects($this->once())
            ->method('getPostalCode')
            ->willReturn('97531');

        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($mockEntity);

        $mockModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($mockRepository);

        $mockIntegration->expects($this->any())
            ->method('getIntegrationModel')
            ->willReturn($mockModel);

        $mockLogger = $this->createMock(Logger::class);
        $mockIntegration->setLogger($mockLogger);

        $mockEntityMgr = $this->createMock(EntityManager::class);
        $mockIntegration->setEntityManager($mockEntityMgr);
        
        $this->assertTrue($mockIntegration->doEnhancement($leadObserver), 'Unexpected result.');
    }
}
