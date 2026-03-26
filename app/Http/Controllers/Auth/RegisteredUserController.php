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
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
       // app/Http/Controllers/Auth/RegisteredUserController.php

$request->validate([
    'name'     => ['required', 'string', 'max:255'],
    'email'    => [
        'required',
        'string',
        'email',
        'max:255',
        'unique:users',
        function ($attribute, $value, $fail) {
            $allowed = ['@unikl.edu.my', '@s.unikl.edu.my'];
            $isAllowed = false;

            foreach ($allowed as $domain) {
                if (str_ends_with(strtolower($value), $domain)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                $fail('Only UniKL email addresses are allowed (@unikl.edu.my or @s.unikl.edu.my).');
            }
        },
    ],
    'password' => ['required', 'confirmed', Rules\Password::defaults()],
]);
$user = User::create([
    'name'     => $request->name,
    'email'    => $request->email,
    'password' => Hash::make($request->password),
    'role'     => 'admission', // all self-registered users are admission
]);
$user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'admissions', // This sets every new person as 'admissions' by default
    ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
