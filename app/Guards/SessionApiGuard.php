<?php

namespace App\Guards;

use App\Models\User;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

class SessionApiGuard implements Guard
{
    use GuardHelpers;

    protected $session;
    protected $request;

    public function __construct(Session $session, Request $request)
    {
        $this->session = $session;
        $this->request = $request;
    }

    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $userUin = $this->session->get('authenticated_user_uin');
        
        if ($userUin && $this->session->get('api_authenticated')) {
            $this->user = User::where('User_UIN', $userUin)->first();
        }

        return $this->user;
    }

    public function validate(array $credentials = [])
    {
        if (!isset($credentials['User_UIN']) || !isset($credentials['api_key'])) {
            return false;
        }

        $user = User::where('User_UIN', $credentials['User_UIN'])->first();
        
        if (!$user) {
            return false;
        }

        // Add your API key validation logic here
        return true;
    }

    public function attempt(array $credentials = [], $remember = false)
    {
        if ($this->validate($credentials)) {
            $user = User::where('User_UIN', $credentials['User_UIN'])->first();
            $this->login($user);
            return true;
        }

        return false;
    }

    public function login($user)
    {
        $this->session->put([
            'api_authenticated' => true,
            'authenticated_user_uin' => $user->User_UIN,
            'api_auth_time' => now()->toISOString(),
            'auth_method' => 'session_api'
        ]);

        $this->user = $user;
    }

    public function logout()
    {
        $this->session->forget([
            'api_authenticated',
            'authenticated_user_uin',
            'api_auth_time',
            'auth_method',
            'selected_Orga_UIN'
        ]);

        $this->user = null;
    }

    public function check()
    {
        return !is_null($this->user());
    }

    public function guest()
    {
        return !$this->check();
    }

    public function id()
    {
        if ($user = $this->user()) {
            return $user->User_UIN;
        }
    }
}