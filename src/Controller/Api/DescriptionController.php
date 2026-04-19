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
        $titre = trim($data['titre'] ?? '');
        $categorie = trim($data['categorie'] ?? '');
        $ville = trim($data['ville'] ?? '');
        $pays = trim($data['pays'] ?? '');

        // Validation côté serveur (pas de HTML5/JS)
        if (empty($titre)) {
            return $this->json(['error' => 'Le titre est obligatoire.'], 400);
        }
        if (strlen($titre) < 3) {
            return $this->json(['error' => 'Le titre doit comporter au moins 3 caractères.'], 400);
        }
        if (empty($categorie)) {
            return $this->json(['error' => 'La catégorie est obligatoire.'], 400);
        }

        $description = $generator->generateDescription($titre, $categorie, $ville, $pays);
        return $this->json(['description' => $description]);
    }

    #[Route('/api/translate-description', name: 'api_translate_description', methods: ['POST'])]
    public function translate(Request $request, DescriptionGenerator $generator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $description = trim($data['description'] ?? '');
        $lang = $data['lang'] ?? 'en';

        if (empty($description)) {
            return $this->json(['error' => 'La description est manquante.'], 400);
        }
        if (strlen($description) < 10) {
            return $this->json(['error' => 'La description doit comporter au moins 10 caractères.'], 400);
        }

        $translated = $generator->translateDescription($description, $lang);
        return $this->json(['description' => $translated]);
    }
}