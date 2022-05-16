<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Controller\Download;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use OracDecor\DownloadCenter\Api\DownloadManagerInterface;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use Psr\Log\LoggerInterface;

class Asset extends Action
{
    /**
     * @var DownloadManagerInterface
     */
    private $downloadManager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        DownloadManagerInterface $downloadManager,
        LoggerInterface $logger
    ) {
        $this->downloadManager = $downloadManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $assetReference = $this->getRequest()->getParam('asset');

            return $this->downloadManager->createDownload(
                new DownloadRequest(['asset_reference' => $assetReference])
            );
        } catch (\Exception $e) {
            $this->logger->critical('Asset download failed: ' . $e->getMessage(), ['exception' => $e]);
            throw new NotFoundException(__('Asset download failed.'));
        }
    }
}
