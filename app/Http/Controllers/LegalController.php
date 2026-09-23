<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function show(string $document): View
    {
        $documents = config('tradevault.legal.documents');
        abort_unless(array_key_exists($document, $documents), 404);

        $path = resource_path("legal/{$document}.md");
        abort_unless(File::exists($path), 404);

        return view('legal.show', [
            'title' => $documents[$document],
            'html' => Str::markdown(File::get($path), ['html_input' => 'escape', 'allow_unsafe_links' => false]),
            'version' => config('tradevault.legal.terms_version'),
        ]);
    }
}
