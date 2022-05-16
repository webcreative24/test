<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Api;

use OracDecor\DownloadCenter\Model\DownloadRequest;

interface DownloadManagerInterface
{
    const ARCHIVE_EXTENSION = 'zip';

    public function createDownload(DownloadRequest $request);
}
