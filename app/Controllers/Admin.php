<?php

namespace App\Controllers;

class Admin extends BaseController
{
    public function dashboard(): string
    {
        $data = [
            'total_comptes'   => 42,
            'prefixes'        => [],
            'nb_operations'   => 156,
            'gains_retrait'   => 45000,
            'gains_transfert' => 128000,
            'comptes'         => [],
        ];

        return view('admin/dashboard', $data);
    }

    public function prefixes(): string
    {
        $data = [
            'prefixes' => [],
        ];

        return view('admin/prefixes', $data);
    }

    public function ajouterPrefixe()
    {
        $prefixe = $this->request->getPost('prefixe');

        // TODO: validation + insertion en BDD

        return redirect()->to('/admin/prefixes')->with('success', 'Préfixe ajouté.');
    }

    public function supprimerPrefixe()
    {
        $id = $this->request->getPost('id');

        // TODO: suppression en BDD

        return redirect()->to('/admin/prefixes')->with('success', 'Préfixe supprimé.');
    }

    public function baremes(): string
    {
        $data = [
            'bareme'  => null,
            'baremes' => [],
        ];

        return view('admin/baremes', $data);
    }

    public function sauvegarderBareme()
    {
        $id             = $this->request->getPost('id');
        $typeOperation  = $this->request->getPost('type_operation');
        $tranches       = $this->request->getPost('tranches');

        // TODO: validation + insert/update en BDD

        return redirect()->to('/admin/baremes')->with('success', 'Barème enregistré.');
    }

    public function modifierBareme($id)
    {
        $data = [
            'bareme'  => ['id' => $id, 'type_operation' => 'retrait', 'tranches' => [
                ['min_montant' => 0, 'max_montant' => 10000, 'frais' => 1.5],
                ['min_montant' => 10001, 'max_montant' => 50000, 'frais' => 2.0],
            ]],
            'baremes' => [],
        ];

        // TODO: charger le bareme depuis la BDD

        return view('admin/baremes', $data);
    }

    public function supprimerBareme()
    {
        $id = $this->request->getPost('id');

        // TODO: suppression en BDD

        return redirect()->to('/admin/baremes')->with('success', 'Barème supprimé.');
    }

    public function logout()
    {
        // TODO: détruire la session

        return redirect()->to('/');
    }
}
