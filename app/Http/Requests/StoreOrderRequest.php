<?php

namespace App\Http\Requests;

use App\Enums\SaleType;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'date_commande' => ['nullable', 'date'],
            'type_vente' => ['required', new Enum(SaleType::class)],
            'date_echeance' => ['nullable', 'required_if:type_vente,credit', 'date', 'after:today'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantite' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_echeance.required_if' => "La date d'échéance est obligatoire pour une commande à crédit.",
            'date_echeance.after' => "La date d'échéance doit être postérieure à aujourd'hui.",
        ];
    }
}
