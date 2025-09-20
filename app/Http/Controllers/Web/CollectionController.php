<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseController;
use App\Models\HomeSection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CollectionController extends BaseController
{
    public function index(?Request $request, string $slug = null): View
    {
        $section = HomeSection::where('slug', $slug)->where('status', 1)->firstOrFail();
        $products = $section->products()->paginate(24);
        $view = theme_root_path() === 'theme_aster' ? 'theme-views.collection' : 'web-views.collection';
        return view($view, compact('section','products'));
    }
}
