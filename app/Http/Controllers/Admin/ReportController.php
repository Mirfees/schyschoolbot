<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use App\Models\Report;

class ReportController extends Controller
{
    public function index(): View
    {
        $reports = Report::orderByDesc('created_at')->paginate(20);

        return view('admin.reports.index', compact('reports'));
    }
}
