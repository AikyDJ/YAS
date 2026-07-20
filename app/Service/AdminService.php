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

        $totalFrais = $db->table('operation o')
            ->select('c.nom, c.prenom, o.id_type_operation, t.nom AS type_op, SUM(o.montant_frais) AS total_frais, COUNT(o.id) AS nb_operations')
            ->join('client c', 'c.id = o.id_primary_client')
            ->join('type_operation t', 't.id = o.id_type_operation')
            ->groupBy('c.id, o.id_type_operation')->get()->getResultArray();

        $gainsTotal = $db->table('operation')
            ->select('SUM(montant_frais) AS total_gains')->get()->getRowArray();

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

    public function createFraisTranche(){
        $db = \Config\Database::connect();
    }


}