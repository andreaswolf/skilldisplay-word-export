<?php
declare(strict_types=1);

namespace App\Export\Pdf;

final readonly class RenderedPdf
{
    public function __construct(public string $data)
    {
    }
}