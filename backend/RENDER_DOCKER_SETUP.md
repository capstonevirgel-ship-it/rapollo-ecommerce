# 🐳 Deploy Laravel with Docker on Render (100% Free)

Based on the [official Render documentation](https://render.com/docs/deploy-php-laravel-docker), here's how to deploy your Laravel API with PayMongo webhooks.

## Prerequisites
1. GitHub repository: `capstonevirgel-ship-it/rapollo-ecommerce`
2. Render account (free)
3. PayMongo account

## Step 1: Create PostgreSQL Database

1. Go to [render.com](https://render.com)
2. Click "New" → "PostgreSQL"
3. Configure:
   - **Name**: `rapollo-db`
   - **Plan**: `Free`
4. **Copy the Internal Database URL** (you'll need this)

## Step 2: Deploy Laravel Web Service

1. Click "New" → "Web Service"
2. Connect your GitHub repository: `capstonevirgel-ship-it/rapollo-ecommerce`
3. Configure:
   - **Name**: `rapollo-api`
   - **Runtime**: `Docker`
   - **Root Directory**: `backend`
   - **Plan**: `Free`

## Step 3: Set Environment Variables

In your web service dashboard, go to "Environment" tab and add:

```env
# App Configuration
APP_NAME="Rapollo Ecommerce API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com

# Database (Use the Internal Database URL from Step 1)
DATABASE_URL=postgresql://username:password@host:port/database
DB_CONNECTION=pgsql

# App Key (Generate this locally first)
APP_KEY=base64:YOUR_APP_KEY_HERE

# PayMongo Configuration
PAYMONGO_PUBLIC_KEY=pk_live_your_public_key
PAYMONGO_SECRET_KEY=sk_live_your_secret_key
PAYMONGO_WEBHOOK_SECRET=whsec_your_webhook_secret
PAYMONGO_BASE_URL=https://api.paymongo.com

# CORS
CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com,http://localhost:3000

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

## Step 4: Generate App Key

Before deploying, generate your app key locally:

```bash
cd backend
php artisan key:generate --show
```

Copy the output and use it as your `APP_KEY` environment variable.

## Step 5: Deploy

1. Click "Create Web Service"
2. Render will automatically build and deploy using Docker
3. Your API will be available at: `https://your-app-name.onrender.com`

## Step 6: Configure PayMongo Webhook

1. Go to PayMongo Dashboard
2. Navigate to Webhooks
3. Create new webhook:
   - **URL**: `https://your-app-name.onrender.com/api/webhooks/paymongo`
   - **Events**: `payment.paid`, `payment.failed`, `payment.cancelled`
4. Copy the webhook secret to your Render environment variables

## Step 7: Test Your Deployment

### Test API Health
```bash
curl https://your-app-name.onrender.com/api/categories
```

### Test PayMongo Payment
```bash
curl -X POST https://your-app-name.onrender.com/api/payments/paymongo/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "cart_data": {
      "items": [
        {
          "product_variant_id": 1,
          "quantity": 1,
          "price": 100.00
        }
      ]
    }
  }'
```

## Docker Configuration

The deployment uses these Docker files I created:

- **`Dockerfile`**: Multi-stage build with PHP 8.1, NGINX, and Composer
- **`docker/nginx.conf`**: NGINX configuration for Laravel
- **`docker/php-fpm.conf`**: PHP-FPM configuration
- **`docker/start.sh`**: Startup script that runs migrations and caches
- **`.dockerignore`**: Excludes unnecessary files from Docker image

## Free Tier Limits

- **Web Service**: 750 hours/month (31 days = 744 hours)
- **Database**: 1GB storage, 100 connections
- **Bandwidth**: 100GB/month
- **Sleep**: Service sleeps after 15 minutes of inactivity

## Troubleshooting

### Common Issues:
1. **Service sleeping**: Normal for free tier, first request will wake it up
2. **Database connection**: Check `DATABASE_URL` format
3. **CORS errors**: Update `CORS_ALLOWED_ORIGINS`
4. **Webhook not working**: Ensure HTTPS URL is correct

### Logs:
- View logs in Render dashboard
- Check "Logs" tab in your web service

## Frontend Integration

Update your frontend to use the new API URL:

```javascript
// In your frontend
const API_BASE_URL = 'https://your-app-name.onrender.com/api';

// Create PayMongo payment
const response = await fetch(`${API_BASE_URL}/payments/paymongo/create`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify(cartData)
});
```

## Cost: 100% FREE! 🎉

- No credit card required
- No hidden costs
- Perfect for students and small projects
- Includes PostgreSQL database
- Automatic HTTPS
- Docker-based deployment

Your Laravel API with PayMongo webhooks is now live and ready to handle payments! 🚀
