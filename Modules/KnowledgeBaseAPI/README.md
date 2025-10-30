# Knowledge Base API Module for FreeScout

This module exposes FreeScout's Knowledge Base through REST API endpoints, allowing external applications and JavaScript functions to access knowledge base articles and categories.

## Features

- ✅ RESTful API endpoints for knowledge base access
- ✅ List all categories (with hierarchical structure)
- ✅ Get category details with articles
- ✅ List and filter articles
- ✅ Get full article content
- ✅ Search functionality
- ✅ Public API endpoints (no authentication required)
- ✅ Protected API endpoints (requires authentication)
- ✅ Article view tracking
- ✅ Pagination support

## Installation

1. Copy the `KnowledgeBaseAPI` module folder to your FreeScout installation's `Modules` directory:
   ```bash
   cp -r Modules/KnowledgeBaseAPI /path/to/freescout/Modules/
   ```

2. Enable the module in FreeScout admin panel:
   - Go to **Manage → Modules**
   - Find "KnowledgeBaseAPI" module
   - Click **Activate**

3. The API endpoints will be immediately available

## API Endpoints

### Authenticated Endpoints

These endpoints require FreeScout API authentication (X-FreeScout-API-Key header):

#### Categories
- `GET /api/kb/categories` - List all categories
- `GET /api/kb/categories/{id}` - Get category details with articles

#### Articles
- `GET /api/kb/articles` - List all articles
  - Query params: `category_id`, `page`, `per_page`
- `GET /api/kb/articles/{id}` - Get article details

#### Search
- `GET /api/kb/search?q={query}` - Search articles

#### Health Check
- `GET /api/kb/health` - Check if API is working

### Public Endpoints

These endpoints don't require authentication:

- `GET /api/kb/public/categories` - List public categories
- `GET /api/kb/public/categories/{id}` - Get public category
- `GET /api/kb/public/articles` - List public articles
- `GET /api/kb/public/articles/{id}` - Get public article
- `GET /api/kb/public/search?q={query}` - Search public articles

## Usage Examples

### JavaScript Fetch API

```javascript
// Get all categories
async function getKBCategories() {
    const response = await fetch('https://your-freescout.com/api/kb/public/categories', {
        headers: {
            'Accept': 'application/json'
        }
    });
    const data = await response.json();
    return data.data;
}

// Get articles from a category
async function getCategoryArticles(categoryId) {
    const response = await fetch(`https://your-freescout.com/api/kb/public/categories/${categoryId}`, {
        headers: {
            'Accept': 'application/json'
        }
    });
    const data = await response.json();
    return data.data.articles;
}

// Get full article
async function getArticle(articleId) {
    const response = await fetch(`https://your-freescout.com/api/kb/public/articles/${articleId}`, {
        headers: {
            'Accept': 'application/json'
        }
    });
    const data = await response.json();
    return data.data.article;
}

// Search articles
async function searchKB(query) {
    const response = await fetch(`https://your-freescout.com/api/kb/public/search?q=${encodeURIComponent(query)}`, {
        headers: {
            'Accept': 'application/json'
        }
    });
    const data = await response.json();
    return data.data;
}
```

### Python Client

```python
from GlobalDeskClient import FreeScoutClient

client = FreeScoutClient()

# Get categories
categories = client._make_request('GET', '/kb/categories')

# Get articles
articles = client._make_request('GET', '/kb/articles')

# Search
results = client._make_request('GET', '/kb/search?q=password')

# Get specific article
article = client._make_request('GET', '/kb/articles/1')
```

### jQuery Example

```javascript
// Display knowledge base categories and articles
$.ajax({
    url: 'https://your-freescout.com/api/kb/public/categories',
    method: 'GET',
    dataType: 'json',
    success: function(response) {
        const categories = response.data;
        categories.forEach(function(category) {
            console.log(category.name);
            // Load articles for this category
            loadCategoryArticles(category.id);
        });
    }
});

function loadCategoryArticles(categoryId) {
    $.ajax({
        url: `https://your-freescout.com/api/kb/public/categories/${categoryId}`,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            const articles = response.data.articles;
            articles.forEach(function(article) {
                console.log('  -', article.title);
            });
        }
    });
}
```

## Response Format

All endpoints return JSON in this format:

### Success Response
```json
{
    "success": true,
    "data": { /* response data */ }
}
```

### Error Response
```json
{
    "success": false,
    "error": "Error message",
    "message": "Detailed error description"
}
```

### Article Object
```json
{
    "id": 1,
    "category_id": 2,
    "title": "How to reset password",
    "slug": "how-to-reset-password",
    "content": "Full article content...",
    "excerpt": "Brief description...",
    "views": 150,
    "status": "published",
    "visibility": "public",
    "created_at": "2025-01-01 10:00:00",
    "updated_at": "2025-01-15 14:30:00"
}
```

### Category Object
```json
{
    "id": 2,
    "name": "Account Management",
    "description": "Articles about managing your account",
    "slug": "account-management",
    "parent_id": null,
    "order": 1,
    "visibility": "public",
    "children": []
}
```

## Configuration

Edit `Config/config.php` to customize:

```php
'enabled' => true,              // Enable/disable API
'rate_limit' => 60,             // Requests per minute
'require_auth' => true,         // Require auth for protected endpoints
'cors_origins' => ['*'],        // CORS allowed origins
'include_content_in_list' => false,  // Include full content in list responses
```

## Database Schema

The module expects these tables to exist in FreeScout:

### kb_categories
- id
- name
- description
- slug
- parent_id
- order
- visibility (public/private)
- created_at
- updated_at

### kb_articles
- id
- category_id
- title
- slug
- content
- excerpt
- status (draft/published)
- visibility (public/private)
- order
- views
- created_at
- updated_at

## Security

- Protected endpoints require FreeScout API key authentication
- Public endpoints only expose articles marked as "public"
- Rate limiting prevents API abuse
- Input sanitization prevents SQL injection
- CORS headers can be configured

## Troubleshooting

### "Table kb_categories doesn't exist"
Make sure you have a Knowledge Base module installed in FreeScout that creates these tables.

### "401 Unauthorized"
For protected endpoints, include the API key in the request header:
```
X-FreeScout-API-Key: your_api_key_here
```

### CORS Issues
Update `cors_origins` in config.php to include your domain, or use '*' for all domains during development.

## License

MIT License

## Support

For issues and questions:
- GitHub Issues: https://github.com/gabeparra/GlobalDeskModules/issues
- FreeScout Community: https://freescout.net/community/
