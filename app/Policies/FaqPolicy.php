<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_chatbot_admin;
    }

    public function view(User $user, Faq $faq): bool
    {
        return $user->is_chatbot_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_chatbot_admin;
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->is_chatbot_admin;
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->is_chatbot_admin;
    }
}
