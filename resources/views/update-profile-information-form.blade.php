<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Profile Information') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your staff details. These auto-fill into every request form you submit.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Personal --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" :value="__('Full Name *')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autocomplete="name" />
                <x-input-error class="mt-1" :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="staff_id" :value="__('Staff ID *')" />
                <x-text-input id="staff_id" name="staff_id" type="text" class="mt-1 block w-full" :value="old('staff_id', $user->staff_id)" required placeholder="e.g. UNIKL123456" />
                <x-input-error class="mt-1" :messages="$errors->get('staff_id')" />
            </div>
            <div>
                <x-input-label for="email" :value="__('Email *')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-1" :messages="$errors->get('email')" />
                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div>
                        <p class="text-sm mt-2 text-gray-800">
                            {{ __('Your email address is unverified.') }}
                            <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>
                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-green-600">{{ __('A new verification link has been sent to your email address.') }}</p>
                        @endif
                    </div>
                @endif
            </div>
            <div>
                <x-input-label for="phone" :value="__('Phone Number *')" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" required placeholder="e.g. 0123456789" />
                <x-input-error class="mt-1" :messages="$errors->get('phone')" />
            </div>
        </div>

        {{-- Staff info --}}
        <div class="border-t border-gray-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="designation" :value="__('Designation *')" />
                <select id="designation" name="designation" required
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Select —</option>
                    @foreach(['Professor','Associate Professor','Senior Lecturer','Lecturer','Assistant Lecturer','Research Fellow','Postdoctoral Researcher','Director','Deputy Director','Manager','Assistant Manager','Executive','Assistant Executive','Officer','Senior Technologist','Technologist','Technical Assistant','Other'] as $d)
                        <option value="{{ $d }}" @selected(old('designation', $user->designation) === $d)>{{ $d }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('designation')" />
            </div>
            <div>
                <x-input-label for="department" :value="__('Department / Faculty / Centre *')" />
                <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $user->department)" required placeholder="e.g. Faculty of Engineering" />
                <x-input-error class="mt-1" :messages="$errors->get('department')" />
            </div>
            <div>
                <x-input-label for="employee_level" :value="__('Employee Level / Grade (optional)')" />
                <x-text-input id="employee_level" name="employee_level" type="text" class="mt-1 block w-full" :value="old('employee_level', $user->employee_level)" placeholder="e.g. DH52, DS52, VU7" />
                <x-input-error class="mt-1" :messages="$errors->get('employee_level')" />
                <p class="text-xs text-gray-400 mt-1">Civil service grade or pay grade if applicable.</p>
            </div>
        </div>

        {{-- Digital Signature --}}
        <div class="border-t border-gray-100 pt-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Digital Signature') }}</h3>
            <p class="text-sm text-gray-600 mb-4">
                {{ __("Add your digital signature for official documents. This will be automatically included in all request submissions.") }}
            </p>
            
            <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-4">
                <canvas id="profile-signature-canvas" width="400" height="200" 
                        class="border border-gray-400 bg-white rounded cursor-crosshair w-full max-w-md"></canvas>
                
                <div class="mt-4 flex flex-col sm:flex-row justify-between items-start gap-4">
                    <div class="flex gap-2">
                        <button type="button" onclick="clearProfileSignature()" 
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
                            {{ __('Clear Signature') }}
                        </button>
                        
                        <button type="button" onclick="saveProfileSignature()" 
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                            {{ __('Save Signature') }}
                        </button>
                    </div>
                    
                    <div class="text-xs text-gray-500">
                        {{ __('Sign above using mouse or touch device') }}
                    </div>
                </div>
                
                @if($user->signature_data)
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded">
                        <p class="text-sm text-green-800 font-medium">{{ __('Current signature on file') }}</p>
                        <img src="{{ $user->signature_data }}" alt="Current Signature" 
                             class="mt-2 max-h-16 border border-gray-300 rounded" />
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Profile') }}</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

<!-- Signature Pad JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    let profileSignaturePad;

    // Initialize profile signature pad
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('profile-signature-canvas');
        if (canvas) {
            profileSignaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                penWidth: 1.5
            });
        }
    });

    function clearProfileSignature() {
        if (profileSignaturePad) {
            profileSignaturePad.clear();
        }
    }

    function saveProfileSignature() {
        if (!profileSignaturePad) {
            alert('Signature pad not initialized');
            return;
        }

        if (profileSignaturePad.isEmpty()) {
            alert('Please provide your signature before saving.');
            return;
        }

        // Save signature via AJAX
        const signatureData = profileSignaturePad.toDataURL();
        
        fetch('{{ route("profile.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                signature_data: signatureData,
                _method: 'PATCH'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'text-sm text-green-600 font-medium mt-2';
                successDiv.textContent = 'Signature saved successfully!';
                
                const form = document.querySelector('form[action*="profile.update"]');
                form.appendChild(successDiv);
                
                // Reload page after 2 seconds to show updated signature
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                alert('Error saving signature: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving signature. Please try again.');
        });
    }
</script>
