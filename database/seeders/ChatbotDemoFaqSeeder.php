<?php

namespace Database\Seeders;

use App\Chatbot\Conflict;
use App\Chatbot\FaqService;
use Illuminate\Database\Seeder;

class ChatbotDemoFaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['What is this chatbot?' => 'This demo answers prepared FAQs. It does not use AI.', 'Can I include personal information?' => 'Please do not include personal or sensitive information.'] as $q => $a) {
            try {
                app(FaqService::class)->create($q, $a);
            } catch (Conflict) { /* Existing owner content is preserved. */
            }
        }
    }
}
