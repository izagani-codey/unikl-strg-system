<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Summary - {{ $grantRequest->ref_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 print:bg-white">
    <div class="max-w-4xl mx-auto p-6 print:p-0">
        <div class="mb-4 print:hidden">
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded">Print Summary</button>
        </div>

        <div class="bg-white border rounded-lg p-8">
            <div class="flex justify-between items-start border-b pb-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold">UniKL STRG Request Summary</h1>
                    <p class="text-sm text-slate-500">Reference: {{ $grantRequest->ref_number }}</p>
                </div>
                <div class="text-right text-sm">
                    <p><strong>Status:</strong> {{ $grantRequest->statusLabel() }}</p>
                    <p><strong>Date:</strong> {{ $grantRequest->created_at->format('d M Y') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-slate-500">Applicant</span><p class="font-semibold">{{ $grantRequest->user->name }}</p></div>
                <div><span class="text-slate-500">Email</span><p class="font-semibold">{{ $grantRequest->payload['email'] ?? $grantRequest->user->email }}</p></div>
                <div><span class="text-slate-500">Request Type</span><p class="font-semibold">{{ $grantRequest->requestType->name }}</p></div>
                <div><span class="text-slate-500">Amount</span><p class="font-semibold">RM {{ number_format($grantRequest->payload['amount'] ?? 0, 2) }}</p></div>
                <div><span class="text-slate-500">Verified By</span><p class="font-semibold">{{ $grantRequest->verifiedBy?->name ?? 'Pending' }}</p></div>
                <div><span class="text-slate-500">Recommended By</span><p class="font-semibold">{{ $grantRequest->recommendedBy?->name ?? 'Pending' }}</p></div>
            </div>

            <div class="mt-6">
                <p class="text-slate-500 text-sm">Description</p>
                <p class="mt-1 text-sm border rounded p-3 bg-slate-50">{{ $grantRequest->payload['description'] ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
