<?php

namespace App\Controllers;

class Client extends BaseController
{
    public function dashboard(): string
    {
        $data = [
            'nom'        => 'Rakoto',
            'prenom'     => 'Jean',
            'code_client'=> '0331234',
            'solde'      => 125000,
            'monnaie'    => 'Ar',
            'operations' => [],
            'frais'      => [
                ['min' => 0,     'max' => 10000,  'pct' => 1.5],
                ['min' => 10001, 'max' => 50000,  'pct' => 2.0],
                ['min' => 50001, 'max' => 100000, 'pct' => 2.5],
                ['min' => 100001,'max' => 500000, 'pct' => 3.0],
            ],
        ];

        return view('user/dahsboard', $data);
    }

    public function procederOperation()
    {
        $type = $this->request->getPost('type_operation');
        $montant = $this->request->getPost('montant');
        $codeSecret = $this->request->getPost('code_secret');

        // TODO: validation + traitement (depot/retrait)

        return redirect()->to('/client/dashboard')->with('success', 'Opération effectuée avec succès.');
    }

    public function procederTransfert()
    {
        $destinataire = $this->request->getPost('destinataire');
        $montant = $this->request->getPost('montant');
        $codeSecret = $this->request->getPost('code_secret');

        // TODO: validation + traitement transfert

        return redirect()->to('/client/dashboard')->with('success', 'Transfert effectué avec succès.');
    }

    public function logout()
    {
        // TODO: détruire la session client

        return redirect()->to('/');
    }
}
