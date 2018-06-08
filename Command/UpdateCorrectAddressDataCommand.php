<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/11/18
 * Time: 11:56 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Command;

use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\Sftp\SftpAdapter as LeagueSftpAdapter;
use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;
use MauticPlugin\MauticEnhancerBundle\Integration\CorrectAddressIntegration as CAI;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            echo 'Starting Expirian data update';

            /** @var \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper $correctAddress */
            $enhancerHelper = new EnhancerHelper($this->getContainer()->get('mautic.helper.integration'));
            $correctAddress = $enhancerHelper->getIntegration('CorrectAddress');
            $settings       = $correctAddress->getIntegrationSettings()->getFeatureSettings();
            $keys           = $correctAddress->getKeys();

            $adapter = new LeagueSftpAdapter([
                'host'            => $settings[CAI::CA_REMOTE_HOST],
                'port'            => $settings[CAI::CA_REMOTE_PORT],
                'root'            => $settings[CAI::CA_REMOTE_PATH],
                'username'        => $keys[CAI::CA_REMOTE_USER],
                'password'        => $keys[CAI::CA_REMOTE_PSWD],
                'hostFingerprint' => $keys[CAI::CA_REMOTE_FNGR],
            ]);
            $client = new LeagueFilesystem($adapter);
            echo 'Created SFTP client'.PHP_EOL;

            //copy the remote archive locally
            $tempfile = tempnam(sys_get_temp_dir(), 'ca_');
            $client->copy($settings[CAI::CA_REMOTE_FILE], $tempfile);
            echo 'Copied data archive to '.$tempfile.' on local filesystem'.PHP_EOL;

            //remove the old files
            $this->cleanDir($settings[CAI::CA_CORRECTA_DATA]);
            echo $settings[CAI::CA_CORRECTA_DATA].' is ready for writing'.PHP_EOL;

            $extractor = new \ZipArchive();
            $extractor->open($tempfile);
            $extractor->extractTo($settings[CAI::CA_CORRECTA_DATA]);
            $extractor->close();
            unlink($tempfile);
            echo 'Expirian data update complete'.PHP_EOL;

            return 0;
        } catch (\Exception $e) {
            echo $e->getMessage();

            return $e->getCode();
        }
    }

    /**
     * @param $dirName
     *
     * @return bool
     */
    protected function cleanDir($dirName)
    {
        if (!file_exists($dirName) || ('dir' !== filetype($dirName))) {
            if (file_exists($dirName)) {
                unlink($dirName);
            }

            return mkdir($dirName, 0755, true);
        }

        $root = new \RecursiveDirectoryIterator($dirName, \RecursiveDirectoryIterator::SKIP_DOTS);
        $ls   = new \RecursiveIteratorIterator($root, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($ls as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        return is_writable($dirName);
    }
}
