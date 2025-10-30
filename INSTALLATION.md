# Knowledge Base API Installation Guide

This guide will walk you through installing and configuring the FreeScout Knowledge Base API module.

## Prerequisites

Before you begin, ensure you have:

1. ✅ FreeScout installed and running (version 1.8.0 or higher)
2. ✅ Knowledge Base feature enabled in FreeScout
3. ✅ Admin access to your FreeScout installation
4. ✅ SSH/FTP access to your server (for file uploads)

## Installation Steps

### Step 1: Upload the Module

There are two ways to install the module:

#### Option A: Direct Copy

1. Download or clone this repository
2. Copy the `Modules/KnowledgeBaseAPI` folder to your FreeScout's `Modules` directory

```bash
# On your server
cd /path/to/freescout
cp -r /path/to/GlobalDeskModules/Modules/KnowledgeBaseAPI ./Modules/
```

#### Option B: Symbolic Link (for development)

If you're developing or want to keep the module updated via git:

```bash
# On your server
cd /path/to/freescout/Modules
ln -s /path/to/GlobalDeskModules/Modules/KnowledgeBaseAPI ./KnowledgeBaseAPI
```

### Step 2: Set Permissions

Ensure the web server can read the module files:

```bash
cd /path/to/freescout
chown -R www-data:www-data Modules/KnowledgeBaseAPI
chmod -R 755 Modules/KnowledgeBaseAPI
```

(Replace `www-data` with your web server user if different, e.g., `apache`, `nginx`, etc.)

### Step 3: Activate the Module

1. Log in to your FreeScout admin panel
2. Navigate to **Manage → Modules**
3. Scroll down to find "KnowledgeBaseAPI"
4. Click the **Activate** button

### Step 4: Verify Installation

Test that the API is working:

```bash
# Test the health endpoint (no authentication required)
curl https://your-freescout.com/api/kb/health

# Expected response:
# {"status":"ok","module":"KnowledgeBaseAPI","version":"1.0.0"}
```

### Step 5: Configure the Module (Optional)

Edit the configuration file if needed:

```bash
nano /path/to/freescout/Modules/KnowledgeBaseAPI/Config/config.php
```

Available options:
- `enabled`: Enable/disable the API
- `rate_limit`: Requests per minute (default: 60)
- `require_auth`: Require authentication for protected endpoints
- `cors_origins`: Allowed CORS origins (default: '*')
- `include_content_in_list`: Include full article content in list responses

### Step 6: Clear Cache

Clear FreeScout's cache to ensure the module is properly loaded:

```bash
cd /path/to/freescout
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Testing the API

### Test Public Endpoints (No Authentication)

```bash
# List categories
curl https://your-freescout.com/api/kb/public/categories

# List articles
curl https://your-freescout.com/api/kb/public/articles

# Search
curl "https://your-freescout.com/api/kb/public/search?q=password"
```

### Test Protected Endpoints (With API Key)

```bash
# Get your API key from FreeScout: Profile → API Settings

# List all categories (authenticated)
curl -H "X-FreeScout-API-Key: YOUR_API_KEY" \
     https://your-freescout.com/api/kb/categories

# List all articles (authenticated)
curl -H "X-FreeScout-API-Key: YOUR_API_KEY" \
     https://your-freescout.com/api/kb/articles
```

## Using the JavaScript Client

### 1. Include the JavaScript file

```html
<script src="path/to/knowledge-base-client.js"></script>
```

### 2. Initialize and use

```javascript
const client = new FreeScoutKBClient('https://your-freescout.com');

// Get categories
client.getCategories().then(categories => {
    console.log('Categories:', categories);
});

// Search
client.search('password').then(results => {
    console.log('Search results:', results);
});
```

### 3. Use the complete widget

```html
<div id="kb-container"></div>

<script>
    const widget = new KnowledgeBaseWidget(
        'kb-container',
        'https://your-freescout.com'
    );
</script>
```

## Using the Python Client

### 1. Install dependencies

```bash
cd backend
pip install -r requirements.txt
```

### 2. Configure environment

Create a `.env` file:

```env
FREESCOUT_BASE_URL=https://your-freescout.com
FREESCOUT_API_KEY=your_api_key_here
```

### 3. Use the client

```python
from GlobalDeskClient import FreeScoutClient

client = FreeScoutClient()

# Get categories
categories = client.list_kb_categories(public=True)
print(f"Found {len(categories)} categories")

# Get articles
articles = client.list_kb_articles(public=True, per_page=10)
print(f"Found {len(articles.get('data', []))} articles")

# Search
results = client.search_kb_articles("password", public=True)
print(f"Found {len(results)} search results")
```

## Troubleshooting

### Module doesn't appear in FreeScout

**Solution:**
1. Check file permissions: `ls -la /path/to/freescout/Modules/KnowledgeBaseAPI`
2. Ensure `module.json` exists and is valid JSON
3. Clear cache: `php artisan cache:clear`
4. Check FreeScout logs: `storage/logs/laravel.log`

### "Table kb_categories doesn't exist" error

**Solution:**
This module requires a Knowledge Base feature/module to be installed in FreeScout that creates the necessary database tables. Check if you have:
- A Knowledge Base module enabled
- Database tables: `kb_categories` and `kb_articles`

If not, you may need to install a Knowledge Base module first or create these tables manually.

### API returns empty results

**Solution:**
1. Check if you have any knowledge base articles created in FreeScout
2. Ensure articles are marked as "published" and "public" (for public endpoints)
3. Check the database: `SELECT * FROM kb_articles;`

### CORS errors in JavaScript

**Solution:**
1. Update `Config/config.php` in the module:
   ```php
   'cors_origins' => ['https://yourdomain.com'],
   ```
2. Or allow all origins during development:
   ```php
   'cors_origins' => ['*'],
   ```
3. Clear cache after changes

### 401 Unauthorized errors

**Solution:**
1. For protected endpoints, ensure you're sending the API key:
   ```
   X-FreeScout-API-Key: your_api_key_here
   ```
2. Verify your API key is correct in FreeScout: Profile → API Settings
3. Use public endpoints if you don't need authentication

## Uninstallation

To remove the module:

1. Deactivate the module in FreeScout admin panel
2. Delete the module directory:
   ```bash
   rm -rf /path/to/freescout/Modules/KnowledgeBaseAPI
   ```
3. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

## Database Schema

If you need to create the tables manually, here's the basic schema:

```sql
CREATE TABLE kb_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    slug VARCHAR(255),
    parent_id INT NULL,
    `order` INT DEFAULT 0,
    visibility ENUM('public', 'private') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES kb_categories(id) ON DELETE SET NULL
);

CREATE TABLE kb_articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    content TEXT,
    excerpt TEXT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    visibility ENUM('public', 'private') DEFAULT 'public',
    `order` INT DEFAULT 0,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES kb_categories(id) ON DELETE CASCADE
);
```

## Getting Help

- **GitHub Issues**: https://github.com/gabeparra/GlobalDeskModules/issues
- **FreeScout Forums**: https://freescout.net/community/
- **Documentation**: See README.md files in each directory

## Next Steps

1. ✅ Install the module (you're here!)
2. 📝 Create some knowledge base articles in FreeScout
3. 🧪 Test the API endpoints
4. 🎨 Integrate the JavaScript widget into your website
5. 🚀 Start using the API in your applications

Happy coding! 🎉
