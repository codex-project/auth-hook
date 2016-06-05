<?php
namespace Codex\Addon\Auth;

use Codex\Addon\Auth\Socialite\User;
use Codex\Contracts\Codex;
use Codex\Projects\Project;
use Laravel\Socialite\Contracts\Factory;

class Auth
{
    protected $users = [ ];

    protected $social;

    protected $codex;

    /**
     * Auth constructor.
     *
     * @param \Laravel\Socialite\Contracts\Factory|\Laravel\Socialite\SocialiteManager $social
     */
    public function __construct(Codex $parent, Factory $social)
    {
        $this->codex  = $parent;
        $this->social = $social;
    }

    public function redirect($driverName)
    {
        $driver = $this->social->driver($driverName);
        if ( 'bitbucket' === $driverName )
        {
            $driver->scopes([ 'account' ]);
        }
        elseif ( 'github' === $driverName )
        {
            $driver->scopes([ 'user', 'user:email', 'read:org' ]);
        }
        return $driver->redirect();
    }

    public function callback($driverName)
    {
        $user = $this->user($driverName);
        session()->set("codex.auth.logins.{$driverName}", $user);
    }

    /**
     * user method
     *
     * @param $driverName
     *
     * @return \Codex\Addon\Auth\Socialite\User
     */
    protected function user($driverName)
    {
        $driver = $this->social->driver($driverName);
        return $driver->user();
    }

    public function logout($driverName)
    {
        session()->forget("codex.auth.logins.{$driverName}");
    }

    /**
     * getUser method
     *
     * @param $driverName
     *
     * @return User
     */
    public function getUser($driverName)
    {
        return session()->get("codex.auth.logins.{$driverName}");
    }

    public function isLoggedIn($driverName)
    {
        return session()->has("codex.auth.logins.{$driverName}");
    }

    public function hasAccess(Project $project)
    {
        if ( $project->config('auth.enabled', false) !== true )
        {
            return true;
        }
        $driverName = $project->config('auth.driver');
        if ( !$this->isLoggedIn($driverName) )
        {
            return false;
        }

        $user = $this->getUser($driverName);
        $user->getGroups();

        if ( in_array($user->getEmail(), $project->config('auth.allow.emails', [ ]), true) )
        {
            return true;
        }
        if ( in_array($user->getNickname(), $project->config('auth.allow.usernames', [ ]), true) )
        {
            return true;
        }

        $diff = count(array_diff($user->getGroups(), $allowedGroups = $project->config('auth.allow.groups', [ ])));
        if ( $diff !== $allowedGroups )
        {
            return true;
        }
        return false;
    }
}