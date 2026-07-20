<?php

namespace App\Controllers;
use App\Service\AuthService;

class Home extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function index()
    {
        return view('index');
    }

    public function auth(){
            $telephone  = $this->request->getPost('telephone');
            $codeSecret = $this->request->getPost('code_secret');

            $authResult = $this->authService->authenticate($telephone, $codeSecret);

            if ($authResult) {
                session()->set('id_client', $authResult['id']);
                return redirect()->to('/client/dashboard');
            } else {
                return redirect()->to('/')->with('error', 'Identifiants invalides');
            }
    }

    public function logout()
    {
        session()->remove('id_client');
        return redirect()->to('/');
    }
}
