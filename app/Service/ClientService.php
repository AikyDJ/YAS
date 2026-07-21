<?php

namespace App\Service;

use App\Models\Client;
use App\Models\Operation;
use App\Models\Typeoperation;
use App\Models\Fraitbarem;
use App\Models\Operateur;
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
    private $operateurModel;
    private $clientOperateurModel;
    private $soldeClientModel;
    private $operationClientModel;
    private $soldeHistoriqueModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->operationModel = new Operation();
        $this->typeOperationModel = new Typeoperation();
        $this->operateurModel = new Operateur();
        $this->fraisBaremModel = new Fraitbarem();
        $this->soldeClientModel = new Soldeclient();
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
        return $this->clientOperateurModel->where('id_client', $id)->first();
    }
    /**
     * Récupère le solde actuel d'un client via la vue v_solde_client.
     */
    public function getSolde(int $id_client): float
    {
        $db = $this->clientModel->db();
        $row = $db->query(
            "SELECT COALESCE(SUM(CASE
                WHEN LOWER(t.nom) = 'depot' THEN o.montant
                WHEN LOWER(t.nom) IN ('retrait', 'transfaire') THEN -o.montant - o.montant_frais
                ELSE 0 END), 0) AS solde
             FROM operation o JOIN type_operation t ON t.id = o.id_type_operation
             WHERE o.id_primary_client = ?",
            [$id_client]
        )->getRowArray();
        $received = $db->query(
            "SELECT COALESCE(SUM(o.montant), 0) AS solde
             FROM operation o JOIN type_operation t ON t.id = o.id_type_operation
             WHERE o.id_secondary_client = ? AND LOWER(t.nom) = 'transfaire'",
            [$id_client]
        )->getRowArray();

        return (float) ($row['solde'] ?? 0) + (float) ($received['solde'] ?? 0);
    }

    /**
     * Récupère l'historique des opérations d'un client pour l'affichage dashboard.
     * Retourne des lignes avec : date, type, montant, solde_jour.
     */
    public function getOperations(int $id_client): array
    {
        $rows = $this->operationModel
            ->select('operation.id, operation.date_operation, operation.montant, type_operation.nom AS type_operation')
            ->join('type_operation', 'type_operation.id = operation.id_type_operation')
            ->where('operation.id_primary_client', $id_client)
            ->orderBy('operation.date_operation', 'DESC')
            ->findAll();

        $operations = [];
        foreach ($rows as $row) {
            $operations[] = [
                'date' => $row['date_operation'],
                'type' => $row['type_operation'],
                'montant' => $row['montant'],
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
        return $this->getSolde($id_client);
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
     * Calcule les frais selon le montant et la bareme.
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
        $result = ['success' => true, 'message' => 'Opération effectuée avec succès.'];
        try {


            if (!$this->verifyCodeSecret($id_client, $code_secret)) {
                $result = ['error' => true, 'message' => 'Code secret incorrect.'];
                throw new \Exception('Code secret incorrect.');
            }

            $type_id = $this->getTypeOperationId($type_nom);
            if ($type_id === null) {
                $result = ['error' => true, 'message' => 'Type d\'opération inconnu.'];
                throw new \Exception('Type d\'opération inconnu.');
            }

            $solde = $this->getSolde($id_client);
            $frais = $this->calculerFrais($montant);

            if (strtolower($type_nom) === 'retrait' && ($montant + $frais) > $solde) {
                $result = ['error' => true, 'message' => 'Solde insuffisant pour ce retrait.'];
                throw new \Exception('Solde insuffisant pour ce retrait.');
            }

            $data = [
                'id_primary_client' => $id_client,
                'id_secondary_client' => null,
                'id_type_operation' => $type_id,
                'montant' => $montant,
                'montant_frais' => $frais,
                'montant_comission' => 0.0,
                'date_operation' => date('Y-m-d'),
            ];

            $this->operationModel->insert($data);
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => 'Erreur lors de l\'opération : ' . $e->getMessage()];
        }
        return $result;
    }

    /**
     * Insère un transfert entre deux clients.
     */
    public function insertTransfert(int $id_emetteur, string $code_destinataire, float $montant, string $code_secret): array
    {
        $result = ['error' => false, 'message' => 'Transfert effectué avec succès.'];
        try {


            if ($montant <= 0 || !$this->verifyCodeSecret($id_emetteur, $code_secret)) {
                $result = ['error' => true, 'message' => 'Montant ou code secret incorrect.'];
                throw new \Exception('Montant ou code secret incorrect.');
            }

            $code_destinataire = preg_replace('/\D/', '', $code_destinataire);
            if (strlen($code_destinataire) === 10 && $code_destinataire[0] === '0') {
                $code_destinataire = substr($code_destinataire, 3);
            }
            $destinataire = $this->getClientDetails($code_destinataire);
            if (!$destinataire) {
                $result = ['error' => true, 'message' => 'Destinataire introuvable.'];
                throw new \Exception('Destinataire introuvable.');
            }
            // verifier detinater meme operateur
            $emetteur = $this->getClientById($id_emetteur);


            if ((int) $destinataire['id'] === $id_emetteur) {
                $result = ['error' => true, 'message' => 'Vous ne pouvez pas vous transférer à vous-même.'];
                throw new \Exception('Vous ne pouvez pas vous transférer à vous-même.');
            }

            $type_id = $this->getTypeOperationId('transfaire');
            if ($type_id === null) {
                $result = ['error' => true, 'message' => 'Type d\'opération inconnu.'];
                throw new \Exception('Type d\'opération inconnu.');
            }

            $solde = $this->getSolde($id_emetteur);
            $frais = $this->calculerFrais($montant);
            $comission = $this->calculerComission($montant, $emetteur['id_operateur'], $destinataire['id_operateur']);

            if (($montant + $frais + $comission) > $solde) {
                $result = ['error' => true, 'message' => 'Solde insuffisant pour ce transfert.'];
                throw new \Exception('Solde insuffisant pour ce transfert.');
            }

            $data = [
                'id_primary_client' => $id_emetteur,
                'id_secondary_client' => (int) $destinataire['id'],
                'id_type_operation' => $type_id,
                'montant' => $montant,
                'montant_frais' => $frais,
                'montant_comission' => $comission,
                'date_operation' => date('Y-m-d'),
            ];

            $this->operationModel->insert($data);
        } catch (\Exception $e) {
            $result = ['success' => false, 'message' => 'Erreur lors du transfert : ' . $e->getMessage()];
            throw $e;
        }
        return $result;
    }
    public function calculerComission(float $montant, int $id_emetteur, int $id_destinataire): float
    {
        if ($id_emetteur !== $id_destinataire) {
            $destinataireOperateur = $this->operateurModel->find($id_destinataire);
            return $montant * (($destinataireOperateur['comission_ptc'] ?? 0) / 100);
        }
        return 0.0;
    }

    /**
     * Insère plusieurs transferts en une seule opération.
     * Tous les destinataires doivent être du même opérateur que l'émetteur.
     *
     * @param int    $id_emetteur   ID du client émetteur
     * @param array  $destinataires [['code_client' => '033...', 'montant' => 5000], ...]
     * @param string $code_secret   Code secret de l'émetteur
     * @param float  $montant       Montant total pour tous les transferts
     */
    public function insertMultipleTransferts(int $id_emetteur, array $destinataires, string $code_secret, float $montant): array
    {
        try {
            $msg = ['success' => false, 'message' => 'Erreur lors du transfert.'];
            if (!$this->verifyCodeSecret($id_emetteur, $code_secret)) {
                $msg = ['success' => false, 'message' => 'Code secret incorrect.'];
                throw new \Exception('Code secret incorrect.');
            }

            if (empty($destinataires)) {
                $msg = ['success' => false, 'message' => 'Aucun destinataire fourni.'];
                throw new \Exception('Aucun destinataire fourni.');
            }

            $emetteur = $this->getClientById($id_emetteur);
            if (!$emetteur) {
                $msg = ['success' => false, 'message' => 'Client émetteur introuvable.'];
                throw new \Exception('Client émetteur introuvable.');
            }

            $id_operateur_emetteur = (int) $emetteur['id_operateur'];
            $type_id = $this->getTypeOperationId('transfaire');
            if ($type_id === null) {
                $msg = ['success' => false, 'message' => 'Type d\'opération inconnu.'];
                throw new \Exception('Type d\'opération inconnu.');
            }

            $results = [];
            $db = $this->operationModel->db();
            $db->transStart();
            $montant = $montant / (int) count($destinataires);

            foreach ($destinataires as $i => $dest) {
                $code_dest = preg_replace('/\D/', '', $dest['code_client'] ?? '');
                if (strlen($code_dest) === 10 && $code_dest[0] === '0') {
                    $code_dest = substr($code_dest, 3);
                }

                $destinataire = $this->getClientDetails($code_dest);
                if (!$destinataire) {
                    $results[] = ['index' => $i, 'success' => false, 'message' => "Destinataire #$code_dest introuvable."];
                    continue;
                }

                if ((int) $destinataire['id'] === $id_emetteur) {
                    $results[] = ['index' => $i, 'success' => false, 'message' => "Impossible de se transférer à soi-même."];
                    continue;
                }

                if ((int) $destinataire['id_operateur'] !== $id_operateur_emetteur) {
                    $results[] = ['index' => $i, 'success' => false, 'message' => "Destinataire #$code_dest n'est pas du même opérateur."];
                    continue;
                }

                if ($montant <= 0) {
                    $results[] = ['index' => $i, 'success' => false, 'message' => "Montant invalide pour le destinataire #$code_dest."];
                    continue;
                }

                $frais = $this->calculerFrais($montant);
                $comission = $this->calculerComission($montant, $id_operateur_emetteur, (int) $destinataire['id_operateur']);

                $solde = $this->getSolde($id_emetteur);
                if (($montant + $frais + $comission) > $solde) {
                    $results[] = ['index' => $i, 'success' => false, 'message' => "Solde insuffisant pour le transfert #$code_dest."];
                    continue;
                }

                $data = [
                    'id_primary_client' => $id_emetteur,
                    'id_secondary_client' => (int) $destinataire['id'],
                    'id_type_operation' => $type_id,
                    'montant' => $montant,
                    'montant_frais' => $frais,
                    'montant_comission' => $comission,
                    'date_operation' => date('Y-m-d'),
                ];

                $this->operationModel->insert($data);
                $results[] = ['index' => $i, 'success' => true, 'message' => "Transfert de $montant Ar vers #$code_dest effectué."];
            }

            $db->transComplete();

            $allSuccess = empty(array_filter($results, fn($r) => !$r['success']));

            return [
                'success' => $allSuccess,
                'message' => $allSuccess
                    ? 'Tous les transferts ont été effectués.'
                    : 'Certains transferts ont échoué.',
                'results' => $results,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors du transfert : ' . $e->getMessage()];
        }
    }
}
