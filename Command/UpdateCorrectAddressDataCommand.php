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
use MauticPlugin\MauticEnhancerBundle\Integration\ExpirianCorrectAddressIntegration as ECAI;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCorrectAddressData extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:integration:enhancer:updatecorrectaddress')
            ->setDescription('Installs the latest data files available from Expirian')
            ->setHelp('This command will download and replace the data files used by CorrectAddress. These are proprietary files available from Expirian');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var \MauticPlugin\MauticEnhancerBundle\Integration\ExpirianCorrectAddressIntegration $correctAddress */
            $correctAddress = $this->getContainer()
                ->get('mautic.enhancer.helper.enhancer')
                ->getIntegration('mautic.enhancer.integration.correctaddress');

            $settings = $correctAddress->getSupportedFeatures();
            $keys     = $correctAddress->getKeys();

            $SftpAdapter = new SftpAdapter([
                'host'            => $settings[ECAI::CA_REMOTE_HOST],
                'port'            => $settings[ECAI::CA_REMOTE_PORT],
                'hostFingerprint' => $keys[ECAI::CA_REMOTE_FNGR],
                'username'        => $keys[ECAI::CA_REMOTE_USER],
                'password'        => $keys[ECAI::CA_REMOTE_PSWD],
                'root'            => $settings[ECAI::CA_REMOTE_PATH],
            ]);
            $client = new Filesystem($SftpAdapter);

            //copy the remote archive locally
            $tempfile = tempnam(sys_get_temp_dir(), 'ca_');
            $client->copy($settings[ECAI::CA_REMOTE_FILE], $tempfile);

            //remove the old files
            $this->cleanDir($settings[ECAI::CA_CORRECTA_DATA]);

            $extractor = new \ZipArchive();
            $extractor->open($tempfile);
            $extractor->extractTo($settings[ECAI::CA_CORRECTA_DATA]);
            $extractor->close();
            unlink($tempfile);
        } catch (\Exception $e) {
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

        $root     = new \RecursiveDirectoryIterator($dirName, \RecursiveDirectoryIterator::SKIP_DOTS);
        $contents = new \RecursiveIteratorIterator($root, \RecursiveIteratorIterator::CHILD_FIRST);

        /** @var \SplFileInfo $file */
        foreach ($contents as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        return is_writable($dirName);
    }
}
