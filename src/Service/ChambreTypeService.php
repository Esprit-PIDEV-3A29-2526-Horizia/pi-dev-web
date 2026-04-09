<?php

namespace App\Service;

class ChambreTypeService
{
    public function getChambreType(int $adultes, int $enfants, int $nombreChambres): string
    {
        $totalPersonnes = $adultes + $enfants;
        if ($nombreChambres <= 0) $nombreChambres = 1;
        $personnesParChambre = ceil($totalPersonnes / $nombreChambres);

        if ($personnesParChambre == 1) {
            $type = "chambre simple";
        } elseif ($personnesParChambre == 2) {
            $type = "chambre double";
        } elseif ($personnesParChambre <= 3) {
            $type = "chambre triple";
        } else {
            $type = "chambre familiale";
        }

        $chambresTexte = $nombreChambres . " chambre" . ($nombreChambres > 1 ? "s" : "");
        return $chambresTexte . " (" . $type . ")";
    }
}