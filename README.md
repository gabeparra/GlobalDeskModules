# GlobalDeskModules

A collection of tools and modules for FreeScout, including a Knowledge Base API module that exposes the knowledge base through REST API endpoints for consumption by JavaScript and other applications.

## 🚀 Features

### Knowledge Base API Module (PHP/Laravel)
- ✅ RESTful API endpoints for FreeScout Knowledge Base
- ✅ Public and authenticated endpoints
- ✅ List categories with hierarchical structure
- ✅ Get articles by category
- ✅ Full-text search functionality
- ✅ Article view tracking
- ✅ Pagination support
- ✅ CORS support for JavaScript consumption

### Python Client
- ✅ Easy-to-use Python client for FreeScout API
- ✅ Support for customers, tickets, conversations, and knowledge base
- ✅ Built-in methods for the new Knowledge Base API

### JavaScript Client
- ✅ Modern JavaScript/ES6 client library
- ✅ Complete Knowledge Base widget implementation
- ✅ Interactive demo page
- ✅ Examples for fetch API, jQuery, and vanilla JS

## 📦 Installation

### 1. FreeScout Module Installation

Copy the Knowledge Base API module to your FreeScout installation:

```bash
# Copy the module to FreeScout's Modules directory
cp -r Modules/KnowledgeBaseAPI /path/to/freescout/Modules/

# Or create a symbolic link
ln -s /path/to/GlobalDeskModules/Modules/KnowledgeBaseAPI /path/to/freescout/Modules/
```

Then activate the module in FreeScout:
1. Log in to FreeScout as admin
2. Go to **Manage → Modules**
3. Find "KnowledgeBaseAPI" in the list
4. Click **Activate**

### 2. Python Client Setup

```bash
cd backend

# Install dependencies
pip install -r requirements.txt

# Set up environment variables
cp .env.example .env  # If available, or create new .env file

# Edit .env and add your FreeScout credentials:
# FREESCOUT_BASE_URL=https://your-freescout-instance.com
# FREESCOUT_API_KEY=your_api_key_here
```

### 3. JavaScript Client Setup

Simply include the JavaScript client in your HTML:

```html
<script src="frontend/knowledge-base-client.js"></script>
```

Or view the demo page:
```bash
# Open frontend/demo.html in your browser
```

## 🔧 Usage

### Python Client

```python
from backend.GlobalDeskClient import FreeScoutClient

# Initialize client (uses .env file)
client = FreeScoutClient()

# Get all KB categories
categories = client.list_kb_categories(public=True)

# Get articles from a category
articles = client.list_kb_articles(category_id=1, public=True)

# Search articles
results = client.search_kb_articles("password reset", public=True)

# Get a specific article
article = client.get_kb_article(article_id=5, public=True)
```

### JavaScript Client

```javascript
// Initialize client
const client = new FreeScoutKBClient('https://your-freescout.com');

// Get categories
const categories = await client.getCategories();

// Get articles
const articles = await client.getArticles({ categoryId: 1, page: 1 });

// Search
const results = await client.search('password reset');

// Get specific article
const article = await client.getArticle(5);
```

### Complete Widget Example

```javascript
// Initialize the complete widget
const kbWidget = new KnowledgeBaseWidget(
    'kb-container',  // Container element ID
    'https://your-freescout.com'  // FreeScout URL
);
```

See `frontend/demo.html` for a complete working example.

## 📚 API Endpoints

### Public Endpoints (No Authentication Required)

```
GET /api/kb/public/categories           - List all public categories
GET /api/kb/public/categories/{id}      - Get category with articles
GET /api/kb/public/articles             - List all public articles
GET /api/kb/public/articles/{id}        - Get specific article
GET /api/kb/public/search?q={query}     - Search articles
```

### Authenticated Endpoints (API Key Required)

```
GET /api/kb/categories                  - List all categories
GET /api/kb/categories/{id}             - Get category with articles
GET /api/kb/articles                    - List all articles
GET /api/kb/articles/{id}               - Get specific article
GET /api/kb/search?q={query}            - Search articles
GET /api/kb/health                      - Health check
```

## 📖 Documentation

- **Module Documentation**: See [Modules/KnowledgeBaseAPI/README.md](Modules/KnowledgeBaseAPI/README.md)
- **JavaScript Examples**: See [frontend/knowledge-base-client.js](frontend/knowledge-base-client.js)
- **Interactive Demo**: Open [frontend/demo.html](frontend/demo.html)
- **Python Examples**: See [backend/GlobalDeskClient.py](backend/GlobalDeskClient.py) and [backend/main.py](backend/main.py)

## 🧪 Testing

### Test Python Client

```bash
cd backend
python GlobalDeskClient.py
```

### Test JavaScript Client

Open `frontend/demo.html` in your web browser and configure your FreeScout URL.

## 🔐 Security

- Protected endpoints require FreeScout API key authentication
- Public endpoints only expose articles marked as "public" visibility
- Input sanitization prevents SQL injection
- CORS headers can be configured in module config
- Rate limiting support

## 🛠️ Configuration

Module configuration is in `Modules/KnowledgeBaseAPI/Config/config.php`:

```php
return [
    'enabled' => true,                     // Enable/disable API
    'rate_limit' => 60,                    // Requests per minute
    'require_auth' => true,                // Require auth for protected endpoints
    'cors_origins' => ['*'],               // CORS allowed origins
    'include_content_in_list' => false,    // Include full content in lists
];
```

## 📋 Requirements

### FreeScout Module
- PHP >= 7.4
- Laravel (included with FreeScout)
- FreeScout >= 1.8.0
- Knowledge Base feature enabled in FreeScout

### Python Client
- Python >= 3.7
- requests >= 2.31.0
- python-dotenv >= 1.0.0

### JavaScript Client
- Modern browser with ES6 support
- Fetch API support (or polyfill)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

MIT License

## 🐛 Troubleshooting

### "Table kb_categories doesn't exist"
Make sure you have a Knowledge Base module installed in FreeScout that creates these tables.

### "401 Unauthorized"
Include the API key in the request header for protected endpoints:
```
X-FreeScout-API-Key: your_api_key_here
```

### CORS Issues
Update `cors_origins` in the module's config.php file to include your domain.

### Module not showing in FreeScout
- Ensure the module is in the correct directory: `Modules/KnowledgeBaseAPI/`
- Check file permissions
- Clear FreeScout cache: `php artisan cache:clear`

## 📞 Support

- GitHub Issues: https://github.com/gabeparra/GlobalDeskModules/issues
- FreeScout Community: https://freescout.net/community/

## ✨ Examples

See the [frontend/demo.html](frontend/demo.html) file for a complete working example with:
- Interactive KB widget
- Category browsing
- Article viewing
- Search functionality
- API endpoint testing

## 🗂️ Project Structure

```
GlobalDeskModules/
├── Modules/
│   └── KnowledgeBaseAPI/          # FreeScout PHP module
│       ├── Config/
│       ├── Http/Controllers/
│       ├── Providers/
│       ├── Routes/
│       ├── composer.json
│       ├── module.json
│       └── README.md
├── backend/
│   ├── GlobalDeskClient.py        # Python client
│   ├── main.py                    # Python examples
│   └── requirements.txt
├── frontend/
│   ├── knowledge-base-client.js   # JavaScript client
│   └── demo.html                  # Interactive demo
└── README.md
```