<?php

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function makeDocumentUser(string $role = 'admin'): array
{
    $email = fake()->unique()->safeEmail();
    $user = User::factory()->create(['email' => $email]);
    $user->syncRoles([$role]);
    StaffProfile::factory()->create(['email' => $email]);

    return [
        'user' => $user,
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
    ];
}

function pdfUpload(string $name): array
{
    $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\nxref\n0 4\n0000000000 65535 f \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF\n";
    $tempPdf = tempnam(sys_get_temp_dir(), 'pdoc').'.pdf';
    file_put_contents($tempPdf, $pdf);

    return [$tempPdf, new UploadedFile($tempPdf, $name, 'application/pdf', null, true)];
}

beforeEach(function () {
    $this->ctx = makeDocumentUser('admin');
});

describe('Project Documents CRUD', function () {

    it('lists documents for a project member', function () {
        $project = Project::factory()->create();
        ProjectDocument::create([
            'project_id' => $project->id,
            'original_filename' => 'plan.pdf',
            'stored_filename' => 'x.pdf',
            'file_path' => 'uploads/projects/'.$project->id.'/x.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'category' => 'drawing',
        ]);

        getJson('/api/v1/projects/'.$project->id.'/documents', $this->ctx['headers'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.original_name', 'plan.pdf');
    });

    it('forbids non-members from listing documents', function () {
        $project = Project::factory()->create();
        $staff = makeDocumentUser('staff');

        getJson('/api/v1/projects/'.$project->id.'/documents', $staff['headers'])
            ->assertStatus(403);
    });

    it('stores an uploaded document', function () {
        $project = Project::factory()->create();
        [$tempPdf, $file] = pdfUpload('spec.pdf');

        $response = $this->call('POST', '/api/v1/projects/'.$project->id.'/documents', [
            'category' => 'specification',
            'notes' => 'Client spec',
        ], [], ['file' => $file], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->ctx['user']->createToken('t')->plainTextToken]);

        $response->assertStatus(201)
            ->assertJsonPath('original_filename', 'spec.pdf')
            ->assertJsonPath('category', 'specification');

        @unlink($tempPdf);
    });

    it('rejects a missing file', function () {
        $project = Project::factory()->create();

        postJson('/api/v1/projects/'.$project->id.'/documents', ['category' => 'drawing'], $this->ctx['headers'])
            ->assertStatus(422);
    });

    it('shows a stored document', function () {
        $project = Project::factory()->create();
        $doc = ProjectDocument::create([
            'project_id' => $project->id,
            'original_filename' => 'view.pdf',
            'stored_filename' => 'y.pdf',
            'file_path' => 'uploads/projects/'.$project->id.'/y.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'category' => 'drawing',
        ]);
        $storage = new FileStorageService;
        $storage->put('uploads/projects/'.$project->id.'/y.pdf', '%PDF-1.4 test', 'application/pdf');

        getJson('/api/v1/documents/'.$doc->id, $this->ctx['headers'])
            ->assertStatus(200);
    });

    it('deletes a document as pm+', function () {
        $project = Project::factory()->create();
        $doc = ProjectDocument::create([
            'project_id' => $project->id,
            'original_filename' => 'del.pdf',
            'stored_filename' => 'z.pdf',
            'file_path' => 'uploads/projects/'.$project->id.'/z.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
        ]);

        deleteJson('/api/v1/documents/'.$doc->id, [], $this->ctx['headers'])
            ->assertStatus(204);

        expect(ProjectDocument::find($doc->id))->toBeNull();
    });

    it('forbids staff from deleting documents', function () {
        $project = Project::factory()->create();
        $doc = ProjectDocument::create([
            'project_id' => $project->id,
            'original_filename' => 'no.pdf',
            'stored_filename' => 'n.pdf',
            'file_path' => 'uploads/projects/'.$project->id.'/n.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
        ]);
        $staff = makeDocumentUser('staff');

        deleteJson('/api/v1/documents/'.$doc->id, [], $staff['headers'])
            ->assertStatus(403);
    });

});
