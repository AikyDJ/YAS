<?php

namespace App\Service;

use App\Models\Client;
use App\Models\Operation;

class AuthService
{
    private $clientModel;
    private $operationModel;

    public function __construct()
    {
        $this->clientModel    = new Client();
        $this->operationModel = new Operation();
    }

    /**
     * Parse un numéro de téléphone et retourne [code_operateur, code_client]
     * ou null si le format est invalide.
     *
     * Formats acceptés :
     *   +261331234567  →  code_operateur = 33,  code_client = 1234567
     *   0331234567     →  code_operateur = 33,  code_client = 1234567
     */
    private function parseTelephone(string $telephone): ?array
    {
        $number = preg_replace('/[\s\-]/', '', $telephone);

        // +261331234567 → 331234567
        if (preg_match('/^\+261(\d{9,10})$/', $number, $m)) {
            $number = $m[1];
        }
        // 0331234567 → 331234567
        elseif (preg_match('/^0(\d{9,10})$/', $number, $m)) {
            $number = $m[1];
        }

        // 331234567 → code_operateur = 33 (2 chiffres), code_client = 1234567
        if (preg_match('/^(\d{2})(\d{7})$/', $number, $m)) {
            return [
                'code_operateur' => (int) $m[1],
                'code_client'    => $m[2],
            ];
        }

        return null;
    }

    /**
     * Authentifie un client avec son téléphone et son code secret.
     *
     * @return array|null  Données du client si OK, null sinon.
     */
    public function authenticate(string $telephone, string $codeSecret): ?array
    {
        try {
            $parsed = $this->parseTelephone($telephone);
            if ($parsed === null) {
                return null;
            }

            $db = $this->clientModel->db();

            $sql = 'SELECT c.* FROM client c '
                 . 'JOIN operateur o ON c.id_operateur = o.id '
                 . 'WHERE c.code_client = :code_client: '
                 . 'AND c.code_secret = :code_secret: '
                 . 'AND o.code_operateur = :code_operateur:';

            $result = $db->query($sql, [
                'code_client'    => $parsed['code_client'],
                'code_secret'    => $codeSecret,
                'code_operateur' => $parsed['code_operateur'],
            ]);

            $row = $result->getRowArray();

            return $row ?: null;
        } catch (\Exception $e) {
            throw new \Exception("Erreur lors de l'authentification : " . $e->getMessage());
        }
    }
}

