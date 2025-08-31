<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    private const STATUS_PENDING = 'pending';

    private const STATUS_IN_PROGRESS = 'in_progress';

    private const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_DONE,
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Oczekujące',
            self::STATUS_IN_PROGRESS => 'W toku',
            self::STATUS_DONE => 'Zrobione',
        ];
    }

    public const MSG_CREATED = 'Zadanie utworzone.';

    public const MSG_UPDATED = 'Zadanie zaktualizowane.';

    public const MSG_DELETED = 'Zadanie usunięte.';

    public const MSG_RESTORED = 'Zadanie przywrócone.';

    public static function statusSelectOptions(): array
    {
        return collect(self::statusLabels())
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'due_date',
        'uuid',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolveRouteBinding($value, $field = null): Model
    {
        return $this->where($field ?? 'uuid', $value)
            ->withTrashed()
            ->firstOrFail();
    }

    /**
     * Lokalny scope pozwalający filtrować zadania po roli właściciela:
     * - 'owner' => użytkownicy z owner = true
     * - 'user'  => użytkownicy z owner = false
     * Inne wartości są ignorowane (brak filtra).
     */
    public function scopeWhereRole($query, ?string $role)
    {
        $role = strtolower(trim((string) $role));
        if ($role === 'owner') {
            $query->whereHas('user', fn ($q) => $q->where('owner', true));
        } elseif ($role === 'user') {
            $query->whereHas('user', fn ($q) => $q->where('owner', false));
        }

        return $query;
    }

    public function scopeFilter($query, array $filters)
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $matchingStatusCodes = collect(self::statusLabels())
                ->filter(function (string $label) use ($search) {
                    return mb_stripos($label, $search) !== false;
                })
                ->keys()
                ->all();

            $searchDate = null;
            try {
                $searchDate = Carbon::parse($search)->toDateString();
            } catch (\Throwable $e) {
                $searchDate = null;
            }

            $query->where(function ($q) use ($search, $matchingStatusCodes, $searchDate) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');

                if (! empty($matchingStatusCodes)) {
                    $q->orWhereIn('status', $matchingStatusCodes);
                }

                if ($searchDate) {
                    $q->orWhereDate('due_date', $searchDate);
                }

                $q->orWhere('due_date', 'like', '%'.$search.'%');
            });
        }

        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['role'] ?? null, function ($query, $role) {
            $query->whereRole($role);
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });

        return $query;
    }
}
