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
        $data = $this->adminService->getDashboardData();

        $gains = $this->adminService->getSituationGain();
        $data['total_gains'] = $gains['total_gains'] ?? 0;

        return view('admin/dashboard', $data);
    }

    public function prefixes(): string
    {
        $data = [
            'prefixes' => $this->adminService->getPrefixes(),
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
        $data = [
            'bareme'  => null,
            'baremes' => $this->adminService->getBaremes(),
        ];

        return view('admin/baremes', $data);
    }

    public function sauvegarderBareme()
    {
        $tranches = $this->request->getPost('tranches');
        $saved = false;

        if (!empty($tranches)) {
            foreach ($tranches as $tranche) {
                if (!empty($tranche['min']) && !empty($tranche['max']) && !empty($tranche['frais'])) {
                    $result = $this->adminService->createFraisTranche([
                        'montant'     => $tranche['frais'],
                        'min_montant' => $tranche['min'],
                        'max_montant' => $tranche['max'],
                    ]);

                    $saved = $saved || !empty($result['success']);
                }
            }
        }

        return redirect()->to('/admin/baremes')->with('success', $saved ? 'Barème enregistré.' : 'Aucune tranche valide à enregistrer.');
    }

    public function modifierBareme($id)
    {
        $bareme = $this->adminService->getBaremeById($id);

        $data = [
            'bareme'  => $bareme,
            'baremes' => $this->adminService->getBaremes(),
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
