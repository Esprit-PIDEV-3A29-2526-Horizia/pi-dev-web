<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{

    #[Route('/admin/calendar', name: 'app_calendar')]
    public function index(): Response
    {
        return $this->render('admin/calendar/index.html.twig');
    }

}