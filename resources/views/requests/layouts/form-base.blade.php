@props([
    'requestTypes',
    'votCodes',
    'user',
    'grantRequest' => null,
    'submitRoute',
    'submitButtonText' => 'Submit Request for Verification',
    'method' => 'POST'
])

@php
$isEdit = $grantRequest !== null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $isEdit ? __('Edit STRG Request') : __('Submit New STRG Request') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 overflow-x-hidden">
                
                <form action="{{ $submitRoute }}" method="POST" enctype="multipart/form-data" id="request-form">
                    @csrf
                    @if($isEdit)
                        @method('PATCH')
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
                            <h3 class="text-sm font-semibold text-red-800">Please fix the following before submitting:</h3>
                            <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- SECTION 1: Applicant & Request Information (Shared) --}}
                    @include('requests.partials.form-sections.base-information', [
                        'requestTypes' => $requestTypes,
                        'user' => $user,
                        'grantRequest' => $grantRequest
                    ])

                    {{-- SECTION 2: Dynamic Request Type Fields (Type-Specific) --}}
                    <div id="dynamic-fields-section" class="mb-6 border-b border-gray-200 pb-6 {{ old('request_type_id', $grantRequest?->request_type_id) ? '' : 'hidden' }}">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Details</h3>
                        <p class="text-sm text-gray-600 mb-4">Please provide the specific information required for this request type.</p>
                        
                        <div id="dynamic-fields-container">
                            {{-- Dynamic fields loaded here via AJAX or server-rendered --}}
                            @if(old('request_type_id') || $grantRequest?->request_type_id)
                                @php
                                    $selectedTypeId = old('request_type_id', $grantRequest?->request_type_id);
                                    $selectedType = $requestTypes->firstWhere('id', $selectedTypeId);
                                @endphp
                                @if($selectedType && $selectedType->field_schema)
                                    <x-dynamic-form-fields 
                                        :fields="$selectedType->field_schema" 
                                        prefix="dynamic_fields" 
                                        :values="old('dynamic_fields', $grantRequest?->payload['dynamic_fields'] ?? [])" 
                                    />
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- SECTION 3: Budget Breakdown (Shared - VOT Items) --}}
                    @include('requests.partials.form-sections.vot-items', [
                        'votCodes' => $votCodes,
                        'grantRequest' => $grantRequest
                    ])

                    {{-- SECTION 4: Digital Signature (Shared) --}}
                    @include('requests.partials.form-sections.applicant-signature', [
                        'grantRequest' => $grantRequest
                    ])

                    {{-- SECTION 5: Supporting Documents (Shared) --}}
                    @include('requests.partials.form-sections.document-upload')

                    {{-- Submit Button --}}
                    <x-loading-button type="primary" class="w-full">
                        {{ $submitButtonText }}
                    </x-loading-button>
                </form>

            </div>
        </div>
    </div>

    @push('scripts')
        @include('requests.partials.form-scripts')
    @endpush
</x-app-layout>
