<?php

namespace App\Service;

use App\Entity\Reservation;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class qrcodeVoyService
{
    public function __construct(
        private RouterInterface $router,
        private string $appUrl
    ) {
    }

    public function buildReservationQrCode(Reservation $reservation): ResultInterface
    {
        $detailPath = $this->router->generate(
            'app_front_reservation_detail',
            ['id' => $reservation->getId()],
            UrlGeneratorInterface::ABSOLUTE_PATH
        );

        $detailUrl = rtrim($this->appUrl, '/') . $detailPath;

        // Version corrigée pour Endroid QrCode 6.x
        $qrCode = new QrCode(
            data: $detailUrl,
            size: 420,
            margin: 16
        );

        $writer = new PngWriter();
        return $writer->write($qrCode);
    }
}