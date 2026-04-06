<?php
namespace App\Twig;

use App\Service\DashboardService;
use App\Service\PlanningService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class StatsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private DashboardService $dash,
        private PlanningService $planning
    ) {}

    public function getGlobals(): array
    {
        $m = (int) date('m'); $y = (int) date('Y');
        return [
            'vehiculesDisponibles' => $this->dash->getNombreVehiculesDisponibles(),
            'vehiculesLoues'       => $this->dash->getNombreVehiculesLoues(),
            'locationsActives'     => $this->dash->getNombreLocationsActives(),
            'caMois'               => $this->planning->calculerCADuMois($y, $m),
            'tauxOccupation'       => round($this->planning->calculerTauxOccupation($y, $m), 1),
            'topModele'            => $this->dash->getModeleLePlusLoue(),
            'statuts'              => $this->dash->getLocationsParStatut(),
            'moisNom'              => $this->planning->getNomMois($m, $y),
        ];
    }
}