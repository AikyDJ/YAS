<?php

namespace App\Controllers;

use App\Service\AdminService;

class Admin extends BaseController
{
    private $adminService;

    public function __construct()
    {
        $this->adminService = new AdminService();
    }

    public function dashboard(): string
    {
        $db = \Config\Database::connect();

        $gains = $this->adminService->getSituationGain();

        $totalComptes = $db->table('client')->countAllResults();
        $nbOperations = $db->table('operation')->countAllResults();
        $prefixes = $db->table('operateur')->get()->getResultArray();
        $comptes = $db->table('v_solde_client')->get()->getResultArray();

        $data = [
            'total_comptes'   => $totalComptes,
            'prefixes'        => $prefixes,
            'nb_operations'   => $nbOperations,
            'gains_retrait'   => $this->getGainsByType($db, 'retrait'),
            'gains_transfert' => $this->getGainsByType($db, 'transfaire'),
            'comptes'         => $comptes,
        ];

        return view('admin/dashboard', $data);
    }

    private function getGainsByType($db, $type): float
    {
        $result = $db->table('v_operation_client')
            ->select('SUM(montant_frais) AS total')
            ->where('type_operation', $type)
            ->get()
            ->getRowArray();

        return $result['total'] ?? 0;
    }

    public function prefixes(): string
    {
        $db = \Config\Database::connect();

        $data = [
            'prefixes' => $db->table('operateur')->get()->getResultArray(),
        ];

        return view('admin/prefixes', $data);
    }

    public function ajouterPrefixe()
    {
        $result = $this->adminService->addNewPrefix([
            'nom'            => $this->request->getPost('nom'),
            'code_operateur' => $this->request->getPost('prefixe'),
        ]);

        $type = $result['success'] ? 'success' : 'error';
        return redirect()->to('/admin/prefixes')->with($type, $result['message']);
    }

    public function supprimerPrefixe()
    {
        $id = $this->request->getPost('id');
        $db = \Config\Database::connect();

        $db->table('operateur')->where('id', $id)->delete();

        return redirect()->to('/admin/prefixes')->with('success', 'Préfixe supprimé.');
    }

    public function baremes(): string
    {
        $db = \Config\Database::connect();

        $data = [
            'bareme'  => null,
            'baremes' => $db->table('frais_barem')->get()->getResultArray(),
        ];

        return view('admin/baremes', $data);
    }

    public function sauvegarderBareme()
    {
        $tranches = $this->request->getPost('tranches');

        if (!empty($tranches)) {
            foreach ($tranches as $tranche) {
                if (!empty($tranche['min']) && !empty($tranche['max']) && !empty($tranche['frais'])) {
                    $this->adminService->createFraisTranche([
                        'montant'     => $tranche['frais'],
                        'min_montant' => $tranche['min'],
                        'max_montant' => $tranche['max'],
                    ]);
                }
            }
        }

        return redirect()->to('/admin/baremes')->with('success', 'Barème enregistré.');
    }

    public function modifierBareme($id)
    {
        $db = \Config\Database::connect();
        $bareme = $db->table('frais_barem')->where('id', $id)->get()->getRowArray();

        $data = [
            'bareme'  => $bareme,
            'baremes' => $db->table('frais_barem')->get()->getResultArray(),
        ];

        return view('admin/baremes', $data);
    }

    public function supprimerBareme()
    {
        $id = $this->request->getPost('id');
        $db = \Config\Database::connect();

        $db->table('frais_barem')->where('id', $id)->delete();

        return redirect()->to('/admin/baremes')->with('success', 'Barème supprimé.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }
}
