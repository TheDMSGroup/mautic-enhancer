<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;
use MauticPlugin\MauticEnhancerBundle\Integration\CorrectAddressIntegration as CAI;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

/**
 * Class UpdateCorrectAddressDataCommand.
 */
class UpdateCorrectAddressDataCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this->setName('mautic:integration:enhancer:updatecorrectaddress');
        $this->setDescription('Installs the latest data files available from Expirian');
        $this->setHelp(
            'This command will download and replace the data files used by CorrectAddress. These are proprietary files available from Expirian'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('<info>Starting Expirian data update.</info>');

            /** @var IntegrationHelper $integrationHelper */
            $integrationHelper = $this->getContainer()->get('mautic.helper.integration');
            $enhancerHelper    = new EnhancerHelper($integrationHelper);
            /** @var \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper $correctAddress */
            $correctAddress = $enhancerHelper->getIntegration('CorrectAddress');
            $settings       = $correctAddress->getIntegrationSettings()->getFeatureSettings();
            $keys           = $correctAddress->getKeys();

            if (function_exists('ssh2_connect')) {
                $sconn = call_user_func('ssh2_connect', $settings[CAI::CA_REMOTE_HOST]);
                call_user_func('ssh2_auth_password', $sconn, $keys[CAI::CA_REMOTE_USER], $keys[CAI::CA_REMOTE_PSWD]);
                $sftp = call_user_func('ssh2_sftp', $sconn);
            } else {
                throw new \Exception(
                    'Required ssh2 extension is not installed',
                    -1
                );
            }
            $output->writeln('<info>SFTP connection established, downloading data file</info>');

            $source = 'ssh2.sftp://'.intval($sftp).$settings[CAI::CA_REMOTE_PATH].'/'.$settings[CAI::CA_REMOTE_FILE];

            $buffer = tempnam(sys_get_temp_dir(), 'ca_'.\date('Y-m-d'));
            if (file_exists($buffer)) {
                unlink($buffer);
            }
            $dest = $buffer.'.zip';
            $rfp  = fopen($source, 'r');
            $wfp  = fopen($dest, 'w');

            $reads = 0;
            do {
                if (!fwrite($wfp, fread($rfp, 8388608))) {
                    break;
                }
                ++$reads;
                if (0 === ($reads % 100)) {
                    $output->write('.');
                }
            } while (true);
            $output->writeln('<info>Copied data archive to '.$dest.' on local filesystem.</info>');

            //extract the new files
            $extractor = new ZipArchive();
            $extractor->open($dest, ZipArchive::CHECKCONS);
            $extractor->extractTo($buffer);
            if ($extractor->close() && is_dir($buffer)) {
                unlink($dest);
                $output->writeln('<info>Archive extracted to '.$buffer.'.</info>');

                if (is_dir($settings[CAI::CA_CORRECTA_DATA].'_bak')) {
                    $this->cleanDir($settings[CAI::CA_CORRECTA_DATA].'_bak');
                    $output->writeln('<info>'.$settings[CAI::CA_CORRECTA_DATA].'_bak cleaned.</info>');
                }
                rename($settings[CAI::CA_CORRECTA_DATA], $settings[CAI::CA_CORRECTA_DATA].'_bak');

                if (rename($buffer, $settings[CAI::CA_CORRECTA_DATA])) {
                    $output->writeln('<info>Expirian data update complete.</info>');

                    return 0;
                } else {
                    $output->writeln(
                        '<error>Failed to move '.$buffer.' to '.$settings[CAI::CA_CORRECTA_DATA].'</error>'
                    );

                    return 1;
                }
            } else {
                $output->writeln('<error>Failed to unzip '.$dest.' to '.$buffer.'</error>');

                return 1;
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to update data: '.$e->getMessage().'</error>');
            $output->write($e->getTraceAsString());
            $output->writeln('');

            return $e->getCode();
        }
    }

    /**
     * @param string $dirName
     *
     * @return bool
     */
    protected function cleanDir($dirName)
    {
        if (file_exists($dirName)) {
            if (is_dir($dirName)) {
                $rm_path = new \RecursiveDirectoryIterator($dirName, \RecursiveDirectoryIterator::SKIP_DOTS);
                $rm_ls   = new \RecursiveIteratorIterator($rm_path, \RecursiveIteratorIterator::CHILD_FIRST);

                foreach ($rm_ls as $rm_file) {
                    $rm_file->isDir() ? rmdir($rm_file->getRealPath()) : unlink($rm_file->getRealPath());
                }
                rmdir($dirName);
            } else {
                unlink($dirName);
            }
        } else {
            mkdir($dirName, 0755, true);
            rmdir($dirName);
        }

        return true;
    }
}
