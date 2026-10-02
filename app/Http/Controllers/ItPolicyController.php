<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ItPolicyController extends Controller
{
    /**
     * The company IT Policy, readable by every signed-in role.
     * Content lives in config/it_policy.php.
     */
    public function show(): View
    {
        return view('it-policy.index', [
            'policy' => config('it_policy'),
        ]);
    }

    /**
     * Download the official PDF (includes the acknowledgement form).
     * Served through the app so only signed-in users can fetch it.
     */
    public function download(): BinaryFileResponse
    {
        $path = resource_path('documents/'.config('it_policy.pdf'));

        abort_unless(is_file($path), 404, 'The IT Policy PDF is not available.');

        return response()->download($path, 'Crest-Forwarder-IT-Policy.pdf');
    }
}
