<?php
namespace App\Service;

use app\Models\Client;
use app\Models\Operation;
use app\Models\Typeoperation;
use app\Models\Views\Operationclient;
use app\Models\Views\Soldeclient;

class AdminService{
    private $client;
    private $operation;
    private $typeoperation;
    private $soldeclient;

    public function getSituationGain(){
        $db = \Config\Database::connect();

        $totalFrais = $db->table('v_operation_client v')
            ->select('v.nom_client_primaire, v.prenom_client_primaire, v.type_operation, SUM(v.montant_frais) AS total_frais, COUNT(v.id_operation) AS nb_operations')
            ->groupBy('v.id_client_primaire, v.type_operation')
            ->get()
            ->getResultArray();

        $gainsTotal = $db->table('v_operation_client')
            ->select('SUM(montant_frais) AS total_gains')
            ->get()
            ->getRowArray();

        return [
            'par_client'  => $totalFrais,
            'total_gains' => $gainsTotal['total_gains'] ?? 0,
        ];
    }

    public function addNewPrefix($data){
        $db = \Config\Database::connect();

        $exists = $db->table('operateur')->where('code_operateur', $data['code_operateur'])->countAllResults();

        if($exists > 0){
            return ['success' => false, 'message' => 'Préfixe existant'];
        }

        $db->table('operateur')->insert([
            'nom'            => $data['nom'],
            'code_operateur' => $data['code_operateur'],
        ]);

        return ['success' => true, 'message' => 'Préfixe ajouté.'];
    }

    public function createFraisTranche($data){
        $db = \Config\Database::connect();

        $exists = $db->table('frais_barem')
            ->where('min_montant <=', $data['max_montant'])
            ->where('max_montant >=', $data['min_montant'])
            ->countAllResults();

        if($exists > 0){
            return ['success' => false, 'message' => 'Chevauchement avec une tranche existante.'];
        }

        $db->table('frais_barem')->insert([
            'montant'     => $data['montant'],
            'min_montant' => $data['min_montant'],
            'max_montant' => $data['max_montant'],
        ]);

        return ['success' => true, 'message' => 'Tranche de frais créée.'];
    }


}