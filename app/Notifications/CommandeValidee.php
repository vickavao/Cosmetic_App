<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommandeValidee extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Commande validée - '.$this->order->reference)
            ->greeting('Bonjour,')
            ->line(sprintf(
                'La commande %s du client %s a été validée et est prête à être préparée.',
                $this->order->reference,
                $this->order->client?->name ?? 'Inconnu'
            ))
            ->action('Voir la commande', route('orders.show', $this->order))
            ->line('Merci de préparer le bon de sortie.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'commande_validee',
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'client_name' => $this->order->client?->name,
            'total' => (float) $this->order->total,
            'message' => sprintf(
                'La commande %s (%s) a été validée. Merci de préparer le bon de sortie.',
                $this->order->reference,
                $this->order->client?->name ?? 'Inconnu'
            ),
        ];
    }
}
