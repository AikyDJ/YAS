<?php

namespace App\Controllers;

use App\Service\ClientService;
use App\Service\OperationService;

class Client extends BaseController
{
    private $clientService;
    private $operationService;

    public function __construct()
    {
        $this->clientService = new ClientService();
        $this->operationService = new OperationService();
    }

    public function dashboard(): string
    {
        $id_client = session()->get('id_client');
        $client = $id_client ? $this->clientService->getClientOperateurById($id_client) : null;
        $data = [
            'solde'      => $id_client ? $this->clientService->getSolde($id_client) : 0,
            'monnaie'    => 'Ar',
            'nom' => $client ? $client['nom'] : '',
            'code_client' => $client ? '+261'. $client['prefix_operateur'] . $client['code_client'] : '',
            'prenom' => $client ? $client['prenom'] : '',
            'operations' => $id_client ? $this->clientService->getOperations($id_client) : [],
            'frais'     => $id_client ? $this->operationService->getAllFraisTranches() : []
        ];

        return view('user/dahsboard', $data);
    }

    public function procederOperation()
    {
        $id_client   = session()->get('id_client');
        $type        = $this->request->getPost('type_operation');
        $montant     = (float) $this->request->getPost('montant');
        $codeSecret  = $this->request->getPost('code_secret');

        $result = $this->clientService->insertOperation($id_client, $type, $montant, $codeSecret);

        if ($result['success']) {
            return redirect()->to('/client/dashboard')->with('success', $result['message']);
        }

        return redirect()->to('/client/dashboard')->with('error', $result['message']);
    }

    public function procederTransfert()
    {
        $id_client     = session()->get('id_client');
        $destinataire  = $this->request->getPost('destinataire');
        $montant       = (float) $this->request->getPost('montant');
        $codeSecret    = $this->request->getPost('code_secret');

        $result = $this->clientService->insertTransfert($id_client, $destinataire, $montant, $codeSecret);

        if ($result['success']) {
            return redirect()->to('/client/dashboard')->with('success', $result['message']);
        }

        return redirect()->to('/client/dashboard')->with('error', $result['message']);
    }
}
