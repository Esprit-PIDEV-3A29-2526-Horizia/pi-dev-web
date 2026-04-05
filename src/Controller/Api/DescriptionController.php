<?php

namespace App\Controller\Api;

use App\Service\DescriptionGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DescriptionController extends AbstractController
{
    #[Route('/api/generate-description', name: 'api_generate_description', methods: ['POST'])]
    public function generate(Request $request, DescriptionGenerator $generator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $titre = $data['titre'] ?? '';
        $categorie = $data['categorie'] ?? '';
        $ville = $data['ville'] ?? '';
        $pays = $data['pays'] ?? '';

        if (!$titre || !$categorie) {
            return $this->json(['error' => 'Titre et catégorie requis'], 400);
        }

        $description = $generator->generateDescription($titre, $categorie, $ville, $pays);
        return $this->json(['description' => $description]);
    }

    #[Route('/api/translate-description', name: 'api_translate_description', methods: ['POST'])]
    public function translate(Request $request, DescriptionGenerator $generator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $description = $data['description'] ?? '';
        $lang = $data['lang'] ?? 'en';

        if (!$description) {
            return $this->json(['error' => 'Description manquante'], 400);
        }

        $translated = $generator->translateDescription($description, $lang);
        return $this->json(['description' => $translated]);
    }
}