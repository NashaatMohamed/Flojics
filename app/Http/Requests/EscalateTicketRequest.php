<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscalateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', Rule::enum(NotificationChannel::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'channels.required' => 'At least one notification channel is required.',
            'channels.min' => 'You must select at least one notification channel.',
            'channels.*.required' => 'Each channel must be specified.',
            'channels.*.enum' => 'Invalid notification channel selected.',
        ];
    }

    public function attributes(): array
    {
        return [
            'channels' => 'Notification channels',
            'channels.*' => 'channel',
        ];
    }
}
