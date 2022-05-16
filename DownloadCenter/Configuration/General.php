<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Configuration;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class General
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
    }

    public function getArchivePath(): string
    {
        return sprintf(
            '%s/%s',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            trim($this->getConfigValue('archive_path'), '/')
        );
    }

    public function isForceRecreate(): bool
    {
        return (bool)$this->getConfigValue('archive_force_recreate');
    }

    private function getConfigValue($name): string
    {
        return $this->scopeConfig->getValue('download_center/general/' . $name);
    }
}
