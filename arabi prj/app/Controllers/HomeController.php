<?php

namespace App\Controllers;

use App\Core\Auth;

class HomeController
{
    public function index(): void
    {
        $loggedIn = Auth::check();
        $dashboardUrl = '';
        $dashboardLabel = 'Mon espace';
        if ($loggedIn) {
            $role = Auth::role();
            $dashboardUrl = $role === 'admin' ? '/admin/stats' : ($role === 'recruiter' ? '/recruiter/jobs' : '/candidate/profile');
            $dashboardLabel = $role === 'admin' ? 'Tableau de bord' : ($role === 'recruiter' ? 'Mes offres' : 'Mon profil');
        }
        $this->view('home', [
            'loggedIn' => $loggedIn,
            'dashboardUrl' => $dashboardUrl,
            'dashboardLabel' => $dashboardLabel,
        ]);
    }

    private function view(string $name, array $data = []): void
    {
        extract($data);
        require dirname(__DIR__) . '/Views/' . $name . '.php';
    }
}
