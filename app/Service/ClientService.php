<?php

namespace App\Service;

use App\Models\Client;
use App\Models\Operation;
use App\Models\Typeoperation;
use App\Models\Fraitbarem;
use App\Models\Views\Clientoperateur;
use App\Models\Views\Operationclient;
use App\Models\Views\Soldeclient;
use App\Models\Views\Soldeclienthistorique;

class ClientService
{
    private $clientModel;
    private $operationModel;
    private $typeOperationModel;
    private $fraisBaremModel;

    private $clientOperateurModel;
    private $soldeClientModel;
    private $operationClientModel;
    private $soldeHistoriqueModel;

    public function __construct()
    {
        $this->clientModel        = new Client();
        $this->operationModel     = new Operation();
        $this->typeOperationModel = new Typeoperation();
        $this->fraisBaremModel    = new Fraitbarem();
        $this->soldeClientModel   = new Soldeclient();
        $this->operationClientModel = new Operationclient();
        $this->clientOperateurModel = new Clientoperateur();
        $this->soldeHistoriqueModel = new Soldeclienthistorique();
    }

    /**
     * Récupère les détails d'un client par son code_client.
     */
    public function getClientDetails(string $code_client): ?array
    {
        return $this->clientModel
            ->where('code_client', $code_client)
            ->first();
    }

    /**
     * Récupère les détails d'un client par son ID.
     */
    public function getClientById(int $id): ?array
    {
        return $this->clientModel->find($id);
    }
    // get client operateur by id
    public function getClientOperateurById(int $id): ?array
    {
        return $this->clientOperateurModel
            ->where('id_client', $id)
            ->first();
    }
    /**
     * Récupère le solde actuel d'un client via la vue v_solde_client.
     */
    public function getSolde(int $id_client): float
    {
        $row = $this->soldeClientModel
            ->where('id_client', $id_client)
            ->first();

        return $row ? (float) $row['solde_actuel'] : 0.0;
    }

    /**
     * Récupère l'historique des opérations d'un client pour l'affichage dashboard.
     * Retourne des lignes avec : date, type, montant, solde_jour.
     */
    public function getOperations(int $id_client): array
    {
        $rows = $this->operationClientModel
            ->where('id_client_primaire', $id_client)
            ->orderBy('date_operation', 'DESC')
            ->findAll();

        $operations = [];
        foreach ($rows as $row) {
            $operations[] = [
                'date'      => $row['date_operation'],
                'type'      => $row['type_operation'],
                'montant'   => $row['montant'],
                'solde_jour' => $this->getSoldeAtDate($id_client, $row['date_operation']),
            ];
        }

        return $operations;
    }

    /**
     * Récupère le solde cumulé à une date donnée via v_solde_client_historique.
     */
    private function getSoldeAtDate(int $id_client, string $date): float
    {
        $row = $this->soldeHistoriqueModel
            ->where('id_client', $id_client)
            ->where('date_operation', $date)
            ->orderBy('id_operation', 'DESC')
            ->first();

        return $row ? (float) $row['solde_cumule'] : 0.0;
    }

    /**
     * Vérifie le code secret d'un client.
     */
    public function verifyCodeSecret(int $id_client, string $code_secret): bool
    {
        $client = $this->clientModel->find($id_client);
        return $client && $client['code_secret'] === $code_secret;
    }

    /**
     * Calcule les frais根据 le montant et la bareme.
     */
    public function calculerFrais(float $montant): float
    {
        $bareme = $this->fraisBaremModel
            ->where('min_montant <=', $montant)
            ->where('max_montant >=', $montant)
            ->first();

        return $bareme ? (float) $bareme['montant'] : 0.0;
    }

    /**
     * Récupère l'ID du type d'opération par son nom (depot, retrait, transfaire).
     */
    public function getTypeOperationId(string $nom): ?int
    {
        $type = $this->typeOperationModel
            ->where('LOWER(nom)', strtolower($nom))
            ->first();

        return $type ? (int) $type['id'] : null;
    }

    /**
     * Insère une opération (dépôt ou retrait).
     */
    public function insertOperation(int $id_client, string $type_nom, float $montant, string $code_secret): array
    {
        if (!$this->verifyCodeSecret($id_client, $code_secret)) {
            return ['success' => false, 'message' => 'Code secret incorrect.'];
        }

        $type_id = $this->getTypeOperationId($type_nom);
        if ($type_id === null) {
            return ['success' => false, 'message' => 'Type d\'opération inconnu.'];
        }

        $solde = $this->getSolde($id_client);
        $frais = $this->calculerFrais($montant);

        if (strtolower($type_nom) === 'retrait' && ($montant + $frais) > $solde) {
            return ['success' => false, 'message' => 'Solde insuffisant pour ce retrait.'];
        }

        $data = [
            'id_primary_client'   => $id_client,
            'id_secondary_client' => null,
            'id_type_operation'   => $type_id,
            'montant'             => $montant,
            'montant_frais'       => $frais,
            'date_operation'      => date('Y-m-d'),
        ];

        $this->operationModel->insert($data);

        return ['success' => true, 'message' => 'Opération effectuée avec succès.'];
    }

    /**
     * Insère un transfert entre deux clients.
     */
    public function insertTransfert(int $id_emetteur, string $code_destinataire, float $montant, string $code_secret): array
    {
        if (!$this->verifyCodeSecret($id_emetteur, $code_secret)) {
            return ['success' => false, 'message' => 'Code secret incorrect.'];
        }

        $destinataire = $this->getClientDetails($code_destinataire);
        if (!$destinataire) {
            return ['success' => false, 'message' => 'Destinataire introuvable.'];
        }

        if ((int) $destinataire['id'] === $id_emetteur) {
            return ['success' => false, 'message' => 'Vous ne pouvez pas vous transférer à vous-même.'];
        }

        $type_id = $this->getTypeOperationId('transfaire');
        if ($type_id === null) {
            return ['success' => false, 'message' => 'Type d\'opération inconnu.'];
        }

        $solde = $this->getSolde($id_emetteur);
        $frais = $this->calculerFrais($montant);

        if (($montant + $frais) > $solde) {
            return ['success' => false, 'message' => 'Solde insuffisant pour ce transfert.'];
        }

        $data = [
            'id_primary_client'   => $id_emetteur,
            'id_secondary_client' => (int) $destinataire['id'],
            'id_type_operation'   => $type_id,
            'montant'             => $montant,
            'montant_frais'       => $frais,
            'date_operation'      => date('Y-m-d'),
        ];

        $this->operationModel->insert($data);

        return ['success' => true, 'message' => 'Transfert effectué avec succès.'];
    }
}
