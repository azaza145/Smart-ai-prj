<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Validator;
use App\Models\User;
use App\Models\Candidate;
use App\Models\PasswordReset;

class AuthController
{
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        if (!Csrf::verify()) {
            $this->redirect('/login', 'Invalid request.', 'danger');
            return;
        }
        $v = new Validator($_POST);
        $v->required('email', 'password')->email('email');
        if ($v->fails()) {
            $this->view('auth/login', ['title' => 'Login', 'errors' => $v->errors(), 'old' => $_POST]);
            return;
        }
        $user = User::findByEmail($_POST['email']);
        if (!$user || !password_verify($_POST['password'], $user['password_hash'])) {
            $this->view('auth/login', ['title' => 'Login', 'errors' => ['email' => 'Invalid credentials.'], 'old' => $_POST]);
            return;
        }
        Auth::login($user);
        if (Auth::isAdmin()) {
            $this->redirect('/admin/stats', 'Welcome back.', 'success');
        } elseif (Auth::isRecruiter()) {
            $this->redirect('/recruiter/jobs', 'Welcome back.', 'success');
        } else {
            $this->redirect('/candidate/profile', 'Welcome back.', 'success');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/', 'Vous avez été déconnecté.', 'info');
    }

    public function showRegister(): void
    {
        $this->view('auth/register', ['title' => 'Register as Candidate']);
    }

    public function register(): void
    {
        if (!Csrf::verify()) {
            $this->redirect('/register', 'Invalid request.', 'danger');
            return;
        }
        $v = new Validator($_POST);
        $v->required('name', 'email', 'password')->email('email')->min('password', 6)->unique('email', 'users', 'email');
        if ($v->fails()) {
            $this->view('auth/register', ['title' => 'Register', 'errors' => $v->errors(), 'old' => $_POST]);
            return;
        }
        $userId = User::create([
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'role' => 'candidate',
        ]);
        Auth::login(User::find($userId));
        Candidate::createFromRegistration($userId, [
            'nom' => '',
            'prenom' => $_POST['name'],
            'email' => $_POST['email'],
        ]);
        $this->redirect('/candidate/profile', 'Account created. Complete your profile.', 'success');
    }

    public function showForgotPassword(): void
    {
        $this->view('auth/forgot_password', ['title' => 'Mot de passe oublié']);
    }

    public function forgotPassword(): void
    {
        if (!Csrf::verify()) {
            $this->redirect('/forgot-password', 'Requête invalide.', 'danger');
            return;
        }
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $this->view('auth/forgot_password', ['title' => 'Mot de passe oublié', 'errors' => ['email' => 'Indiquez votre email.'], 'old' => $_POST]);
            return;
        }
        $user = User::findByEmail($email);
        if (!$user) {
            $this->redirect('/login', 'Aucun compte associé à cet email.', 'danger');
            return;
        }
        PasswordReset::deleteExpired();
        $token = PasswordReset::create($email);
        $this->redirect('/reset-password?token=' . urlencode($token), 'Définissez votre nouveau mot de passe ci-dessous.', 'info');
    }

    public function showResetPassword(): void
    {
        $token = trim($_GET['token'] ?? '');
        if ($token === '') {
            $this->redirect('/forgot-password', 'Lien invalide ou expiré.', 'danger');
            return;
        }
        $row = PasswordReset::findByToken($token);
        if (!$row) {
            $this->redirect('/forgot-password', 'Lien invalide ou expiré.', 'danger');
            return;
        }
        $this->view('auth/reset_password', ['title' => 'Nouveau mot de passe', 'token' => $token]);
    }

    public function resetPassword(): void
    {
        if (!Csrf::verify()) {
            $this->redirect('/login', 'Requête invalide.', 'danger');
            return;
        }
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        if ($token === '') {
            $this->redirect('/forgot-password', 'Lien invalide.', 'danger');
            return;
        }
        $row = PasswordReset::findByToken($token);
        if (!$row) {
            $this->redirect('/forgot-password', 'Lien invalide ou expiré.', 'danger');
            return;
        }
        $errors = [];
        if (strlen($password) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        if ($password !== $password_confirm) {
            $errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
        }
        if (!empty($errors)) {
            $this->view('auth/reset_password', ['title' => 'Nouveau mot de passe', 'token' => $token, 'errors' => $errors]);
            return;
        }
        $user = User::findByEmail($row['email']);
        if ($user) {
            User::update((int) $user['id'], ['password' => $password]);
        }
        PasswordReset::deleteByToken($token);
        $this->redirect('/login', 'Votre mot de passe a été réinitialisé. Connectez-vous avec votre nouveau mot de passe.', 'success');
    }

    private function view(string $name, array $data = []): void
    {
        extract($data);
        require dirname(__DIR__) . '/Views/' . $name . '.php';
    }

    private function redirect(string $url, string $message = '', string $type = 'info'): void
    {
        if ($message !== '') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        }
        header('Location: ' . $url);
        exit;
    }
}
