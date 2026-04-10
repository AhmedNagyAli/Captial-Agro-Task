<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OptionGroup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;


class HomeController extends Controller
{
    public function index()
    {
        $groups = Cache::remember('builder.catalog', 3600, function () {
            return OptionGroup::with('options')->ordered()->get();
        });

        return Inertia::render('Dashboard', [
            'groups' => $groups
        ]);
    }
}
