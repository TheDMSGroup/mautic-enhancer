<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/11/18
 * Time: 11:56 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;

class GenderNameModel extends AbstractCommonModel
{
    const REFERENCE_REMOTE   = 'https://www.ssa.gov/OACT/babynames/';
    const REFERENCE_LOCAL    = '/tmp/';
    const REFERENCE_FILENAME = 'names.zip';

    /**
     * @return string
     */
    public function getEntityName()
    {
        return '\MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerGenderName';
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|\MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStatePostalCodeRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getEntityName());
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function verifyReferenceTable()
    {
        return $this->getRepository()->verifyReferenceTable();
    }

    /**
     * @return bool
     */
    public function updateReferenceTable()
    {
        try {
            $this->getRepository()->updateReferenceTable($this);

            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }
    }

    public function prepareGenderNameData()
    {
        $dataWorking = [
            'F' => [],
            'M' => [],
        ];

        if ($this->fetchNamesZip() &&
            ($workingDir = $this->cleanWorkingDir()) &&
            $this->extractNamesZip($workingDir)
        ) {
            foreach (glob($workingDir.'*.txt') as $dataFile) {
                $fp = fopen($dataFile, 'r');
                while ($dataLine = fgetcsv($fp)) {
                    list($name, $gender, $individuals) = $dataLine;
                    $name                              = strtoupper($name);
                    $gender                            = strtoupper($gender);
                    if (!isset($dataWorking[$gender][$name])) {
                        $dataWorking[$gender][$name] = 0;
                    }
                    $dataWorking[$gender][$name] += $individuals;
                }
                fclose($fp);
                unlink($dataFile);
            }

            $dataPrepped = [];

            $unisex = array_intersect(
                array_keys($dataWorking['F']),
                array_keys($dataWorking['M'])
            );
            foreach ($dataWorking['F'] as $fName) {
                if (in_array($fName, $unisex)) {
                    $total         = $dataWorking['F'][$fName] + $dataWorking['M'][$fName];
                    $dataPrepped[] = ['gender' => 'F', 'name' => $fName, 'probability' => $dataWorking['F'][$fName] / $total, 'count' => $total];
                    $dataPrepped[] = ['gender' => 'M', 'name' => $fName, 'probability' => $dataWorking['M'][$fName] / $total, 'count' => $total];
                    unset($dataWorking['M'][$fName]);
                } else {
                    $dataPrepped[] = ['gender' => 'F', 'name' => $fName, 'probability' => 1.0, 'count' => $dataWorking['F'][$fName]];
                }
            }
            unset($dataWorking['F']);
            foreach ($dataWorking['M'] as $mName) {
                $dataPrepped[] = ['gender' => 'M', 'name' => $mName, 'probability' => 1.0, 'count' => $dataWorking['M'][$mName]];
            }
            unset($dataWorking);

            return $dataPrepped;
        }
    }

    /**
     * @return bool|resource
     */
    protected function fetchNamesZip()
    {
        try {
            file_put_contents(
                self::REFERENCE_LOCAL.self::REFERENCE_FILENAME,
                file_get_contents(self::REFERENCE_REMOTE.self::REFERENCE_FILENAME)
            );
            $this->logger->info(self::REFERENCE_FILENAME.' downloaded');

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Unable to download data file: '.$e->getMessage());

            return false;
        }
    }

    /**
     * @return bool|string
     */
    protected function cleanWorkingDir()
    {
        $workingDir = self::REFERENCE_LOCAL.'genderNames/';
        if (!(is_dir($workingDir) || mkdir($workingDir))) {
            $this->logger->error('Unable co create working dir at '.$workingDir);

            return false;
        } else {
            $files = array_diff(scandir($workingDir), ['.', '..']);
            foreach ($files as $file) {
                try {
                    unlink($file);
                    $this->logger->warning('Unexpected file '.$file.' found and removed from '.$workingDir);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
            $files = array_diff(scandir($workingDir), ['.', '..']);
            if ($files) {
                $this->logger->error('Unable to remove '.implode(', ', $files).' from '.$workingDir);

                return false;
            }
        }

        return $workingDir;
    }

    /**
     * @param string $workingDir
     */
    protected function extractNamesZip($workingDir)
    {
        try {
            $genderNames = new \ZipArchive();
            $opened      = $genderNames->open(self::REFERENCE_LOCAL.self::REFERENCE_FILENAME);
            if (true !== $opened) {
                $this->logger->warning("[$opened] Unable to open archive");
            } elseif (!$genderNames->extractTo($workingDir)) {
                $this->logger->warning('Unable to extract archive');
            } elseif (!$genderNames->close()) {
                $this->logger->warning('Unable to close archive');
            } else {
                unset($genderNames);

                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }
}
