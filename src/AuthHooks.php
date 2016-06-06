<?php
namespace Codex\Addon\Auth;

use Codex\Addons\Annotations\Hook;
use Codex\Contracts\Codex;
use Codex\Documents\Document;
use Codex\Http\CodexController;
use Codex\Projects\Project;

class AuthHooks
{
    /**
     * controllerDocument method
     * @Hook("controller:document")
     *
     * @param \Codex\Http\CodexController         $controller
     * @param \Codex\Documents\Document           $document
     * @param \Codex\Codex|\Codex\Contracts\Codex $codex
     * @param \Codex\Projects\Project             $project
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response|void
     */
    public function controllerDocument(CodexController $controller, Document $document, Codex $codex, Project $project)
    {
        if($project->config('auth.enabled', false) !== true){
            return;
        }
        if($codex->auth->hasAccess($project) === false){
            return response()->redirectToRoute('codex.auth.protected');
        }
    }

    protected function hasEnabledAuth(Project $project)
    {
        return $project->config('auth.enabled', false) === true;
    }
}