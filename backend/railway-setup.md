# Railway Deployment Guide for Laravel API

## Prerequisites
1. GitHub account
2. Railway account (sign up at railway.app)
3. Your Laravel project pushed to GitHub

## Step 1: Prepare Your Laravel Project

### 1.1 Create Railway Configuration
Create `railway.json` in your project root:

```json
{
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php artisan serve --host=0.0.0.0 --port=$PORT",
    "healthcheckPath": "/",
    "healthcheckTimeout": 100,
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### 1.2 Update .env for Production
Create `.env.production`:

```env
APP_NAME="Rapollo Ecommerce API"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-app-name.railway.app

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=your-railway-db-host
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=your-railway-db-password

# PayMongo Configuration
PAYMONGO_PUBLIC_KEY=pk_live_your_live_public_key
PAYMONGO_SECRET_KEY=sk_live_your_live_secret_key
PAYMONGO_WEBHOOK_SECRET=whsec_your_webhook_secret
PAYMONGO_BASE_URL=https://api.paymongo.com

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com,http://localhost:3000
```

### 1.3 Add Procfile
Create `Procfile` in your project root:

```
web: php artisan serve --host=0.0.0.0 --port=$PORT
```

### 1.4 Update composer.json
Add this to your `scripts` section in `composer.json`:

```json
{
  "scripts": {
    "post-install-cmd": [
      "php artisan key:generate --force",
      "php artisan migrate --force",
      "php artisan config:cache",
      "php artisan route:cache",
      "php artisan view:cache"
    ]
  }
}
```

## Step 2: Deploy to Railway

### 2.1 Connect GitHub
1. Go to railway.app
2. Sign up with GitHub
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Choose your repository

### 2.2 Add Database
1. In your Railway project dashboard
2. Click "New" → "Database" → "PostgreSQL"
3. Railway will create a PostgreSQL database
4. Copy the connection details

### 2.3 Configure Environment Variables
In Railway dashboard:
1. Go to your service
2. Click "Variables" tab
3. Add all your environment variables from `.env.production`

### 2.4 Deploy
Railway will automatically deploy when you push to your main branch.

## Step 3: Configure PayMongo Webhook

### 3.1 Get Your API URL
After deployment, Railway gives you a URL like:
`https://your-app-name.railway.app`

### 3.2 Set Webhook in PayMongo
1. Go to PayMongo Dashboard
2. Navigate to Webhooks
3. Create new webhook:
   - URL: `https://your-app-name.railway.app/api/webhooks/paymongo`
   - Events: `payment.paid`, `payment.failed`, `payment.cancelled`
4. Copy the webhook secret to your Railway environment variables

## Step 4: Test Your Deployment

### 4.1 Test API Health
```bash
curl https://your-app-name.railway.app/api/categories
```

### 4.2 Test Webhook
Use PayMongo's test webhook or ngrok to test locally first.

## Step 5: Domain Setup (Optional)

### 5.1 Custom Domain
1. In Railway dashboard
2. Go to your service → Settings → Domains
3. Add your custom domain
4. Update DNS records as instructed

## Troubleshooting

### Common Issues:
1. **Database connection**: Check PostgreSQL credentials
2. **CORS errors**: Update CORS_ALLOWED_ORIGINS
3. **Webhook not working**: Check HTTPS and URL format
4. **App key missing**: Run `php artisan key:generate` locally and copy the key

### Logs:
- View logs in Railway dashboard
- Check Laravel logs: `php artisan log:show`

## Cost
- **Free tier**: $5 credit monthly (usually enough for small projects)
- **Database**: Included in free tier
- **HTTPS**: Free
- **Custom domain**: Free

## Alternative: Render.com

If Railway doesn't work, try Render:
1. Sign up at render.com
2. Connect GitHub
3. Create "Web Service"
4. Select your repository
5. Add PostgreSQL database
6. Configure environment variables
7. Deploy!

Render free tier: 750 hours/month (usually enough for 24/7 operation)
