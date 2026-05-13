<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DevTeamController extends Controller
{
    public function index(Request $request)
    {
        $emails = [
            'mark.c.mendoza@example.com',
            'miguel.c.tapalla@example.com',
            'marvin.tomales@example.com',
            'eivrian.pacis@example.com',
            'thomas.naguit@example.com',
            'jhimar.motea@example.com',
            'clark.raguhos@example.com',
            'francis.sulit@example.com',
        ];

        $members = User::whereIn('email', $emails)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'avatar_url' => $u->avatar_url ?? null,
                    'role' => $u->role ?? null,
                    'bio' => $u->bio ?? null,
                    'linkedin' => $u->linkedin ?? null,
                ];
            })
            ->values();

        // If DB doesn't have seeded users yet, return a simple fallback list
        if ($members->isEmpty()) {
            $names = [
                'Mark Cyrus Mendoza',
                'Miguel Carlo Tapalla',
                'Marvin Tomales',
                'Eivrian Nicholson S. Pacis',
                'Thomas Adrian M. Naguit',
                'Jhimar Carl U. Motea',
                'Clark Kent B. Raguhos',
                'Francis Dave C. Sulit',
            ];

            $fallback = collect($names)->map(function ($name, $idx) {
                return [
                    'id' => null,
                    'name' => $name,
                    'email' => null,
                    'avatar_url' => null,
                    'role' => null,
                    'bio' => null,
                    'linkedin' => null,
                ];
            })->values();

            return response()->json(['data' => $fallback], 200);
        }

        return response()->json(['data' => $members], 200);
    }
}
