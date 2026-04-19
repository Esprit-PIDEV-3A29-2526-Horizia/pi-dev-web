<?php

namespace App\Controller\Admin;

use App\Entity\Vehicule;
use App\Form\VehiculeType;
use App\Repository\VehiculeRepository;
use App\Repository\MarqueRepository;
use App\Repository\ModeleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/vehicule')]
class VehiculeController extends AbstractController
{
    // 📋 LISTE des véhicules avec filtres + pagination
    #[Route('/', name: 'admin_vehicule_index', methods: ['GET'])]
    public function index(
        VehiculeRepository $repository,
        Request $request,
        MarqueRepository $marqueRepository
    ): Response {
        // Paramètres de filtre
        $rechercheImmat  = $request->query->get('immat', '');
        $filtreEtat      = $request->query->get('etat', '');
        $filtreCarburant = $request->query->get('carburant', '');
        $filtreMarque    = $request->query->get('marque', '');
        $tri             = $request->query->get('tri', 'immatriculation');

        // Vue (table ou cards) et pagination
        $vue  = $request->query->get('vue', 'table');
        $page = max(1, (int) $request->query->get('page', 1));

        // Nombre de résultats par page selon la vue
        $limit = ($vue === 'cards') ? 6 : 10;

        // Construire la requête
        $qb = $repository->createQueryBuilder('v')
            ->leftJoin('v.modele', 'm')
            ->leftJoin('m.marque', 'ma')
            ->addSelect('m', 'ma');

        // Filtre par immatriculation
        if (!empty($rechercheImmat)) {
            $qb->andWhere('v.immatriculation LIKE :immat')
               ->setParameter('immat', '%' . $rechercheImmat . '%');
        }

        // Filtre par état
        if (!empty($filtreEtat) && $filtreEtat !== 'Tous') {
            $qb->andWhere('v.etat = :etat')
               ->setParameter('etat', $filtreEtat);
        }

        // Filtre par carburant
        if (!empty($filtreCarburant) && $filtreCarburant !== 'Tous') {
            $qb->andWhere('v.carburant = :carburant')
               ->setParameter('carburant', $filtreCarburant);
        }

        // Filtre par marque
        if (!empty($filtreMarque)) {
            $qb->andWhere('ma.idMarque = :marque')
               ->setParameter('marque', $filtreMarque);
        }

        // Tri
        switch ($tri) {
            case 'prix_croissant':
                $qb->orderBy('v.prixParJour', 'ASC');
                break;
            case 'prix_decroissant':
                $qb->orderBy('v.prixParJour', 'DESC');
                break;
            case 'annee_recente':
                $qb->orderBy('v.annee', 'DESC');
                break;
            case 'kilometrage':
                $qb->orderBy('v.kilometrage', 'ASC');
                break;
            default:
                $qb->orderBy('v.immatriculation', 'ASC');
        }

        // Compter le total (avant pagination)
        $countQb = clone $qb;
        $total   = (int) $countQb->select('COUNT(v.idVehicule)')
                                  ->getQuery()
                                  ->getSingleScalarResult();

        // Calculer le nombre de pages
        $total_pages = max(1, (int) ceil($total / $limit));
        $page        = min($page, $total_pages); // Sécurité : page ne dépasse pas le max

        // Appliquer la pagination
        $vehicules = $qb->select('v', 'm', 'ma')
                        ->setFirstResult(($page - 1) * $limit)
                        ->setMaxResults($limit)
                        ->getQuery()
                        ->getResult();

        // Récupérer les marques pour le filtre
        $marques = $marqueRepository->findAllAlphabetique();

        return $this->render('admin/vehicule/index.html.twig', [
            'vehicules'       => $vehicules,
            'marques'         => $marques,
            // Filtres
            'rechercheImmat'  => $rechercheImmat,
            'filtreEtat'      => $filtreEtat,
            'filtreCarburant' => $filtreCarburant,
            'filtreMarque'    => $filtreMarque,
            'tri'             => $tri,
            // Vue & pagination
            'vue'             => $vue,
            'page'            => $page,
            'limit'           => $limit,
            'total'           => $total,
            'total_pages'     => $total_pages,
        ]);
    }

    // ➕ AJOUTER un véhicule
    #[Route('/new', name: 'admin_vehicule_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        MarqueRepository $marqueRepository,
        ModeleRepository $modeleRepository
    ): Response {
        $vehicule = new Vehicule();
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($vehicule);
            $em->flush();

            $this->addFlash('success', 'Véhicule ajouté avec succès !');
            return $this->redirectToRoute('admin_vehicule_index');
        }

        return $this->render('admin/vehicule/new.html.twig', [
            'form'             => $form->createView(),
            'marques'          => $marqueRepository->findAllAlphabetique(),
            'modeleRepository' => $modeleRepository,
        ]);
    }

    // 👁️ VOIR un véhicule
    #[Route('/{id}', name: 'admin_vehicule_show', methods: ['GET'])]
    public function show(Vehicule $vehicule): Response
    {
        return $this->render('admin/vehicule/show.html.twig', [
            'vehicule' => $vehicule,
        ]);
    }

    // ✏️ MODIFIER un véhicule
    #[Route('/{id}/edit', name: 'admin_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Vehicule $vehicule,
        EntityManagerInterface $em,
        MarqueRepository $marqueRepository,
        ModeleRepository $modeleRepository
    ): Response {
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Véhicule modifié avec succès !');
            return $this->redirectToRoute('admin_vehicule_index');
        }

        return $this->render('admin/vehicule/edit.html.twig', [
            'form'             => $form->createView(),
            'vehicule'         => $vehicule,
            'marques'          => $marqueRepository->findAllAlphabetique(),
            'modeleRepository' => $modeleRepository,
        ]);
    }

    // 🗑️ SUPPRIMER un véhicule
    #[Route('/{id}/delete', name: 'admin_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicule $vehicule, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $vehicule->getIdVehicule(), $request->request->get('_token'))) {

            if ($vehicule->getLocations()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce véhicule car il a ' . $vehicule->getLocations()->count() . ' location(s) associée(s).');
            } else {
                $em->remove($vehicule);
                $em->flush();
                $this->addFlash('success', 'Véhicule supprimé avec succès !');
            }
        }

        return $this->redirectToRoute('admin_vehicule_index');
    }

    // 🔍 API pour récupérer les modèles par marque (AJAX)
    #[Route('/modeles/by-marque/{id}', name: 'admin_vehicule_modeles_by_marque', methods: ['GET'])]
    public function getModelesByMarque(int $id, ModeleRepository $modeleRepository): Response
    {
        $modeles = $modeleRepository->findByMarque($id);

        $data = [];
        foreach ($modeles as $modele) {
            $data[] = [
                'id'  => $modele->getIdModele(),
                'nom' => $modele->getNomModele(),
            ];
        }

        return $this->json($data);
    }
}