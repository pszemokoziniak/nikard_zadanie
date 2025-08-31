<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var User|\stdClass $user */
        $user = $this->resource;

        $first = isset($user->first_name) ? $user->first_name : null;
        $last = isset($user->last_name) ? $user->last_name : null;
        $name = isset($user->name) ? $user->name : trim(trim((string) $first).' '.trim((string) $last));
        $photoPath = isset($user->photo_path) ? $user->photo_path : null;

        return [
            'id' => $user->id ?? null,
            'uuid' => $user->uuid ?? null,
            'first_name' => $first,
            'last_name' => $last,
            'name' => $name,
            'email' => $user->email ?? null,
            'owner' => (bool) ($user->owner ?? false),
            'photo' => $photoPath
                ? route('image', ['path' => $photoPath, 'w' => 40, 'h' => 40, 'fit' => 'crop'])
                : null,
            'deleted_at' => $user->deleted_at ?? null,
        ];
    }
}
