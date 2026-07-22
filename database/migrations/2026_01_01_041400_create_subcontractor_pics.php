<?php

return new class {
    public function up(\Illuminate\Database\Schema\Builder $schema): void
    {
        // 1. Create subcontractor_pics table (mirrors client_pics)
        $schema->create('subcontractor_pics', function ($table) {
            $table->id();
            $table->foreignId('subcontractor_id')->constrained('subcontractors')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Migrate existing contact_person + contact_phone into PIC rows
        $db = $schema->getConnection();
        $rows = $db->table('subcontractors')
            ->whereNotNull('contact_person')
            ->where('contact_person', '!=', '')
            ->get(['id', 'contact_person', 'contact_phone']);

        foreach ($rows as $row) {
            $db->table('subcontractor_pics')->insert([
                'subcontractor_id' => $row->id,
                'name' => $row->contact_person,
                'phone' => $row->contact_phone,
                'is_primary' => true,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        }

        // 3. Drop old columns
        $schema->table('subcontractors', function ($table) {
            $table->dropColumn(['contact_person', 'contact_phone']);
        });
    }

    public function down(\Illuminate\Database\Schema\Builder $schema): void
    {
        $schema->table('subcontractors', function ($table) {
            $table->string('contact_person', 100)->nullable()->after('email');
            $table->string('contact_phone', 50)->nullable()->after('contact_person');
        });
        $schema->dropIfExists('subcontractor_pics');
    }
};
