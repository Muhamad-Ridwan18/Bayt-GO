<?php

namespace App\Http\Controllers\Muthowif;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class KelolaLayananController extends Controller
{
    public function __invoke(): View
    {
        return view('muthowif.kelola-layanan');
    }
}
