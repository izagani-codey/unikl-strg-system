<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'staff_id'       => ['required', 'string', 'max:50', 'unique:users,staff_id'],
            'designation'    => ['required', 'string', 'max:255'],
            'department'     => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'employee_level' => ['nullable', 'string', 'max:100'],
            'email'          => [
                'required', 'string', 'email', 'max:255', 'unique:' . User::class,
                function ($attribute, $value, $fail) {
                    if (app()->environment('testing')) return;
                    $allowed = ['@unikl.edu.my', '@s.unikl.edu.my'];
                    $isAllowed = collect($allowed)->contains(fn($d) => str_ends_with(strtolower($value), $d));
                    if (!$isAllowed) {
                        $fail('Only UniKL email addresses are allowed (@unikl.edu.my or @s.unikl.edu.my).');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'           => $request->name,
            'staff_id'       => $request->staff_id,
            'designation'    => $request->designation,
            'department'     => $request->department,
            'phone'          => $request->phone,
            'employee_level' => $request->employee_level,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'role'           => 'admission',
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
