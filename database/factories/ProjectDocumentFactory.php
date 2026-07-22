<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectDocument>
 */
class ProjectDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'phase_id' => null,
            'task_id' => null,
            'uploaded_by' => StaffProfile::factory(),
            'original_filename' => fake()->randomElement([
                'site-photo-'.fake()->numerify('###').'.jpg',
                'inspection-report-'.fake()->numerify('###').'.pdf',
                'method-statement-'.fake()->numerify('###').'.pdf',
                'safety-permit-'.fake()->numerify('###').'.pdf',
                'quality-check-'.fake()->numerify('###').'.xlsx',
                'daily-log-'.fake()->numerify('###').'.pdf',
                'drawing-'.fake()->numerify('###').'.dwg',
                'material-cert-'.fake()->numerify('###').'.pdf',
            ]),
            'stored_filename' => fake()->uuid().'.'.fake()->fileExtension(),
            'file_path' => 'documents/'.fake()->uuid().'.'.fake()->fileExtension(),
            'mime_type' => fake()->randomElement(['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
            'file_size' => fake()->numberBetween(100000, 5000000),
            'category' => fake()->randomElement(['photo', 'report', 'certificate', 'drawing', 'inspection', 'safety']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
