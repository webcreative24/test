<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Service;

use Magento\Framework\App\ResponseInterface;
use OracDecor\Base\App\Response\StreamResponseFactory;
use OracDecor\DownloadCenter\Configuration\General;
use OracDecor\DownloadCenter\Exception\InvalidRequestException;
use OracDecor\DownloadCenter\Model\DownloadRequest;

class ZipStreamProvider
{
    /**
     * @var General
     */
    private $generalConfiguration;

    /**
     * @var StreamResponseFactory
     */
    private $streamResponseFactory;

    public function __construct(
        General $generalConfiguration,
        StreamResponseFactory $streamResponseFactory
    ) {
        $this->generalConfiguration = $generalConfiguration;
        $this->streamResponseFactory = $streamResponseFactory;
    }

    /**
     * @param DownloadRequest $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function createStream(DownloadRequest $request)
    {
        $this->guardValidRequest($request);

        $zipFile = $request->getData('token');
        $basePath = $this->generalConfiguration->getArchivePath();

        return $this->streamResponseFactory->create(
            $basePath . DIRECTORY_SEPARATOR . $zipFile,
            $zipFile
        );
    }

    /**
     * @param DownloadRequest $request
     * @throws InvalidRequestException
     */
    private function guardValidRequest(DownloadRequest $request)
    {
        if (!$request->hasData('token')) {
            throw new InvalidRequestException('A token is required');
        }
    }
}
