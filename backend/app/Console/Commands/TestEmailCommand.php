<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Event;
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
    protected $signature = 'test:email {type=product} {email?}';

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
            if ($type === 'product') {
                $this->testProductEmail($email);
            } elseif ($type === 'ticket') {
                $this->testTicketEmail($email);
            } else {
                $this->error('Invalid type. Use "product" or "ticket"');
                return 1;
            }
            
            $this->info('Test email sent successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
            return 1;
        }
    }
    
    private function testProductEmail($email)
    {
        // Create a fake user
        $user = new User();
        $user->user_name = 'Test User';
        $user->email = $email;
        
        // Create a fake purchase
        $purchase = new Purchase();
        $purchase->id = 999;
        $purchase->reference_number = 'TEST-PROD-' . time();
        $purchase->total_amount = 1250.00;
        $purchase->type = 'product';
        $purchase->status = 'completed';
        
        // Mock purchase items with proper structure
        $item = new \stdClass();
        $item->quantity = 2;
        $item->price = 625.00;
        
        // Mock variant with product and images
        $variant = new \stdClass();
        $variant->color = (object) ['name' => 'Red'];
        $variant->size = (object) ['name' => 'Large'];
        
        $product = new \stdClass();
        $product->name = 'Test Fashion Product';
        
        // Create a mock images collection that responds to where() and first()
        $images = new class {
            public function where($column, $value) {
                return new class {
                    public function first() { return null; }
                };
            }
            public function first() { return null; }
        };
        
        $product->images = $images;
        $variant->product = $product;
        $item->variant = $variant;
        
        $purchase->items = collect([$item]);
        
        Mail::to($email)->send(new ProductPurchaseConfirmation($purchase, $user));
    }
    
    private function testTicketEmail($email)
    {
        // Create a fake user
        $user = new User();
        $user->user_name = 'Test User';
        $user->email = $email;
        
        // Create a fake purchase
        $purchase = new Purchase();
        $purchase->id = 999;
        $purchase->reference_number = 'TEST-TICKET-' . time();
        $purchase->total_amount = 500.00;
        $purchase->type = 'ticket';
        $purchase->status = 'completed';
        
        // Mock event
        $event = new Event();
        $event->title = 'Test Fashion Show 2025';
        $event->date = now()->addDays(30)->toDateTimeString();
        $event->location = 'Manila Convention Center';
        $event->description = 'A spectacular fashion show featuring the latest trends';
        
        $purchase->event = $event;
        
        // Mock tickets
        $ticket1 = new \stdClass();
        $ticket1->ticket_number = 'TKT-' . time() . '-001';
        $ticket1->price = 250.00;
        
        $ticket2 = new \stdClass();
        $ticket2->ticket_number = 'TKT-' . time() . '-002';
        $ticket2->price = 250.00;
        
        $purchase->tickets = collect([$ticket1, $ticket2]);
        
        Mail::to($email)->send(new TicketPurchaseConfirmation($purchase, $user));
    }
}