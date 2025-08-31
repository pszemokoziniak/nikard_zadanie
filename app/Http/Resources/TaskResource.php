<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Task $task */
        $task = $this->resource;

        return [
            'id' => $task->id,
            'uuid' => $task->uuid,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'due_date' => optional($task->due_date)->toDateString(),
            'deleted_at' => $task->deleted_at,
            'can' => [
                'update' => auth()->user()?->can('update', $task) ?? false,
                'delete' => auth()->user()?->can('delete', $task) ?? false,
            ],
        ];
    }
}
