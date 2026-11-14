<?php

namespace App\Services\Shared;

use Illuminate\Support\Str;

class QrCodeService
{
    public function generate(string $prefix = 'QR'): string
    {
        return strtoupper($prefix).'-'.Str::upper(Str::random(10)).'-'.now()->format('ymdHis');
    }

    public function generatePair(string $prefix = 'ENT'): array
    {
        return [
            'qr_code' => $this->generate('QR-'.$prefix),
            'nfc_code' => $this->generate('NFC-'.$prefix),
        ];
    }

    public function toSvg(string $code, int $size = 120): string
    {
        $escaped = e($code);
        $cell = max(4, (int) floor($size / 12));
        $far = $size - ($cell * 2);

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'" role="img" aria-label="'.$escaped.'">'
            .'<rect width="100%" height="100%" fill="#fff"/>'
            .'<rect x="'.$cell.'" y="'.$cell.'" width="'.$cell.'" height="'.$cell.'" fill="#0f172a"/>'
            .'<rect x="'.$far.'" y="'.$cell.'" width="'.$cell.'" height="'.$cell.'" fill="#0f172a"/>'
            .'<rect x="'.$cell.'" y="'.$far.'" width="'.$cell.'" height="'.$cell.'" fill="#0f172a"/>'
            .'<text x="50%" y="52%" text-anchor="middle" font-size="8" fill="#64748b" font-family="monospace">'.$escaped.'</text>'
            .'</svg>';
    }
}
