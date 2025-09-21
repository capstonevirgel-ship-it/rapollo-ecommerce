# 🆓 100% FREE Laravel API Hosting with Render

## Why Render?
- ✅ **Completely FREE forever** (no credit card required)
- ✅ **750 hours/month** (enough for 24/7 operation)
- ✅ **HTTPS included**
- ✅ **PostgreSQL database included**
- ✅ **Easy deployment**
- ✅ **Perfect for students**

## Step 1: Prepare Your Laravel Project

### 1.1 Create Render Configuration
Create `render.yaml` in your project root:

```yaml
services:
  - type: web
    name: rapollo-api
    env: php
    buildCommand: composer install --no-dev --optimize-autoloader
    startCommand: php artisan serve --host=0.0.0.0 --port=$PORT
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: false
      - key: LOG_CHANNEL
        value: stack
  - type: pserv
    name: rapollo-db
    env: postgresql
    plan: free
```

### 1.2 Update composer.json
Add this to your `scripts` section:

```json
{
  "scripts": {
    "deploy": [
      "php artisan key:generate --force",
      "php artisan migrate --force",
      "php artisan config:cache",
      "php artisan route:cache"
    ]
  }
}
```

### 1.3 Create .env.production
```env
APP_NAME="Rapollo Ecommerce API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database (Render will provide these)
DB_CONNECTION=pgsql
DB_HOST=your-render-db-host
DB_PORT=5432
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

# PayMongo Configuration
PAYMONGO_PUBLIC_KEY=pk_live_your_public_key
PAYMONGO_SECRET_KEY=sk_live_your_secret_key
PAYMONGO_WEBHOOK_SECRET=whsec_your_webhook_secret
PAYMONGO_BASE_URL=https://api.paymongo.com

# CORS
CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com,http://localhost:3000
```

## Step 2: Deploy to Render

### 2.1 Create Account
1. Go to [render.com](https://render.com)
2. Sign up with GitHub (no credit card required!)
3. Connect your GitHub account

### 2.2 Deploy Web Service
1. Click "New" → "Web Service"
2. Connect your GitHub repository
3. Configure:
   - **Name**: `rapollo-api`
   - **Environment**: `PHP`
   - **Build Command**: `composer install --no-dev --optimize-autoloader`
   - **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`
   - **Plan**: `Free`

### 2.3 Add Database
1. Click "New" → "PostgreSQL"
2. Configure:
   - **Name**: `rapollo-db`
   - **Plan**: `Free`
3. Copy the database credentials

### 2.4 Set Environment Variables
In your web service dashboard:
1. Go to "Environment" tab
2. Add all variables from `.env.production`
3. Use the database credentials from step 2.3

### 2.5 Deploy
1. Click "Create Web Service"
2. Render will automatically build and deploy
3. Your API will be available at: `https://your-app-name.onrender.com`

## Step 3: Configure PayMongo Webhook

### 3.1 Get Your API URL
After deployment: `https://your-app-name.onrender.com`

### 3.2 Set Webhook in PayMongo
1. Go to PayMongo Dashboard
2. Navigate to Webhooks
3. Create new webhook:
   - **URL**: `https://your-app-name.onrender.com/api/webhooks/paymongo`
   - **Events**: `payment.paid`, `payment.failed`, `payment.cancelled`
4. Copy the webhook secret to your Render environment variables

## Step 4: Test Your Deployment

### 4.1 Test API Health
```bash
curl https://your-app-name.onrender.com/api/categories
```

### 4.2 Test Webhook
Use PayMongo's test webhook feature.

## Step 5: Frontend Integration

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

## Free Tier Limits

### Render Free Tier:
- **Web Service**: 750 hours/month (31 days = 744 hours)
- **Database**: 1GB storage, 100 connections
- **Bandwidth**: 100GB/month
- **Sleep**: Service sleeps after 15 minutes of inactivity (wakes up on request)

### Important Notes:
- **Cold starts**: First request after sleep takes ~30 seconds
- **Database**: PostgreSQL free tier is sufficient for small projects
- **No credit card**: Completely free, no payment method required

## Troubleshooting

### Common Issues:
1. **Service sleeping**: Normal for free tier, first request will wake it up
2. **Database connection**: Check PostgreSQL credentials in environment variables
3. **CORS errors**: Update CORS_ALLOWED_ORIGINS
4. **Webhook not working**: Ensure HTTPS URL is correct

### Logs:
- View logs in Render dashboard
- Check "Logs" tab in your web service

## Alternative: Vercel (Also 100% Free)

If Render doesn't work, try Vercel:

### 1. Install Vercel CLI
```bash
npm i -g vercel
```

### 2. Deploy
```bash
# In your backend directory
vercel
```

### 3. Configure
- Follow Vercel's Laravel guide
- Use external free database (PlanetScale, Supabase, etc.)

## Cost Summary

| Service | Cost | Credit Card | Database | HTTPS |
|---------|------|-------------|----------|-------|
| **Render** | **FREE** | ❌ No | ✅ Included | ✅ Yes |
| **Vercel** | **FREE** | ❌ No | ❌ External | ✅ Yes |
| **Netlify** | **FREE** | ❌ No | ❌ External | ✅ Yes |
| **Railway** | $5 credit | ✅ Yes | ✅ Included | ✅ Yes |

**Winner: Render.com - 100% FREE with everything included! 🎉**
