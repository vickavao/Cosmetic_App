<?php

namespace App\Http\Requests;

use App\Enums\SaleType;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
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
     * Anti-surcommande : blocage dur si la quantité cumulée d'un produit
     * dépasse son stock disponible réel (stock physique moins réservations).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items');

            if (! is_array($items) || $items === []) {
                return;
            }

            $grouped = [];
            foreach ($items as $line) {
                if (! isset($line['product_id'], $line['quantite'])) {
                    continue;
                }

                $productId = (int) $line['product_id'];
                $grouped[$productId] = ($grouped[$productId] ?? 0) + (int) $line['quantite'];
            }

            if ($grouped === []) {
                return;
            }

            $products = Product::query()->whereIn('id', array_keys($grouped))->get()->keyBy('id');

            foreach ($items as $index => $line) {
                $productId = (int) ($line['product_id'] ?? 0);

                if (! isset($grouped[$productId])) {
                    continue;
                }

                $disponible = (int) ($products->get($productId)?->disponible ?? 0);

                if ($grouped[$productId] > $disponible) {
                    $validator->errors()->add(
                        "items.{$index}.quantite",
                        "Action impossible : Stock insuffisant (Disponible : {$disponible})",
                    );
                }
            }
        });
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
