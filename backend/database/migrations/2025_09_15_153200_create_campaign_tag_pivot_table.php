<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaign_tag_pivot', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('campaign_tags')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
            
            $table->primary(['campaign_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_tag_pivot');
    }
};
