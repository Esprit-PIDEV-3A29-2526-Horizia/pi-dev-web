<?php
namespace App\Service;
class ChambreTypeService
{
    public function getChambreType(int $adultes, int $enfants, int $nombreChambres): string
    {
        $totalPersonnes = $adultes + $enfants;
        $maxParChambre = 4;

        // Nombre de chambres minimal nécessaire
        $chambresNecessaires = (int) ceil($totalPersonnes / $maxParChambre);
        // On utilise le maximum entre le nombre demandé et le nécessaire
        $chambresEffectives = max($nombreChambres, $chambresNecessaires);

        // Répartir les personnes dans les chambres (au plus 4 par chambre)
        $personnesRestantes = $totalPersonnes;
        $types = [];
        for ($i = 0; $i < $chambresEffectives; $i++) {
            if ($personnesRestantes <= 0) {
                $types[] = "chambre vide";
                continue;
            }
            $capacite = min($personnesRestantes, $maxParChambre);
            if ($capacite == 1) {
                $types[] = "simple";
            } elseif ($capacite == 2) {
                $types[] = "double";
            } elseif ($capacite == 3) {
                $types[] = "triple";
            } else {
                $types[] = "quadruple";
            }
            $personnesRestantes -= $capacite;
        }

        // Compter les occurrences de chaque type (ignore les chambres vides)
        $compteur = array_count_values($types);
        $description = [];
        foreach ($compteur as $type => $count) {
            if ($type == 'chambre vide') continue;
            $description[] = $count . " chambre" . ($count > 1 ? "s" : "") . " " . $type;
        }

        $result = implode(" + ", $description);
        if (empty($result)) {
            $result = $chambresEffectives . " chambre" . ($chambresEffectives > 1 ? "s" : "");
        }
        return $result;
    }
}