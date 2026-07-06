<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BonSortieEmis extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Bon de Sortie émis - Commande {$this->order->reference}")
            ->line("Le Bon de Sortie pour la commande {$this->order->reference} a été émis.")
            ->line("Vous disposez maintenant de l'autorisation officielle de livrer cette commande.")
            ->action('Voir la commande', route('orders.show', $this->order))
            ->line('Merci d\'utiliser notre application.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'client' => $this->order->client->name,
            'message' => "Bon de Sortie émis pour la commande {$this->order->reference}. Vous pouvez livrer.",
        ];
    }
}
