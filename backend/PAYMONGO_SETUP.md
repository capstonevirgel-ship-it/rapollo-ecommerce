# PayMongo Webhook Setup Guide

## Environment Variables

Add these to your `.env` file:

```env
# PayMongo Configuration
# Get these from your PayMongo dashboard: https://dashboard.paymongo.com/

# Public key for frontend (safe to expose)
PAYMONGO_PUBLIC_KEY=pk_test_your_public_key_here

# Secret key for backend (keep this secure!)
PAYMONGO_SECRET_KEY=sk_test_your_secret_key_here

# Webhook secret for verifying webhook signatures
PAYMONGO_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# PayMongo API base URL (usually don't need to change this)
PAYMONGO_BASE_URL=https://api.paymongo.com
```

## Webhook Configuration

1. **Set up webhook in PayMongo Dashboard:**
   - Go to https://dashboard.paymongo.com/
   - Navigate to Webhooks section
   - Create a new webhook with URL: `https://yourdomain.com/api/webhooks/paymongo`
   - Select events: `payment.paid`, `payment.failed`, `payment.cancelled`
   - Copy the webhook secret and add it to your `.env` file

2. **Test webhook locally:**
   - Use ngrok or similar tool to expose your local server
   - Update webhook URL in PayMongo dashboard to your ngrok URL
   - Test payments to verify webhook is working

## API Endpoints

### Create PayMongo Payment
```
POST /api/payments/paymongo/create
```

**Request Body:**
```json
{
  "cart_data": {
    "items": [
      {
        "product_variant_id": 1,
        "quantity": 2,
        "price": 100.00
      }
    ],
    "shipping_address": {
      "name": "John Doe",
      "address": "123 Main St",
      "city": "Manila",
      "postal_code": "1000"
    },
    "billing_address": {
      "name": "John Doe",
      "address": "123 Main St",
      "city": "Manila",
      "postal_code": "1000"
    }
  }
}
```

**Response:**
```json
{
  "message": "Payment intent created successfully.",
  "payment_intent_id": "pi_xxx",
  "client_key": "client_key_xxx",
  "purchase_id": 123,
  "redirect_url": "https://api.paymongo.com/v1/payment_intents/pi_xxx/confirm"
}
```

### Verify Payment Status
```
POST /api/payments/paymongo/verify
```

**Request Body:**
```json
{
  "payment_intent_id": "pi_xxx"
}
```

### Webhook Endpoint
```
POST /api/webhooks/paymongo
```
This endpoint is automatically called by PayMongo when payment events occur.

## Frontend Integration

1. **Install PayMongo SDK:**
   ```bash
   npm install @paymongo/paymongo-js
   ```

2. **Create payment intent:**
   ```javascript
   const response = await fetch('/api/payments/paymongo/create', {
     method: 'POST',
     headers: {
       'Content-Type': 'application/json',
       'Authorization': `Bearer ${token}`
     },
     body: JSON.stringify(cartData)
   });
   
   const { payment_intent_id, client_key, redirect_url } = await response.json();
   ```

3. **Redirect to PayMongo:**
   ```javascript
   // Option 1: Direct redirect
   window.location.href = redirect_url;
   
   // Option 2: Use PayMongo SDK for custom UI
   const paymongo = new PayMongo(client_key);
   // Implement custom payment form
   ```

## Database Changes

The following fields have been added to the `purchases` table:
- `payment_status` - Status of the payment (pending, paid, failed, cancelled)
- `payment_intent_id` - PayMongo payment intent ID
- `payment_amount` - Amount paid
- `payment_currency` - Currency used
- `payment_failure_code` - Failure code if payment failed
- `payment_failure_message` - Failure message if payment failed
- `shipping_address` - JSON field for shipping address
- `billing_address` - JSON field for billing address
- `paid_at` - Timestamp when payment was completed

## Security Notes

1. **Webhook Signature Verification:** The webhook controller verifies PayMongo's signature to ensure requests are legitimate.

2. **Environment Variables:** Never commit your secret keys to version control.

3. **HTTPS Required:** PayMongo webhooks require HTTPS in production.

4. **Error Handling:** All webhook events are logged for debugging purposes.

## Testing

1. Use PayMongo's test mode for development
2. Test all payment scenarios (success, failure, cancellation)
3. Verify webhook events are processed correctly
4. Check database updates after successful payments
