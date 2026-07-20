<?php
namespace App\Service;
use app\Models\Fraitbarem;

class OperationService
{
    private $fraisBaremModel;
    public function __construct()
    {
        $this->fraisBaremModel = new Fraitbarem();
    }
    public function getfraisTranche($montant)
    {

        $frais = $this->fraisBaremModel->where('min_montant <=', $montant)
            ->where('max_montant >=', $montant)
            ->get()
            ->getRowArray();

        return $frais ? (float) $frais['frais'] : 0;
    }

    public function getAllFraisTranches()
    {
        return $this->fraisBaremModel->findAll();
    }
}