<?php

namespace App\Controllers;

use App\Services\RadiusDebugService;

class RadiusDebugController extends BaseController
{
    public function index(): void
    {
        require_auth();
        if (!is_super_admin() && !is_technician()) {
            http_response_code(403);
            exit('Forbidden');
        }
        $filters = is_technician() ? ['router_ids' => $this->scope->currentRouterIds() ?: [-1]] : [];
        $debug = (new RadiusDebugService())->inspect($filters);
        $this->view('radius_debug/index', compact('debug'));
    }
}
