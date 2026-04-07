<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormTemplate;
use App\Models\RequestType;
use App\Models\User;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Get the STRG New Application request type
        $requestType = RequestType::where('slug', 'strg-new')->first();
        
        // Get staff2 user
        $staff2User = User::where('email', 'staff2@unikl.edu.my')->first();
        
        if ($requestType && $staff2User) {
            // Create the template that was previously uploaded
            $template = FormTemplate::create([
                'title' => 'testing 2',
                'template_type' => 'general_form',
                'file_path' => 'blank-forms/imfTpCXuJWfsGCbPfDSNu14icefUpL4EaKrZc9yv.pdf',
                'uploaded_by' => $staff2User->id,
                'is_active' => true,
            ]);
            
            // Assign it to request type
            $requestType->default_template_id = $template->id;
            $requestType->save();
            
            $this->command->info('✅ Template created and assigned to STRG New Application');
        } else {
            if (!$requestType) {
                $this->command->warn('⚠️ STRG New Application request type not found');
            }
            if (!$staff2User) {
                $this->command->warn('⚠️ Staff2 user not found');
            }
        }
    }
}
