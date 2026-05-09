<?php

namespace App\Controller;

use App\Entity\FavoriVoyage;
use App\Entity\User;
use App\Entity\Voyage;
use App\Repository\FavoriVoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FavoriApiController extends AbstractController
{
    private function getOrCreateVisitorToken(Request $request): string
    {
        return $request->cookies->get('horozia_visitor') ?: bin2hex(random_bytes(16));
    }

    #[Route('/mes-favoris', name: 'app_front_mes_favoris', methods: ['GET'])]
    public function mesFavoris(
        Request $request,
        FavoriVoyageRepository $favoriRepository,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();
        $visitorToken = $this->getOrCreateVisitorToken($request);

        if ($user instanceof User) {
            $qb = $favoriRepository->createQueryBuilder('f')
                ->leftJoin('f.voyage', 'v')
                ->addSelect('v')
                ->andWhere('f.createdBy = :user')
                ->setParameter('user', $user)
                ->orderBy('f.id', 'DESC');
        } else {
            $qb = $favoriRepository->createQueryBuilder('f')
                ->leftJoin('f.voyage', 'v')
                ->addSelect('v')
                ->andWhere('f.visitorToken = :visitorToken')
                ->setParameter('visitorToken', $visitorToken)
                ->orderBy('f.id', 'DESC');
        }

        $favoris = $paginator->paginate(
            $qb,
            max(1, (int) $request->query->get('page', 1)),
            6
        );

        $response = $this->render('front/mes_favoris.html.twig', [
            'favoris' => $favoris,
        ]);

        if (!$request->cookies->has('horozia_visitor')) {
            $response->headers->setCookie(
                Cookie::create('horozia_visitor', $visitorToken, strtotime('+1 year'))
            );
        }

        return $response;
    }

    #[Route('/api/favoris/check/{id}', name: 'app_api_favori_check', methods: ['GET'])]
    public function check(
        int $id,
        Request $request,
        FavoriVoyageRepository $favoriRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $voyage = $entityManager->find(Voyage::class, $id);

            if (!$voyage instanceof Voyage) {
                return $this->json([
                    'success' => false,
                    'message' => 'Voyage introuvable.',
                ], 404);
            }

            $user = $this->getUser();
            $visitorToken = $this->getOrCreateVisitorToken($request);

            if ($user instanceof User) {
                $isFavorite = $favoriRepository->findOneByUserAndVoyage($user, $voyage) !== null;
            } else {
                $isFavorite = $favoriRepository->findOneByVisitorAndVoyage($visitorToken, $voyage) !== null;
            }

            $response = $this->json([
                'success' => true,
                'isFavorite' => $isFavorite,
            ]);

            if (!$request->cookies->has('horozia_visitor')) {
                $response->headers->setCookie(
                    Cookie::create('horozia_visitor', $visitorToken, strtotime('+1 year'))
                );
            }

            return $response;
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/favoris/toggle/{id}', name: 'app_api_favori_toggle', methods: ['POST'])]
    public function toggle(
        int $id,
        Request $request,
        FavoriVoyageRepository $favoriRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $voyage = $entityManager->find(Voyage::class, $id);

            if (!$voyage instanceof Voyage) {
                return $this->json([
                    'success' => false,
                    'message' => 'Voyage introuvable.',
                ], 404);
            }

            $user = $this->getUser();
            $visitorToken = $this->getOrCreateVisitorToken($request);

            if ($user instanceof User) {
                $favori = $favoriRepository->findOneByUserAndVoyage($user, $voyage);
            } else {
                $favori = $favoriRepository->findOneByVisitorAndVoyage($visitorToken, $voyage);
            }

            if ($favori instanceof FavoriVoyage) {
                $entityManager->remove($favori);
                $entityManager->flush();

                $response = $this->json([
                    'success' => true,
                    'isFavorite' => false,
                    'message' => 'Voyage retiré des favoris.',
                ]);
            } else {
                $favori = new FavoriVoyage();

                if ($user instanceof User) {
                    $favori->setCreatedBy($user);
                    $favori->setVisitorToken(null);
                } else {
                    $favori->setVisitorToken($visitorToken);
                }

                $favori->setVoyage($voyage);

                $entityManager->persist($favori);
                $entityManager->flush();

                $response = $this->json([
                    'success' => true,
                    'isFavorite' => true,
                    'message' => 'Voyage ajouté aux favoris.',
                ]);
            }

            if (!$request->cookies->has('horozia_visitor')) {
                $response->headers->setCookie(
                    Cookie::create('horozia_visitor', $visitorToken, strtotime('+1 year'))
                );
            }

            return $response;
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}