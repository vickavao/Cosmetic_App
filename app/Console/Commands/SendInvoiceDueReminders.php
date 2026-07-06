<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\SaleType;
use App\Models\Invoice;
use App\Notifications\CreditDueReminder;
use Illuminate\Console\Command;

class SendInvoiceDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:invoice-due-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for invoices due in 3 days (J-3)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetDate = now()->addDays(3)->toDateString();

        $invoices = Invoice::query()
            ->where('type_vente', SaleType::Credit->value)
            ->whereDate('date_echeance', $targetDate)
            ->whereIn('statut', [InvoiceStatus::Emise->value, InvoiceStatus::Partielle->value])
            ->with('client', 'agent')
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('Aucune facture avec échéance J-3 trouvée.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($invoices as $invoice) {
            if ($invoice->agent) {
                $invoice->agent->notify(new CreditDueReminder($invoice));
                $count++;
            }
        }

        $this->info("Rappels J-3 envoyés pour {$count} facture(s).");

        return self::SUCCESS;
    }
}
