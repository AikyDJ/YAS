<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if ($this->request->getMethod() === 'post') {
            $telephone  = $this->request->getPost('telephone');
            $codeSecret = $this->request->getPost('code_secret');

            // TODO: vérifier identifiants en BDD
            // Si admin → redirect admin/dashboard
            // Si client → redirect client/dashboard

            return redirect()->to('/client/dashboard');
        }

        return view('index');
    }
}
