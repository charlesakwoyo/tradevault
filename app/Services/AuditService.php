<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditService
{
    /** Keys whose values never reach the audit trail. */
    private const REDACTED = [
        'password', 'password_confirmation', 'current_password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes', 'id_number', 'destination',
        'token', 'code',
    ];

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $meta
     */
    public function log(
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        array $meta = [],
        ?User $actor = null,
    ): AuditLog {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();
        $actor ??= $request?->user();

        return AuditLog::unguarded(fn () => AuditLog::query()->create([
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'old_values' => $this->redact($old) ?: null,
            'new_values' => $this->redact($new) ?: null,
            'meta' => $this->redact($meta) ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 1000) : null,
            'created_at' => now(),
        ]));
    }

    /**
     * Log the before/after of a model's dirty attributes. Call before save().
     *
     * @param  array<string, mixed>  $meta
     */
    public function logChanges(string $action, Model $subject, array $meta = [], ?User $actor = null): AuditLog
    {
        $dirty = $subject->getDirty();

        return $this->log(
            $action,
            $subject,
            Arr::only($subject->getOriginal(), array_keys($dirty)),
            $dirty,
            $meta,
            $actor,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::REDACTED, true)) {
                $values[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            } elseif ($value instanceof \BackedEnum) {
                $values[$key] = $value->value;
            }
        }

        return $values;
    }
}
