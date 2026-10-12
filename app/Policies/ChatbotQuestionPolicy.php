<?php

namespace App\Policies;

use App\Models\ChatbotQuestion;
use App\Models\User;

class ChatbotQuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_chatbot_admin;
    }

    public function view(User $user, ChatbotQuestion $question): bool
    {
        return $user->is_chatbot_admin;
    }

    public function delete(User $user, ChatbotQuestion $question): bool
    {
        return $user->is_chatbot_admin;
    }
}
