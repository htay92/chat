<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chatbot_questions')) {
            return;
        }
        Schema::create('chatbot_questions', function (Blueprint $table) {
            $table->id();
            $table->char('session_hash', 64)->collation('ascii_bin');
            $table->uuid('request_id')->collation('ascii_bin');
            $table->text('question');
            $table->string('status', 10);
            $table->foreignId('faq_id')->nullable()->constrained('faqs')->nullOnDelete();
            $table->timestamps();
            $table->unique(['session_hash', 'request_id'], 'chatbot_submission_unique');
            $table->index(['status', 'created_at', 'id']);
            $table->index(['created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_questions');
    }
};
