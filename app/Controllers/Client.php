<?php

namespace App\Controllers;

class Client extends BaseController
{
    public function dashboard(): string
    {
        $data = [
            'solde'     => 0,
            'monnaie'   => 'FC',
            'operations'=> [],
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
}
