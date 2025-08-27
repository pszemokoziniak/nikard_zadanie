<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'max:50', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['nullable', 'string'],
            'owner' => ['required', 'boolean'],
            'photo' => ['nullable', 'image'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Pole :attribute jest wymagane.',
            'first_name.string' => 'Pole :attribute musi być tekstem.',
            'first_name.max' => 'Pole :attribute nie może mieć więcej niż :max znaków.',

            'last_name.required' => 'Pole :attribute jest wymagane.',
            'last_name.string' => 'Pole :attribute musi być tekstem.',
            'last_name.max' => 'Pole :attribute nie może mieć więcej niż :max znaków.',

            'email.required' => 'Pole :attribute jest wymagane.',
            'email.string' => 'Pole :attribute musi być tekstem.',
            'email.max' => 'Pole :attribute nie może mieć więcej niż :max znaków.',
            'email.email' => 'Pole :attribute musi być poprawnym adresem e-mail.',
            'email.unique' => 'Użytkownik z takim adresem e-mail już istnieje.',

            'password.string' => 'Pole :attribute musi być tekstem.',

            'owner.required' => 'Pole :attribute jest wymagane.',
            'owner.boolean' => 'Pole :attribute musi mieć wartość true/false.',

            'photo.image' => 'Pole :attribute musi być plikiem graficznym.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'imię',
            'last_name' => 'nazwisko',
            'email' => 'e-mail',
            'password' => 'hasło',
            'owner' => 'właściciel',
            'photo' => 'zdjęcie',
        ];
    }
}
