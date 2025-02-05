<?php
declare(strict_types=1);

namespace App\Export\Pdf;

use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use GuzzleHttp\Client;

final readonly class GotenbergPdfRenderer
{
    public const CREATOR_NAME = 'Skills PDF renderer';

    public function __construct(private string $gotenbergApiUrl, private Client $httpClient) {}

    public function renderPdf(PdfData $pdf): RenderedPdf
    {
        $request = Gotenberg::chromium($this->gotenbergApiUrl)
            ->pdf()
            ->paperSize('148.5mm', '105mm')
            ->margins(0, 0, 0, 0)
            ->metadata([
                // see https://exiftool.org/TagNames/XMP.html#pdf
                'Title' => $pdf->title,
                'Creator' => self::CREATOR_NAME,
                // CreationDate/ModDate cannot be set here
            ])
            ->waitDelay('5s')
            ->assets(
                Stream::path(__DIR__ . '/../../../css/skill-cards.css'),
                Stream::path(__DIR__ . '/../../../js/paged.polyfill.min.js'),
            )
            ->html(Stream::string(
                'index.html',
                $pdf->htmlContents
            ));
        $result = $this->httpClient->send($request);

        $body = $result->getBody();
        $body->rewind();

        return new RenderedPdf($body->getContents());
    }
}