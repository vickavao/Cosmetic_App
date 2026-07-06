<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return true;
        }

        return in_array($user->role, [
            Role::Admin,
            Role::Directeur,
            Role::ChefMarketing,
            Role::AgentMarketeur,
            Role::Magasinier,
        ], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            Role::Admin,
            Role::Directeur,
            Role::AgentMarketeur,
        ], true);
    }

    public function update(User $user, Order $order): bool
    {
        if (in_array($user->role, [Role::Admin, Role::Directeur, Role::AgentMarketeur], true)) {
            return true;
        }

        return $order->user_id === $user->id && $order->statut === OrderStatus::EnAttenteValidation;
    }

    /**
     * Validating an order (stock impact) is reserved to staff roles.
     */
    public function validate(User $user, Order $order): bool
    {
        // Seul le Chef Marketing valide ; l'Admin garde un accès de secours.
        return in_array($user->role, [
            Role::ChefMarketing,
            Role::Admin,
        ], true);
    }

    /**
     * Refusing a pending order.
     */
    public function reject(User $user, Order $order): bool
    {
        return $this->validate($user, $order);
    }

    /**
     * Create Goods Issue Note (Bon de Sortie) - réservé au Magasinier.
     * Transition sécurisée : uniquement depuis Validee vers PretPourLivraison.
     */
    public function createGoodsIssueNote(User $user, Order $order): bool
    {
        if (! in_array($user->role, [Role::Magasinier, Role::Admin], true)) {
            return false;
        }

        return $order->statut === OrderStatus::Validee;
    }

    /**
     * Create Invoice - réservé à l'Agent Marketeur.
     * Transition sécurisée : uniquement depuis PretPourLivraison vers LivreeEtFacturee.
     */
    public function createInvoice(User $user, Order $order): bool
    {
        if ($order->statut !== OrderStatus::PretPourLivraison) {
            return false;
        }

        if ($user->role === Role::Admin) {
            return true;
        }

        // Seul l'Agent Marketeur propriétaire de la commande peut livrer/facturer.
        return $user->role === Role::AgentMarketeur && $order->user_id === $user->id;
    }

    public function delete(User $user, Order $order): bool
    {
        return in_array($user->role, [Role::Admin, Role::Directeur], true);
    }
}
