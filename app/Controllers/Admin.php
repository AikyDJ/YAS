<?php

namespace App\Controllers;

class Admin extends BaseController
{
    public function dashboard(): string
    {
        $data = [
            'total_comptes'   => 0,
            'prefixes'        => [],
            'gains_retrait'   => 0,
            'gains_transfert' => 0,
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

    public function supprimerPrefixe($id)
    {
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
        $montantMin1    = $this->request->getPost('montant_min_1');
        $montantMax1    = $this->request->getPost('montant_max_1');
        $fraisPct1      = $this->request->getPost('frais_pct_1');
        $montantMin2    = $this->request->getPost('montant_min_2');
        $montantMax2    = $this->request->getPost('montant_max_2');
        $fraisPct2      = $this->request->getPost('frais_pct_2');

        // TODO: validation + insert/update en BDD

        return redirect()->to('/admin/baremes')->with('success', 'Barème enregistré.');
    }

    public function modifierBareme($id)
    {
        $data = [
            'bareme'  => ['id' => $id, 'type_operation' => '', 'montant_min' => '', 'montant_max' => '', 'frais_pct' => ''],
            'baremes' => [],
        ];

        // TODO: charger le bareme depuis la BDD

        return view('admin/baremes', $data);
    }

    public function supprimerBareme($id)
    {
        // TODO: suppression en BDD

        return redirect()->to('/admin/baremes')->with('success', 'Barème supprimé.');
    }

    public function logout()
    {
        // TODO: détruire la session

        return redirect()->to('/');
    }
}
