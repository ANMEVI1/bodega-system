<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('admin.contacts.index', ['contacts' => ContactSubmission::query()->latest()->paginate(15)]);
    }

    public function update(ContactSubmission $contact): RedirectResponse
    {
        $contact->update(['status' => $contact->status === 'new' ? 'read' : 'new']);

        return back()->with('success', 'Estado de la consulta actualizado.');
    }

    public function destroy(ContactSubmission $contact): RedirectResponse
    {
        $contact->delete();

        return back()->with('success', 'Consulta eliminada.');
    }
}
