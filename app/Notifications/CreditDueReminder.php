<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreditDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $echeance = $this->invoice->date_echeance?->format('d/m/Y');
        $reste = number_format($this->invoice->resteAPayer(), 2, ',', ' ');

        return (new MailMessage)
            ->subject("Rappel échéance (J-3) - Facture {$this->invoice->reference}")
            ->line("La facture {$this->invoice->reference} arrive à échéance le {$echeance}.")
            ->line("Client : {$this->invoice->client->name}")
            ->line("Reste à payer : {$reste} \$")
            ->action('Voir la facture', route('invoices.show', $this->invoice))
            ->line('Merci d\'assurer le suivi du recouvrement.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'reference' => $this->invoice->reference,
            'client' => $this->invoice->client->name,
            'date_echeance' => $this->invoice->date_echeance?->toDateString(),
            'reste_a_payer' => $this->invoice->resteAPayer(),
            'message' => "Échéance J-3 : facture {$this->invoice->reference} ({$this->invoice->client->name}).",
        ];
    }
}
