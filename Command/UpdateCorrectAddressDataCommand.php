<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/11/18
 * Time: 11:56 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;
use MauticPlugin\MauticEnhancerBundle\Integration\CorrectAddressIntegration as CAI;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class UpdateCorrectAddressDataCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this->setName('mautic:integration:enhancer:updatecorrectaddress');
        $this->setDescription('Installs the latest data files available from Expirian');
        $this->setHelp('This command will download and replace the data files used by CorrectAddress. These are proprietary files available from Expirian');
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

            /** @var \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper $correctAddress */
            $enhancerHelper = new EnhancerHelper($this->getContainer()->get('mautic.helper.integration'));
            $correctAddress = $enhancerHelper->getIntegration('CorrectAddress');
            $settings       = $correctAddress->getIntegrationSettings()->getFeatureSettings();
            $keys           = $correctAddress->getKeys();

            $sftpAdapter = new SftpAdapter([
                'host'            => $settings[CAI::CA_REMOTE_HOST],
                'port'            => $settings[CAI::CA_REMOTE_PORT],
                'root'            => $settings[CAI::CA_REMOTE_PATH],
                'username'        => $keys[CAI::CA_REMOTE_USER],
                'password'        => $keys[CAI::CA_REMOTE_PSWD],
                'hostFingerprint' => $keys[CAI::CA_REMOTE_FNGR],
            ]);
            $client = new Filesystem($sftpAdapter);
            $output->writeln('<info>Created SFTP client.</info>');

            //copy the remote archive locally
            $tempfile = tempnam(sys_get_temp_dir(), 'ca_');
            $client->copy($settings[CAI::CA_REMOTE_FILE], $tempfile);
            $output->writeln('<info>Copied data archive to '.$tempfile.' on local filesystem.</info>');

            //extract the new files
            $buffer    = '/tmp'.$settings[CAI::CA_CORRECTA_DATA];
            $extractor = new ZipArchive();
            $extractor->open($tempfile, ZipArchive::CHECKCONS);
            $extractor->extractTo($buffer);
            $extractor->close();
            unlink($tempfile);
            $output->writeln('<info>Archive extracted to '.$buffer.'.</info>');

            //remove the old files
            $this->cleanDir($settings[CAI::CA_CORRECTA_DATA]);
            $output->writeln('<info>'.$settings[CAI::CA_CORRECTA_DATA].' removed.</info>');

            rename($buffer, $settings[CAI::CA_CORRECTA_DATA]);
            $output->writeln('<info>Expirian data update complete.</info>');

            return 0;
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
                $root = new \RecursiveDirectoryIterator($dirName, \RecursiveDirectoryIterator::SKIP_DOTS);
                $ls   = new \RecursiveIteratorIterator($root, \RecursiveIteratorIterator::CHILD_FIRST);

                foreach ($ls as $file) {
                    $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
                }
                rmdir($dirName);
            } else {
                unlink($dirName);
            }
        }

        return true;
    }
}
