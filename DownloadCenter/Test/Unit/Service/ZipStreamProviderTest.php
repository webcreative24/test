<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Test\Unit\Service;

use Magento\Framework\App\ResponseInterface;
use OracDecor\Base\App\Response\StreamResponseFactory;
use OracDecor\DownloadCenter\Configuration\General;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\ZipStreamProvider;
use PHPUnit\Framework\TestCase;

class ZipStreamProviderTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $generalConfig;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $streamResponseFactory;
    /**
     * @var ZipStreamProvider
     */
    private $zipStreamProvider;

    public function setUp()
    {
        $this->generalConfig = $this->createMock( General::class);
        $this->streamResponseFactory = $this->createMock( StreamResponseFactory::class);

        $this->zipStreamProvider = new ZipStreamProvider(
            $this->generalConfig,
            $this->streamResponseFactory
        );
    }

    public function testCreateStream()
    {
        $token = '1234abcd.zip';
        $basePath = '/var/archives';
        $archivePath = $basePath . '/' . $token;

        $downloadRequest = new DownloadRequest(['token' => $token]);

        $this->generalConfig->expects($this->any())->method('getArchivePath')->willReturn($basePath);

        $streamResponse = $this->createMock(ResponseInterface::class);
        $this->streamResponseFactory->expects($this->once())
            ->method('create')
            ->with(
                $archivePath,
                $token
            )
            ->willReturn($streamResponse);

        $this->assertEquals($streamResponse, $this->zipStreamProvider->createStream($downloadRequest));
    }
}
