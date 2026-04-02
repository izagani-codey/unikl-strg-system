<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-700 to-indigo-700 bg-clip-text text-transparent">Secure Deployment & PDF Autofill Playbook</h1>
            <p class="text-gray-600 mt-1">Operational checklist for sensitive data, template setup, and safe publishing.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-4">
                <p class="font-semibold">Important:</p>
                <p class="text-sm mt-1">Current <code>TemplateService::generateAutoFilledPdf()</code> behavior is a safe placeholder: it copies the original template and logs usage. It does not yet write values into PDF fields automatically.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section class="bg-white rounded-xl shadow p-6 space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">1) How to test autofill safely</h2>
                    <ol class="list-decimal list-inside text-sm text-slate-700 space-y-2">
                        <li>Create a dedicated <strong>staging</strong> environment and a separate database/storage bucket.</li>
                        <li>Use synthetic test identities only (no real names, IDs, or phone numbers).</li>
                        <li>Upload blank template samples without confidential details.</li>
                        <li>Generate output PDFs and verify layout/field mapping manually.</li>
                        <li>Purge generated files after each test cycle.</li>
                    </ol>
                </section>

                <section class="bg-white rounded-xl shadow p-6 space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">2) Can you build 1:1 templates?</h2>
                    <p class="text-sm text-slate-700">Yes. Build templates visually close to your final form and reserve clear field zones for future dynamic values (name, staff ID, dates, totals, signatures). Keep each target region consistent in location/size to simplify true autofill implementation later.</p>
                    <ul class="list-disc list-inside text-sm text-slate-700 space-y-1">
                        <li>Keep static labels in the base PDF.</li>
                        <li>Use whitespace boxes for dynamic values.</li>
                        <li>Document field map keys (e.g., <code>user.name</code>, <code>request.total_amount</code>).</li>
                    </ul>
                </section>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section class="bg-white rounded-xl shadow p-6 space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">3) Keep GitHub private (recommended baseline)</h2>
                    <ul class="list-disc list-inside text-sm text-slate-700 space-y-2">
                        <li>Use a private repo and restrict collaborators by role.</li>
                        <li>Never commit <code>.env</code>, production DB dumps, generated PDFs, or uploaded attachments.</li>
                        <li>Store app secrets in GitHub Encrypted Secrets / deployment secret manager only.</li>
                        <li>Use branch protection + required reviews for workflow/security changes.</li>
                        <li>Enable secret scanning and dependency alerts.</li>
                    </ul>
                </section>

                <section class="bg-white rounded-xl shadow p-6 space-y-3">
                    <h2 class="text-xl font-bold text-slate-900">4) "Deployable skeleton" mode</h2>
                    <p class="text-sm text-slate-700">For collaboration without leakage, keep this profile in your public/private handoff branch:</p>
                    <ul class="list-disc list-inside text-sm text-slate-700 space-y-2">
                        <li>Use seeded demo users only.</li>
                        <li>Disable real mail delivery (log/mailtrap mode).</li>
                        <li>Remove real templates; include only sanitized sample forms.</li>
                        <li>Seed fake request data only.</li>
                        <li>Provide a one-command setup script for reviewers.</li>
                    </ul>
                    <p class="text-xs text-slate-500">Tip: use <code>./scripts/devbox_skeleton.sh</code> to initialize a local skeleton/devbox quickly.</p>
                </section>
            </div>

            <section class="bg-slate-900 text-slate-100 rounded-xl p-6">
                <h2 class="text-xl font-bold">Recommended next implementation (production-ready autofill)</h2>
                <ol class="list-decimal list-inside text-sm mt-3 space-y-2 text-slate-200">
                    <li>Store template field coordinates / PDF form field names in DB.</li>
                    <li>Add a real PDF field writer (AcroForm fill / coordinate overlay).</li>
                    <li>Persist generated files in private storage and serve via authorized download route only.</li>
                    <li>Add regression tests for mapping correctness and access control.</li>
                </ol>
            </section>
        </div>
    </div>
</x-app-layout>
