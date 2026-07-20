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
            'prefix'         => $this->request->getPost('prefixe'),
        ]);

        $type = $result['success'] ? 'success' : 'error';
        return redirect()->to('/admin/prefixes')->with($type, $result['message']);
    }

    public function supprimerPrefixe()
    {
        $result = $this->adminService->deletePrefix((int) $this->request->getPost('id'));
        return redirect()->to('/admin/prefixes')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function baremes(): string
    {
        $data = [
            'bareme'  => null,
            'baremes' => $this->adminService->getBaremes(),
            'types'   => $this->adminService->getTypeOperations(),
        ];

        return view('admin/baremes', $data);
    }

    public function sauvegarderBareme()
    {
        $result = $this->adminService->saveBareme([
            'id'               => $this->request->getPost('id'),
            'id_type_operation'=> $this->request->getPost('id_type_operation'),
            'montant'          => $this->request->getPost('montant'),
            'min_montant'      => $this->request->getPost('min_montant'),
            'max_montant'      => $this->request->getPost('max_montant'),
        ]);

        return redirect()->to('/admin/baremes')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function modifierBareme($id)
    {
        $bareme = $this->adminService->getBaremeById($id);

        $data = [
            'bareme'  => $bareme,
            'baremes' => $this->adminService->getBaremes(),
            'types'   => $this->adminService->getTypeOperations(),
        ];

        return view('admin/baremes', $data);
    }

    public function supprimerBareme()
    {
        $id = $this->request->getPost('id');
        $bareme = $this->adminService->getBaremeById($id);
        if ($bareme === null) {
            return redirect()->to('/admin/baremes')->with('error', 'Barème introuvable.');
        }
        \Config\Database::connect()->table('frais_barem')->where('id', $bareme['id'])->delete();
        return redirect()->to('/admin/baremes')->with('success', 'Barème supprimé.');
    }

    public function types(): string
    {
        $data = [
            'types' => $this->adminService->getTypeOperations(),
        ];

        return view('admin/types', $data);
    }

    public function ajouterTypeOperation()
    {
        $result = $this->adminService->saveTypeOperation([
            'nom' => $this->request->getPost('nom'),
            'code_type_operation' => $this->request->getPost('code_type_operation'),
        ]);

        return redirect()->to('/admin/types')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }
}
