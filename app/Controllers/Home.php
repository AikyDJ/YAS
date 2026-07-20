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
        if (session()->get('id_client')) {
            return redirect()->to('/client/dashboard');
        }
        return view('index');
    }

    public function auth(){
            $telephone  = trim((string) $this->request->getPost('telephone'));
            $codeSecret = trim((string) $this->request->getPost('code_secret'));

            if ($telephone === '' || !preg_match('/^\d{4}$/', $codeSecret)) {
                return redirect()->to('/')->with('error', 'Numéro ou code secret invalide.');
            }

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
