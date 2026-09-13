<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccessCodeController extends Controller
{
    public function index(): View
    {
        $codes = AccessCode::latest()->get();
        return view('admin.codes.index', compact('codes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:access_codes,code'],
            'class_name' => ['required', 'string', 'max:10'],
        ]);

        AccessCode::create($validated);

        return back()->with('success', 'Код доступа успешно создан.');
    }

    public function toggle(AccessCode $accessCode): RedirectResponse
    {
        // Переключаем статус активности кода
        $accessCode->update(['is_active' => !$accessCode->is_active]);

        $status = $accessCode->is_active ? 'активирован' : 'деактивирован';
        return back()->with('success', "Код для класса {$accessCode->class_name} {$status}.");
    }

    public function destroy(AccessCode $accessCode): RedirectResponse
    {
        $accessCode->delete();

        return back()->with('success', 'Код безвозвратно удален.');
    }
}
