<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Service\Factory;

use Alchemy\Zippy\Zippy;

class ZippyFactory
{
    public function create(): Zippy
    {
        return Zippy::load();
    }
}
