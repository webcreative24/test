<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Service;

use Magento\Framework\Filesystem\Driver\File as Filesystem;
use OracDecor\Akeneo\Configuration\AkeneoConfiguration;
use OracDecor\Assets\Api\Data\AssetInterface;
use OracDecor\DownloadCenter\Configuration\General;
use OracDecor\DownloadCenter\Exception\ArchiveException;
use OracDecor\DownloadCenter\Service\Factory\ZippyFactory;
use Psr\Log\LoggerInterface;

class ArchiveManager
{
    /**
     * @var ZippyFactory
     */
    private $zippyFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var AkeneoConfiguration
     */
    private $akeneoConfiguration;
    /**
     * @var General
     */
    private $generalConfiguration;

    public function __construct(
        ZippyFactory $zippyFactory,
        LoggerInterface $logger,
        Filesystem $filesystem,
        AkeneoConfiguration $configuration,
        General $generalConfiguration
    ) {
        $this->zippyFactory = $zippyFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->akeneoConfiguration = $configuration;
        $this->generalConfiguration = $generalConfiguration;
    }

    /**
     * Create archive of given assets.
     * The archiving process is skipped in case the archive already exists.
     *
     * @param string $archiveFilename
     * @param AssetInterface[] $assets
     * @return string
     * @throws \Exception
     */
    public function createArchive(string $archiveFilename, array $assets): string
    {
        // Skip archiving if archive already exists:
        $archivePath = sprintf('%s/%s', $this->getArchiveBaseDirectory(), $archiveFilename);
        if ($this->filesystem->isExists($archivePath) && !$this->generalConfiguration->isForceRecreate()) {
            $this->logger->info(sprintf('Skip archiving, the archive %s still exists.', $archivePath));
            return $archivePath;
        }

        $files = [];
        foreach ($assets as $asset) {
            // The property requested_files is set in the FilesDownloadManager and is used to download a specific selection of file(s).
            $fileFields = ($asset->getData('requested_files')) ?? $asset->getAvailableFileFields();
            $files = array_merge($files, $this->composeFilesForArchive($asset, $fileFields));
        }

        return $this->doCreateArchive($archiveFilename, $files);
    }

    private function composeFilesForArchive(AssetInterface $asset, array $fileFields): array
    {
        $basePath = ($asset->isDownloadable()) ? $this->akeneoConfiguration->getPrivatePath() : $this->akeneoConfiguration->getPublicPath();

        $files = [];
        foreach ($fileFields as $fileField) {
            if (!$asset->getData($fileField)) {
                $this->logger->info('File field ' . $fileField . ' of asset ' . $asset->getReference() . ' is empty');
                continue;
            }

            $filePath = $basePath . DIRECTORY_SEPARATOR . $asset->getData($fileField);

            if (!$this->filesystem->isExists($filePath)) {
                $this->logger->error(sprintf('File (%s) %s of asset %s does not exist.', $fileField, $filePath, $asset->getReference()));
                continue;
            }

            $files[pathinfo($filePath, PATHINFO_BASENAME)] = $filePath;
        }

        return $files;
    }

    /**
     * @param string $filename
     * @param array $files
     * @return string
     * @throws ArchiveException
     */
    private function doCreateArchive(string $filename, array $files): string
    {
        try {
            $zippy = $this->zippyFactory->create();

            $baseDirectory = $this->getArchiveBaseDirectory();
            $this->logger->info(sprintf('Ensure the base directory exist %s', $baseDirectory));
            $this->filesystem->createDirectory($baseDirectory);

            $absolutePath = sprintf(
                '%s/%s',
                $baseDirectory,
                $filename
            );

            $this->logger->info(sprintf('Creating archive with name %s with %s files.', $absolutePath, count($files)));
            $zippy->create($absolutePath, $files);
        } catch (\Exception $e) {
            $this->logger->critical('Cannot create ZIP archive: ' . $e->getMessage(), ['exception' => $e]);
            throw new ArchiveException('Cannot create ZIP archive', 0, $e);
        }
        $this->logger->info(sprintf('Archive created %s.', $absolutePath));

        return $absolutePath;
    }

    /**
     * @return string
     */
    private function getArchiveBaseDirectory(): string
    {
        return $this->generalConfiguration->getArchivePath();
    }
}
