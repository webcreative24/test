<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Controller\Download;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Webapi\Rest\Request\Deserializer\Json;
use OracDecor\DownloadCenter\Api\DownloadManagerInterface;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\ZipStreamProvider;
use Psr\Log\LoggerInterface;
use Magento\Framework\Webapi\Exception as WebException;

class Files extends Action
{
    /**
     * @var Json
     */
    private $deserializer;
    /**
     * @var DownloadManagerInterface
     */
    private $downloadManager;
    /**
     * @var ZipStreamProvider
     */
    private $zipStreamProvider;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        Json $deserializer,
        DownloadManagerInterface $downloadManager,
        ZipStreamProvider $zipStreamProvider,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->deserializer = $deserializer;
        $this->downloadManager = $downloadManager;
        $this->zipStreamProvider = $zipStreamProvider;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            return $this->handlePostRequest($request);
        }

        return $this->handleGetRequest($request);
    }

    /**
     * In case of POST request: create zip archive based on requested assets and chosen files
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function handlePostRequest(RequestInterface $request)
    {
        try {
            $requestBody = (array)$this->deserializer->deserialize((string)$request->getContent());

            return $this->downloadManager->createDownload(
                new DownloadRequest($requestBody)
            );
        } catch (\Exception $e) {
            $this->logger->critical('Bulk file download failed: ' . $e->getMessage(), ['exception' => $e]);
            $result = $this->jsonFactory->create();
            $result->setHttpResponseCode(WebException::HTTP_BAD_REQUEST);
            $result->setData(['error' => __('Bulk file download failed. Try again later.')]);
            return $result;
        }
    }

    /**
     * In case of GET request: serve stream response based on token
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws NotFoundException
     */
    private function handleGetRequest(RequestInterface $request)
    {
        try {
            return $this->zipStreamProvider->createStream(
                new DownloadRequest(['token' => $request->getParam('token')])
            );
        } catch (\Exception $e) {
            $this->logger->critical('Bulk file download failed: ' . $e->getMessage(), ['exception' => $e]);
            throw new NotFoundException(__('Bulk file download failed.'));
        }
    }
}
