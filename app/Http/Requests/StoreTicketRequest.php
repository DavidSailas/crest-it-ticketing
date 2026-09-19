<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    /**
     * The fixed "what do you need help with?" categories shown on the ticket
     * form — the single source of truth for both the dropdown and validation.
     */
    public const CATEGORIES = [
        'Computer / Laptop',
        'Internet / Network',
        'Email / Microsoft 365',
        'Password / Account',
        'Printer',
        'Mobile Device',
        'Software / Application',
        'Security',
        'Phone / Communication',
        'Hardware / Office Equipment',
        'Access Request',
        'Other',
    ];

    /**
     * Any authenticated user can submit a ticket — role/ownership scoping
     * happens elsewhere (index/show), not on creation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Department and branch are no longer picked on the form — they're
     * pulled straight from the submitter's own profile (set by an admin)
     * so the request is always tied to their real department/office and
     * can't be misreported or tampered with client-side.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        $this->merge([
            'department' => $user?->department?->name,
            'location' => $user?->location
                ? trim($user->branch_name.' Office')
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'department' => 'required|exists:departments,name',
            'location' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|in:'.implode(',', self::CATEGORIES),
            'priority' => 'required|in:low,medium,high,critical',
            // Non-VIP staff must explicitly confirm a Critical ticket is a
            // widespread outage (multiple people / a whole department),
            // not just "I personally can't work" — that's High. VIP accounts
            // skip this since their tickets are always auto-set to Critical
            // regardless of what's submitted.
            'confirms_multiple_affected' => [
                function (string $attribute, $value, \Closure $fail) {
                    $isCriticalFromNonVip = ! $this->user()->is_vip && $this->input('priority') === 'critical';

                    if ($isCriticalFromNonVip && ! $value) {
                        $fail('Critical is reserved for outages affecting multiple people or a department — please confirm this, or choose High if it only affects you.');
                    }
                },
            ],
            'on_behalf_of_user_id' => 'nullable|exists:users,id',
            'on_behalf_of_name' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip',
        ];
    }

    public function messages(): array
    {
        return [
            'department.required' => "Your account doesn't have a department set yet. Please ask an administrator to update your profile before submitting a ticket.",
            'department.exists' => "Your profile's department could not be matched — please ask an administrator to check it.",
            'location.required' => "Your account doesn't have a branch/office set yet. Please ask an administrator to update your profile before submitting a ticket.",
        ];
    }
}

