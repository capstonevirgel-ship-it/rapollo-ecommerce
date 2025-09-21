# 🚀 Quick Deploy Guide for Students

## Option 1: Railway (Recommended - 5 minutes setup)

### Step 1: Push to GitHub
```bash
# In your backend directory
git add .
git commit -m "Add PayMongo webhook system"
git push origin main
```

### Step 2: Deploy to Railway
1. Go to [railway.app](https://railway.app)
2. Sign up with GitHub
3. Click "New Project" → "Deploy from GitHub repo"
4. Select your repository
5. Railway will auto-detect Laravel and deploy!

### Step 3: Add Database
1. In Railway dashboard, click "New" → "Database" → "PostgreSQL"
2. Copy the database credentials

### Step 4: Set Environment Variables
In Railway dashboard → Your service → Variables tab, add:

```env
APP_NAME="Rapollo Ecommerce API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.railway.app

DB_CONNECTION=pgsql
DB_HOST=your-railway-db-host
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=your-railway-db-password

PAYMONGO_PUBLIC_KEY=pk_live_your_key
PAYMONGO_SECRET_KEY=sk_live_your_key
PAYMONGO_WEBHOOK_SECRET=whsec_your_secret
```

### Step 5: Set PayMongo Webhook
1. Go to PayMongo Dashboard → Webhooks
2. Create webhook: `https://your-app-name.railway.app/api/webhooks/paymongo`
3. Select events: `payment.paid`, `payment.failed`, `payment.cancelled`

**Done! Your API is live with HTTPS! 🎉**

---

## Option 2: Render (Alternative)

### Step 1: Deploy
1. Go to [render.com](https://render.com)
2. Sign up with GitHub
3. Click "New" → "Web Service"
4. Connect your repository
5. Build command: `composer install --no-dev --optimize-autoloader`
6. Start command: `php artisan serve --host=0.0.0.0 --port=$PORT`

### Step 2: Add Database
1. Click "New" → "PostgreSQL"
2. Copy credentials to environment variables

### Step 3: Set Environment Variables
Same as Railway, but in Render dashboard

---

## Option 3: Heroku (Student Program)

### Step 1: Apply for Student Program
1. Go to [education.github.com/pack](https://education.github.com/pack)
2. Apply for Heroku credits
3. Get $13/month in credits (more than enough!)

### Step 2: Deploy
```bash
# Install Heroku CLI
# Login and create app
heroku create your-app-name
heroku addons:create heroku-postgresql:hobby-dev
git push heroku main
```

---

## 🎯 **Recommended: Railway**

**Why Railway?**
- ✅ Free $5 credit monthly (enough for students)
- ✅ Automatic HTTPS
- ✅ PostgreSQL database included
- ✅ Git-based deployment
- ✅ Easy environment variable management
- ✅ Great for Laravel

**Cost: FREE for students!**

---

## 🔧 **Quick Test Commands**

After deployment, test your API:

```bash
# Test API health
curl https://your-app-name.railway.app/api/categories

# Test webhook (use PayMongo test webhook)
curl -X POST https://your-app-name.railway.app/api/webhooks/paymongo \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'
```

---

## 📱 **Frontend Integration**

Update your frontend to use the new API URL:

```javascript
// In your frontend
const API_BASE_URL = 'https://your-app-name.railway.app/api';

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

**You're all set! Your Laravel API with PayMongo webhooks is now live! 🚀**
