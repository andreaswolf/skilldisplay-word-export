<?php
declare(strict_types=1);

namespace App\Export\Pdf;

final readonly class PdfData
{
    public function __construct(
        public string $title,
        public string $htmlContents
    )
    {
    }
}