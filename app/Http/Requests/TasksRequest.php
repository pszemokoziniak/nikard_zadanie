<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(Task::STATUSES)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Pole :attribute jest wymagane.',
            'title.string' => 'Pole :attribute musi być tekstem.',
            'title.max' => 'Pole :attribute nie może być dłuższe niż :max znaków.',

            'description.string' => 'Pole :attribute musi być tekstem.',

            'status.required' => 'Wybierz wartość dla pola :attribute.',
            'status.string' => 'Pole :attribute musi być tekstem.',
            'status.in' => 'Pole :attribute musi mieć jedną z dozwolonych wartości: oczekujące, w toku lub zrobione.',

            'due_date.date' => 'Pole :attribute musi być poprawną datą.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tytuł',
            'description' => 'opis',
            'status' => 'status',
            'due_date' => 'data zakończenia',
        ];
    }
}
