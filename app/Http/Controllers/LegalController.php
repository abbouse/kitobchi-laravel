<?php

namespace App\Http\Controllers;

use App\Models\Policy;

class LegalController extends Controller
{
    public function index()
    {
        $policies = Policy::active()->with('translations')->orderBy('sort_order')->orderBy('id')->get();

        return view('legal.index', compact('policies'));
    }

    public function show(string $slug)
    {
        $policy = Policy::active()->with('translations')->where('slug', $slug)->firstOrFail();
        $policies = Policy::active()->with('translations')->orderBy('sort_order')->orderBy('id')->get();

        return view('legal.show', compact('policy', 'policies'));
    }

    // Legacy redirects for old URLs
    public function terms()
    {
        $policy = Policy::active()->where('slug', 'foydalanish-shartlari')->first()
               ?? Policy::active()->where('slug', 'terms')->first();

        if ($policy) {
            return redirect()->route('legal.policy', $policy->slug);
        }

        $policies = Policy::active()->with('translations')->orderBy('sort_order')->get();

        return view('legal.index', compact('policies'));
    }

    public function privacy()
    {
        $policy = Policy::active()->where('slug', 'maxfiylik-siyosati')->first()
               ?? Policy::active()->where('slug', 'privacy')->first();

        if ($policy) {
            return redirect()->route('legal.policy', $policy->slug);
        }

        $policies = Policy::active()->with('translations')->orderBy('sort_order')->get();

        return view('legal.index', compact('policies'));
    }

    public function subscription()
    {
        $policy = Policy::active()->where('slug', 'obuna-shartlari')->first()
               ?? Policy::active()->where('slug', 'subscription')->first();

        if ($policy) {
            return redirect()->route('legal.policy', $policy->slug);
        }

        $policies = Policy::active()->with('translations')->orderBy('sort_order')->get();

        return view('legal.index', compact('policies'));
    }
}
