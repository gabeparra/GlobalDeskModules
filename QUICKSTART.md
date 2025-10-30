# Quick Start Guide

Get the FreeScout Knowledge Base API up and running in 5 minutes!

## 🚀 For Module Installation (FreeScout Admins)

### 1. Copy Module to FreeScout
```bash
cp -r Modules/KnowledgeBaseAPI /path/to/freescout/Modules/
```

### 2. Activate in FreeScout
- Login as admin → **Manage** → **Modules**
- Find "KnowledgeBaseAPI" → Click **Activate**

### 3. Test It
```bash
curl https://your-freescout.com/api/kb/health
```

Expected response:
```json
{"status":"ok","module":"KnowledgeBaseAPI","version":"1.0.0"}
```

**Done!** Your Knowledge Base is now accessible via API. 🎉

---

## 💻 For JavaScript Developers

### 1. Include the Client
```html
<script src="knowledge-base-client.js"></script>
```

### 2. Initialize and Use
```javascript
const client = new FreeScoutKBClient('https://your-freescout.com');

// Get all categories
const categories = await client.getCategories();
console.log(categories);

// Search articles
const results = await client.search('password reset');
console.log(results);
```

### 3. Or Use the Complete Widget
```html
<div id="kb-widget"></div>

<script src="knowledge-base-client.js"></script>
<script>
    const widget = new KnowledgeBaseWidget(
        'kb-widget',
        'https://your-freescout.com'
    );
</script>
```

**Done!** You have a working KB widget. 🎉

---

## 🐍 For Python Developers

### 1. Install Dependencies
```bash
cd backend
pip install -r requirements.txt
```

### 2. Configure
Create `.env` file:
```env
FREESCOUT_BASE_URL=https://your-freescout.com
FREESCOUT_API_KEY=your_api_key_here
```

### 3. Use the Client
```python
from GlobalDeskClient import FreeScoutClient

client = FreeScoutClient()

# Get categories
categories = client.list_kb_categories(public=True)

# Get articles
articles = client.list_kb_articles(public=True)

# Search
results = client.search_kb_articles("help", public=True)
```

**Done!** You're ready to integrate. 🎉

---

## 📖 Available API Endpoints

### Public (No Auth Required)
```
GET /api/kb/public/categories
GET /api/kb/public/categories/{id}
GET /api/kb/public/articles
GET /api/kb/public/articles/{id}
GET /api/kb/public/search?q={query}
```

### Protected (API Key Required)
```
GET /api/kb/categories
GET /api/kb/articles
GET /api/kb/search?q={query}
```

---

## 🔑 Get Your API Key

1. Login to FreeScout
2. Click your profile → **API Settings**
3. Copy the API key
4. Use it in the header:
   ```
   X-FreeScout-API-Key: your_api_key_here
   ```

---

## 📚 Need More Help?

- **Full Documentation**: See [README.md](README.md)
- **Installation Guide**: See [INSTALLATION.md](INSTALLATION.md)
- **Module Docs**: See [Modules/KnowledgeBaseAPI/README.md](Modules/KnowledgeBaseAPI/README.md)
- **Demo**: Open [frontend/demo.html](frontend/demo.html)
- **Issues**: https://github.com/gabeparra/GlobalDeskModules/issues

---

## ✨ Example Response

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Getting Started",
            "description": "New user guides",
            "children": []
        },
        {
            "id": 2,
            "name": "Account Management",
            "description": "Managing your account",
            "children": [
                {
                    "id": 3,
                    "name": "Password & Security",
                    "description": "Security guides"
                }
            ]
        }
    ],
    "count": 2
}
```

Happy coding! 🚀
