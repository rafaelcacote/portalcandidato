<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Cpf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterCpfAvailabilityController extends Controller
{
    /**
     * Check whether a CPF is available for candidate registration (JSON for the registration form).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $digits = Cpf::normalizeToDigits($request->query('cpf', ''));

        if ($digits === '' || strlen($digits) < 11) {
            return response()->json([
                'status' => 'incomplete',
            ]);
        }

        if (strlen($digits) > 11) {
            return response()->json([
                'status' => 'invalid',
            ]);
        }

        if (! Cpf::digitsAreValid($digits)) {
            return response()->json([
                'status' => 'invalid',
            ]);
        }

        $taken = User::query()->where('cpf', $digits)->exists();

        return response()->json([
            'status' => $taken ? 'taken' : 'available',
        ]);
    }
}
