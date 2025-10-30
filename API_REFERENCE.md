# API Reference

Complete API reference for the FreeScout Knowledge Base API module.

## Base URL

All API endpoints are relative to your FreeScout installation:
```
https://your-freescout.com/api/kb
```

## Authentication

### Public Endpoints
Public endpoints do not require authentication:
```
GET /api/kb/public/*
```

### Protected Endpoints
Protected endpoints require the FreeScout API key in the request header:
```
X-FreeScout-API-Key: your_api_key_here
```

Example with curl:
```bash
curl -H "X-FreeScout-API-Key: YOUR_KEY" \
     https://your-freescout.com/api/kb/articles
```

## Response Format

All endpoints return JSON with a consistent structure:

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

---

## Endpoints

### 1. Health Check

Check if the API is operational.

**Endpoint:** `GET /api/kb/health`

**Authentication:** None required

**Response:**
```json
{
    "status": "ok",
    "module": "KnowledgeBaseAPI",
    "version": "1.0.0"
}
```

**Example:**
```bash
curl https://your-freescout.com/api/kb/health
```

---

### 2. List Categories

Get all knowledge base categories with hierarchical structure.

**Endpoint:** 
- Public: `GET /api/kb/public/categories`
- Protected: `GET /api/kb/categories`

**Authentication:** 
- Public: None
- Protected: API key required

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Getting Started",
            "description": "New user guides",
            "slug": "getting-started",
            "parent_id": null,
            "order": 1,
            "created_at": "2025-01-01 10:00:00",
            "updated_at": "2025-01-15 14:30:00",
            "children": [
                {
                    "id": 3,
                    "name": "First Steps",
                    "description": "Your first steps",
                    "slug": "first-steps",
                    "parent_id": 1,
                    "order": 1,
                    "created_at": "2025-01-02 10:00:00",
                    "updated_at": "2025-01-16 14:30:00",
                    "children": []
                }
            ]
        }
    ],
    "count": 1
}
```

**Example:**
```bash
# Public
curl https://your-freescout.com/api/kb/public/categories

# Protected
curl -H "X-FreeScout-API-Key: YOUR_KEY" \
     https://your-freescout.com/api/kb/categories
```

---

### 3. Get Category

Get a specific category with its articles.

**Endpoint:**
- Public: `GET /api/kb/public/categories/{id}`
- Protected: `GET /api/kb/categories/{id}`

**Authentication:**
- Public: None
- Protected: API key required

**Parameters:**
- `id` (integer, required): Category ID

**Response:**
```json
{
    "success": true,
    "data": {
        "category": {
            "id": 1,
            "name": "Getting Started",
            "description": "New user guides",
            "slug": "getting-started",
            "parent_id": null,
            "order": 1,
            "created_at": "2025-01-01 10:00:00",
            "updated_at": "2025-01-15 14:30:00"
        },
        "articles": [
            {
                "id": 1,
                "title": "Welcome to Our Platform",
                "slug": "welcome",
                "excerpt": "Learn the basics...",
                "views": 150,
                "created_at": "2025-01-01 10:00:00",
                "updated_at": "2025-01-15 14:30:00"
            }
        ],
        "article_count": 1
    }
}
```

**Example:**
```bash
# Public
curl https://your-freescout.com/api/kb/public/categories/1

# Protected
curl -H "X-FreeScout-API-Key: YOUR_KEY" \
     https://your-freescout.com/api/kb/categories/1
```

---

### 4. List Articles

Get all knowledge base articles with pagination.

**Endpoint:**
- Public: `GET /api/kb/public/articles`
- Protected: `GET /api/kb/articles`

**Authentication:**
- Public: None
- Protected: API key required

**Query Parameters:**
- `category_id` (integer, optional): Filter by category ID
- `page` (integer, optional): Page number (default: 1)
- `per_page` (integer, optional): Items per page (default: 20)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "category_id": 1,
            "title": "Welcome to Our Platform",
            "slug": "welcome",
            "excerpt": "Learn the basics of our platform...",
            "views": 150,
            "created_at": "2025-01-01 10:00:00",
            "updated_at": "2025-01-15 14:30:00",
            "category": {
                "id": 1,
                "name": "Getting Started",
                "description": "New user guides",
                "slug": "getting-started"
            }
        }
    ],
    "pagination": {
        "total": 45,
        "per_page": 20,
        "current_page": 1,
        "total_pages": 3
    }
}
```

**Examples:**
```bash
# Public - all articles
curl https://your-freescout.com/api/kb/public/articles

# Public - articles in category 1
curl https://your-freescout.com/api/kb/public/articles?category_id=1

# Public - page 2 with 10 items per page
curl "https://your-freescout.com/api/kb/public/articles?page=2&per_page=10"

# Protected
curl -H "X-FreeScout-API-Key: YOUR_KEY" \
     https://your-freescout.com/api/kb/articles
```

---

### 5. Get Article

Get a specific article with full content.

**Endpoint:**
- Public: `GET /api/kb/public/articles/{id}`
- Protected: `GET /api/kb/articles/{id}`

**Authentication:**
- Public: None
- Protected: API key required

**Parameters:**
- `id` (integer, required): Article ID

**Response:**
```json
{
    "success": true,
    "data": {
        "article": {
            "id": 1,
            "category_id": 1,
            "title": "Welcome to Our Platform",
            "slug": "welcome",
            "content": "<h1>Welcome!</h1><p>This is the full article content...</p>",
            "excerpt": "Learn the basics of our platform...",
            "status": "published",
            "visibility": "public",
            "order": 1,
            "views": 151,
            "created_at": "2025-01-01 10:00:00",
            "updated_at": "2025-01-15 14:30:00"
        },
        "category": {
            "id": 1,
            "name": "Getting Started",
            "description": "New user guides",
            "slug": "getting-started"
        }
    }
}
```

**Note:** Requesting an article automatically increments its view count.

**Examples:**
```bash
# Public
curl https://your-freescout.com/api/kb/public/articles/1

# Protected
curl -H "X-FreeScout-API-Key: YOUR_KEY" \
     https://your-freescout.com/api/kb/articles/1
```

---

### 6. Search Articles

Search for articles by query string.

**Endpoint:**
- Public: `GET /api/kb/public/search`
- Protected: `GET /api/kb/search`

**Authentication:**
- Public: None
- Protected: API key required

**Query Parameters:**
- `q` (string, required): Search query

**Search Fields:**
The search looks for the query in:
- Article title
- Article content
- Article excerpt

**Response:**
```json
{
    "success": true,
    "query": "password reset",
    "data": [
        {
            "id": 5,
            "category_id": 2,
            "title": "How to Reset Your Password",
            "slug": "reset-password",
            "excerpt": "Follow these steps to reset your password...",
            "views": 450,
            "created_at": "2025-01-03 10:00:00",
            "updated_at": "2025-01-20 14:30:00",
            "category": {
                "id": 2,
                "name": "Account Management",
                "description": "Managing your account",
                "slug": "account-management"
            }
        }
    ],
    "count": 1
}
```

**Examples:**
```bash
# Public
curl "https://your-freescout.com/api/kb/public/search?q=password"

# Protected
curl -H "X-FreeScout-API-Key: YOUR_KEY" \
     "https://your-freescout.com/api/kb/search?q=password%20reset"
```

---

## JavaScript Client Reference

### FreeScoutKBClient Class

#### Constructor
```javascript
new FreeScoutKBClient(baseUrl, apiKey)
```

**Parameters:**
- `baseUrl` (string, required): FreeScout installation URL
- `apiKey` (string, optional): API key for protected endpoints

**Example:**
```javascript
// Public endpoints only
const client = new FreeScoutKBClient('https://your-freescout.com');

// With API key for protected endpoints
const client = new FreeScoutKBClient('https://your-freescout.com', 'YOUR_API_KEY');
```

#### Methods

##### getCategories()
```javascript
async getCategories()
```

Returns all public categories with hierarchical structure.

**Returns:** `Promise<Array>`

**Example:**
```javascript
const categories = await client.getCategories();
console.log(categories);
```

##### getCategory(categoryId)
```javascript
async getCategory(categoryId)
```

Get a specific category with its articles.

**Parameters:**
- `categoryId` (number): Category ID

**Returns:** `Promise<Object>`

**Example:**
```javascript
const data = await client.getCategory(1);
console.log(data.category);
console.log(data.articles);
```

##### getArticles(options)
```javascript
async getArticles(options)
```

Get articles with pagination and filtering.

**Parameters:**
- `options` (object, optional):
  - `categoryId` (number): Filter by category
  - `page` (number): Page number
  - `perPage` (number): Items per page

**Returns:** `Promise<Object>`

**Example:**
```javascript
const result = await client.getArticles({
    categoryId: 1,
    page: 1,
    perPage: 10
});
console.log(result.data);
console.log(result.pagination);
```

##### getArticle(articleId)
```javascript
async getArticle(articleId)
```

Get a specific article with full content.

**Parameters:**
- `articleId` (number): Article ID

**Returns:** `Promise<Object>`

**Example:**
```javascript
const data = await client.getArticle(5);
console.log(data.article);
console.log(data.category);
```

##### search(query)
```javascript
async search(query)
```

Search articles by query string.

**Parameters:**
- `query` (string): Search query

**Returns:** `Promise<Array>`

**Example:**
```javascript
const results = await client.search('password reset');
results.forEach(article => {
    console.log(article.title);
});
```

##### healthCheck()
```javascript
async healthCheck()
```

Check API health status.

**Returns:** `Promise<Object>`

**Example:**
```javascript
const health = await client.healthCheck();
console.log(health.status); // "ok"
```

### KnowledgeBaseWidget Class

#### Constructor
```javascript
new KnowledgeBaseWidget(containerId, freescoutUrl)
```

**Parameters:**
- `containerId` (string): ID of the HTML container element
- `freescoutUrl` (string): FreeScout installation URL

**Example:**
```javascript
const widget = new KnowledgeBaseWidget('kb-container', 'https://your-freescout.com');
```

The widget automatically:
- Creates a search interface
- Shows browseable categories
- Displays articles
- Handles navigation

---

## Python Client Reference

### FreeScoutClient Class

#### Constructor
```python
FreeScoutClient(base_url=None, api_key=None)
```

**Parameters:**
- `base_url` (str, optional): FreeScout URL (or from FREESCOUT_BASE_URL env)
- `api_key` (str, optional): API key (or from FREESCOUT_API_KEY env)

**Example:**
```python
from GlobalDeskClient import FreeScoutClient

# From environment variables
client = FreeScoutClient()

# With explicit parameters
client = FreeScoutClient(
    base_url='https://your-freescout.com',
    api_key='YOUR_API_KEY'
)
```

#### Methods

##### list_kb_categories(public=True)
```python
list_kb_categories(public=True) -> List[Dict]
```

Get all categories.

**Parameters:**
- `public` (bool): Use public endpoint if True

**Returns:** List of category dictionaries

**Example:**
```python
categories = client.list_kb_categories(public=True)
for cat in categories:
    print(f"{cat['name']} (ID: {cat['id']})")
```

##### get_kb_category(category_id, public=True)
```python
get_kb_category(category_id, public=True) -> Dict
```

Get a specific category with articles.

**Parameters:**
- `category_id` (int): Category ID
- `public` (bool): Use public endpoint if True

**Returns:** Dictionary with category and articles

**Example:**
```python
data = client.get_kb_category(1, public=True)
print(data['category']['name'])
for article in data['articles']:
    print(f"  - {article['title']}")
```

##### list_kb_articles(category_id=None, page=1, per_page=20, public=True)
```python
list_kb_articles(category_id=None, page=1, per_page=20, public=True) -> Dict
```

Get articles with pagination.

**Parameters:**
- `category_id` (int, optional): Filter by category
- `page` (int): Page number
- `per_page` (int): Items per page
- `public` (bool): Use public endpoint if True

**Returns:** Dictionary with data and pagination info

**Example:**
```python
result = client.list_kb_articles(category_id=1, per_page=10, public=True)
for article in result['data']:
    print(article['title'])
print(f"Page {result['pagination']['current_page']} of {result['pagination']['total_pages']}")
```

##### get_kb_article(article_id, public=True)
```python
get_kb_article(article_id, public=True) -> Dict
```

Get a specific article.

**Parameters:**
- `article_id` (int): Article ID
- `public` (bool): Use public endpoint if True

**Returns:** Dictionary with article and category

**Example:**
```python
data = client.get_kb_article(5, public=True)
print(data['article']['title'])
print(data['article']['content'])
```

##### search_kb_articles(query, public=True)
```python
search_kb_articles(query, public=True) -> List[Dict]
```

Search articles.

**Parameters:**
- `query` (str): Search query
- `public` (bool): Use public endpoint if True

**Returns:** List of matching articles

**Example:**
```python
results = client.search_kb_articles("password", public=True)
for article in results:
    print(f"{article['title']} - {article['views']} views")
```

---

## Error Codes

| HTTP Status | Description |
|-------------|-------------|
| 200 | Success |
| 400 | Bad Request (e.g., missing search query) |
| 401 | Unauthorized (invalid API key) |
| 404 | Not Found (category or article doesn't exist) |
| 429 | Too Many Requests (rate limit exceeded) |
| 500 | Internal Server Error |

---

## Rate Limiting

Default rate limit: 60 requests per minute

To configure, edit `Config/config.php`:
```php
'rate_limit' => 100,  // 100 requests per minute
```

---

## CORS Configuration

To configure allowed origins, edit `Config/config.php`:
```php
'cors_origins' => [
    'https://yourdomain.com',
    'https://app.yourdomain.com'
],

// Or allow all origins (development only):
'cors_origins' => ['*'],
```

---

## Examples

See the following files for complete examples:
- JavaScript: `frontend/demo.html`
- Python: `backend/main.py`
- Integration: `ARCHITECTURE.md`
