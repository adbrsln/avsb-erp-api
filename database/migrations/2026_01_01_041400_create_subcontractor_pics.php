<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create subcontractor_pics table (mirrors client_pics)
        Schema::create('subcontractor_pics', function (Blueprint $table) {
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
        $db = Schema::getConnection();
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
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 3. Drop old columns
        Schema::table('subcontractors', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'contact_phone']);
        });
    }

    public function down(): void
    {
        Schema::table('subcontractors', function (Blueprint $table) {
            $table->string('contact_person', 100)->nullable()->after('email');
            $table->string('contact_phone', 50)->nullable()->after('contact_person');
        });
        Schema::dropIfExists('subcontractor_pics');
    }
};
