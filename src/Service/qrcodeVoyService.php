<?php

namespace App\Service;

use App\Entity\Reservation;
use Endroid\QrCode\Builder\Builder;
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

        return Builder::create()
            ->writer(new PngWriter())
            ->data($detailUrl)
            ->size(420)
            ->margin(16)
            ->build();
    }
}