<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function index() {
        return view('legal.index');
    }

    public function terms() {
        return view('legal.terms');
    }

    public function privacy() {
        return view('legal.privacy');
    }

    public function subscription() {
        return view('legal.subscription');
    }
}