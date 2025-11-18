<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Event;
use App\Mail\ContactFormMail;
use App\Mail\PaymentFailureNotification;
use App\Mail\ProductPurchaseConfirmation;
use App\Mail\TicketPurchaseConfirmation;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {type=product : product|ticket|payment-failure|contact} {email?} {--purchase=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending with Gmail SMTP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $email = $this->argument('email');
        
        if (!$email) {
            $email = 'lancejavate2002@gmail.com';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address');
            return 1;
        }
        
        $this->info("Testing {$type} email to: {$email}");
        
        try {
            $purchaseId = $this->option('purchase');

            match ($type) {
                'product' => $this->testProductEmail($email, $purchaseId),
                'ticket' => $this->testTicketEmail($email, $purchaseId),
                'payment-failure' => $this->testPaymentFailureEmail($email, $purchaseId),
                'contact' => $this->testContactEmail($email),
                default => throw new \InvalidArgumentException('Invalid type. Use product, ticket, payment-failure, or contact'),
            };
            
            $this->info('Test email sent successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
            return 1;
        }
    }
    
    private function resolvePurchase(?string $purchaseId, array $relations): ?Purchase
    {
        if (!$purchaseId) {
            return null;
        }

        return Purchase::with($relations)->find($purchaseId);
    }

    private function testProductEmail(string $email, ?string $purchaseId = null): void
    {
        $relations = [
            'items.variant.product.images',
            'items.variant.color',
            'items.variant.size',
            'user',
        ];

        $purchase = $this->resolvePurchase($purchaseId, $relations);

        if ($purchase && $purchase->isProductPurchase()) {
            $user = $purchase->user ?: new User(['user_name' => 'Rapollo Customer', 'email' => $email]);
            Mail::to($email)->send(new ProductPurchaseConfirmation($purchase, $user));
            return;
        }

        // fallback to seeded sample data
        $user = new User();
        $user->user_name = 'Test User';
        $user->email = $email;

        $purchase = new Purchase();
        $purchase->id = 999;
        $purchase->reference_number = 'TEST-PROD-' . time();
        $purchase->total_amount = 1250.00;
        $purchase->total = 1250.00;
        $purchase->type = 'product';
        $purchase->status = 'completed';

        $item = new \stdClass();
        $item->quantity = 2;
        $item->price = 625.00;

        $variant = new \stdClass();
        $variant->color = (object) ['name' => 'Red'];
        $variant->size = (object) ['name' => 'Large'];

        $product = new \stdClass();
        $product->name = 'Test Fashion Product';
        $product->images = collect();
        $variant->product = $product;
        $item->variant = $variant;

        $purchase->setRelation('items', collect([$item]));

        Mail::to($email)->send(new ProductPurchaseConfirmation($purchase, $user));
    }
    
    private function testTicketEmail(string $email, ?string $purchaseId = null): void
    {
        $relations = [
            'event',
            'tickets',
            'user',
        ];

        $purchase = $this->resolvePurchase($purchaseId, $relations);

        if ($purchase && $purchase->isTicketPurchase()) {
            $user = $purchase->user ?: new User(['user_name' => 'Rapollo Customer', 'email' => $email]);
            Mail::to($email)->send(new TicketPurchaseConfirmation($purchase, $user));
            return;
        }

        $user = new User();
        $user->user_name = 'Test User';
        $user->email = $email;

        $purchase = new Purchase();
        $purchase->id = 999;
        $purchase->reference_number = 'TEST-TICKET-' . time();
        $purchase->total_amount = 500.00;
        $purchase->total = 500.00;
        $purchase->type = 'ticket';
        $purchase->status = 'completed';

        $event = new Event();
        $event->title = 'Test Fashion Show 2025';
        $event->date = now()->addDays(30)->toDateTimeString();
        $event->location = 'Manila Convention Center';
        $event->description = 'A spectacular fashion show featuring the latest trends';

        $purchase->setRelation('event', $event);

        $ticket1 = new \stdClass();
        $ticket1->ticket_number = 'TKT-' . time() . '-001';
        $ticket1->price = 250.00;
        $ticket1->quantity = 1;

        $ticket2 = new \stdClass();
        $ticket2->ticket_number = 'TKT-' . time() . '-002';
        $ticket2->price = 250.00;
        $ticket2->quantity = 1;

        $purchase->setRelation('tickets', collect([$ticket1, $ticket2]));

        Mail::to($email)->send(new TicketPurchaseConfirmation($purchase, $user));
    }

    private function testPaymentFailureEmail(string $email, ?string $purchaseId = null): void
    {
        $relations = [
            'event',
            'items.variant.product.images',
            'items.variant.color',
            'items.variant.size',
            'user',
        ];

        $purchase = $this->resolvePurchase($purchaseId, $relations);
        $user = $purchase?->user ?: new User(['user_name' => 'Test User', 'email' => $email]);

        if (!$purchase) {
            $purchase = new Purchase();
            $purchase->id = 1001;
            $purchase->reference_number = 'TEST-FAIL-' . time();
            $purchase->total = 2999.00;
            $purchase->type = 'product';
            $purchase->status = 'failed';

            $item = new \stdClass();
            $item->quantity = 1;
            $item->price = 2999.00;

            $variant = new \stdClass();
            $product = new \stdClass();
            $product->name = 'Signature Hoodie';
            $product->images = collect();
            $variant->product = $product;
            $item->variant = $variant;

            $purchase->setRelation('items', collect([$item]));
        }

        Mail::to($email)->send(
            new PaymentFailureNotification(
                $purchase,
                $user,
                failureReason: 'Your bank declined the transaction. Please use a different payment method.',
                failureCode: '402'
            )
        );
    }

    private function testContactEmail(string $email): void
    {
        $formData = [
            'firstName' => 'Alex',
            'lastName' => 'Rivera',
            'email' => $email,
            'phone' => '09171234567',
            'subject' => 'order',
            'message' => "Hi Rapollo team,\n\nCould you help me with an exchange for my recent order?\n\nThanks,\nAlex",
        ];

        Mail::to($email)->send(new ContactFormMail($formData));
    }
}