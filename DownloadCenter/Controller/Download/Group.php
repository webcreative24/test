<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Controller\Download;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
use OracDecor\DownloadCenter\Api\DownloadManagerInterface;
use OracDecor\DownloadCenter\Exception\InvalidRequestException;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use Psr\Log\LoggerInterface;

class Group extends Action
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
        throw new InvalidRequestException('This feature has been disabled.');

        try {
            $groupReference = $this->getRequest()->getParam('group');

            return $this->downloadManager->createDownload(
                new DownloadRequest(['group_reference' => $groupReference])
            );
        } catch (\Exception $e) {
            $this->logger->critical('Group download failed: ' . $e->getMessage(), ['exception' => $e]);
            throw new NotFoundException(__('Group download failed.'));
        }
    }
}
