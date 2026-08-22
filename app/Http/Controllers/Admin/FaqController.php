<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\Faq;
use Inertia\Inertia;

class FaqController extends Controller
{
    /**
     * Listado de todas las FAQs (incluyendo no publicadas).
     */
    public function index()
    {
        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => Faq::orderBy('order')->get(),
        ]);
    }

    /**
     * Crear nueva FAQ.
     */
    public function store(StoreFaqRequest $request)
    {
        Faq::create($request->validated());

        return back()->with('success', 'Pregunta frecuente agregada correctamente.');
    }

    /**
     * Actualizar FAQ existente.
     */
    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $faq->update($request->validated());

        return back()->with('success', 'Pregunta frecuente actualizada correctamente.');
    }

    /**
     * Eliminar FAQ.
     */
    public function destroy(Faq $faq)
    {
        $faq->delete();

        return back()->with('success', 'Pregunta frecuente eliminada correctamente.');
    }
}
