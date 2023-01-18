<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

class NoAssetException extends RuntimeException
{
    public const NO_ZIP_ASSET = 1;
    public const NO_XML_ASSET = 2;
}
