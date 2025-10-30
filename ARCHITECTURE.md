# Architecture Overview

This document explains how the Knowledge Base API module works and how all the components interact.

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         FreeScout                               │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │         KnowledgeBaseAPI Module (Laravel/PHP)            │  │
│  │                                                          │  │
│  │  ┌────────────────────────────────────────────────────┐ │  │
│  │  │            API Routes (api.php)                    │ │  │
│  │  │  • /api/kb/public/*  (No Auth)                    │ │  │
│  │  │  • /api/kb/*         (API Key Auth)               │ │  │
│  │  └────────────────────────────────────────────────────┘ │  │
│  │                          ↓                              │  │
│  │  ┌────────────────────────────────────────────────────┐ │  │
│  │  │      KnowledgeBaseAPIController.php                │ │  │
│  │  │  • listCategories()                                │ │  │
│  │  │  • getCategory()                                   │ │  │
│  │  │  • listArticles()                                  │ │  │
│  │  │  • getArticle()                                    │ │  │
│  │  │  • search()                                        │ │  │
│  │  └────────────────────────────────────────────────────┘ │  │
│  │                          ↓                              │  │
│  │  ┌────────────────────────────────────────────────────┐ │  │
│  │  │          Database (MySQL/MariaDB)                  │ │  │
│  │  │  • kb_categories table                             │ │  │
│  │  │  • kb_articles table                               │ │  │
│  │  └────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                  ↑
                                  │ HTTP/HTTPS
                                  │ (JSON)
                  ┌───────────────┴────────────────┐
                  │                                │
    ┌─────────────▼────────────┐   ┌──────────────▼─────────────┐
    │  JavaScript Client       │   │    Python Client           │
    │  (knowledge-base-        │   │    (GlobalDeskClient.py)   │
    │   client.js)             │   │                            │
    │                          │   │  • list_kb_categories()    │
    │  • FreeScoutKBClient     │   │  • list_kb_articles()      │
    │  • KnowledgeBaseWidget   │   │  • get_kb_article()        │
    │                          │   │  • search_kb_articles()    │
    │  Used by:                │   │                            │
    │  • Web Applications      │   │  Used by:                  │
    │  • Websites              │   │  • Backend Services        │
    │  • SPAs                  │   │  • Scripts                 │
    │  • Mobile Apps (WebView) │   │  • Automation              │
    └──────────────────────────┘   └────────────────────────────┘
```

## Data Flow

### 1. Public Article Retrieval (No Authentication)

```
┌──────────┐         ┌──────────┐         ┌──────────┐         ┌──────────┐
│ Browser  │────────▶│ JS Client│────────▶│  API     │────────▶│ Database │
│          │         │          │         │ Endpoint │         │          │
└──────────┘         └──────────┘         └──────────┘         └──────────┘
     │                    │                     │                     │
     │ User clicks        │ client.getArticles()│ GET /api/kb/public/│ SELECT * FROM
     │ "View Article"     │                     │ articles           │ kb_articles
     │                    │                     │                    │
     │                    │◀────JSON Response───│◀────Result Set─────│
     │◀────Render HTML────│                     │                    │
```

### 2. Authenticated Article Retrieval (With API Key)

```
┌──────────┐         ┌──────────┐         ┌──────────┐         ┌──────────┐
│ Python   │────────▶│  Client  │────────▶│   API    │────────▶│ Database │
│ Script   │         │          │         │ Endpoint │         │          │
└──────────┘         └──────────┘         └──────────┘         └──────────┘
     │                    │                     │                     │
     │ Script runs        │ list_kb_articles()  │ GET /api/kb/       │ SELECT * FROM
     │                    │ + API Key in header │ articles           │ kb_articles
     │                    │                     │ (Auth check)       │
     │                    │                     │                    │
     │                    │◀────JSON Response───│◀────Result Set─────│
     │◀────Process Data───│                     │                    │
```

### 3. Search Flow

```
User enters search query "password reset"
         │
         ▼
┌────────────────────────────────┐
│  JavaScript Widget             │
│  widget.showSearchResults()    │
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│  FreeScoutKBClient             │
│  client.search("password       │
│                reset")         │
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│  API Endpoint                  │
│  GET /api/kb/public/search     │
│  ?q=password+reset             │
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│  KnowledgeBaseAPIController    │
│  publicSearch()                │
│  - Validates query             │
│  - Searches title, content,    │
│    excerpt                     │
│  - Includes category info      │
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│  Database                      │
│  SELECT * FROM kb_articles     │
│  WHERE title LIKE '%password   │
│  reset%' OR content LIKE ...   │
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│  JSON Response                 │
│  {                             │
│    "success": true,            │
│    "data": [                   │
│      {                         │
│        "id": 5,                │
│        "title": "How to Reset  │
│                  Password",    │
│        "excerpt": "...",       │
│        "category": {...}       │
│      }                         │
│    ]                           │
│  }                             │
└────────────────┬───────────────┘
                 │
                 ▼
┌────────────────────────────────┐
│  Widget displays results       │
│  - Shows article titles        │
│  - Shows excerpts              │
│  - Shows categories            │
│  - Clickable to view full      │
└────────────────────────────────┘
```

## Database Schema

```sql
┌─────────────────────────────────────────────────────────┐
│                    kb_categories                        │
├──────────────┬──────────────────────────────────────────┤
│ Field        │ Type                                     │
├──────────────┼──────────────────────────────────────────┤
│ id           │ INT PRIMARY KEY AUTO_INCREMENT           │
│ name         │ VARCHAR(255) NOT NULL                    │
│ description  │ TEXT                                     │
│ slug         │ VARCHAR(255)                             │
│ parent_id    │ INT NULL (FK to kb_categories.id)        │
│ order        │ INT DEFAULT 0                            │
│ visibility   │ ENUM('public', 'private')                │
│ created_at   │ TIMESTAMP                                │
│ updated_at   │ TIMESTAMP                                │
└──────────────┴──────────────────────────────────────────┘
                         │
                         │ One-to-Many
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│                     kb_articles                         │
├──────────────┬──────────────────────────────────────────┤
│ Field        │ Type                                     │
├──────────────┼──────────────────────────────────────────┤
│ id           │ INT PRIMARY KEY AUTO_INCREMENT           │
│ category_id  │ INT NOT NULL (FK to kb_categories.id)    │
│ title        │ VARCHAR(255) NOT NULL                    │
│ slug         │ VARCHAR(255)                             │
│ content      │ TEXT                                     │
│ excerpt      │ TEXT                                     │
│ status       │ ENUM('draft', 'published')               │
│ visibility   │ ENUM('public', 'private')                │
│ order        │ INT DEFAULT 0                            │
│ views        │ INT DEFAULT 0                            │
│ created_at   │ TIMESTAMP                                │
│ updated_at   │ TIMESTAMP                                │
└──────────────┴──────────────────────────────────────────┘
```

## API Endpoints

### Public Endpoints (No Authentication)

| Method | Endpoint                          | Description                |
|--------|-----------------------------------|----------------------------|
| GET    | /api/kb/public/categories         | List all public categories |
| GET    | /api/kb/public/categories/{id}    | Get category with articles |
| GET    | /api/kb/public/articles           | List all public articles   |
| GET    | /api/kb/public/articles/{id}      | Get specific article       |
| GET    | /api/kb/public/search?q={query}   | Search public articles     |

### Protected Endpoints (API Key Required)

| Method | Endpoint                    | Description                |
|--------|-----------------------------|----------------------------|
| GET    | /api/kb/categories          | List all categories        |
| GET    | /api/kb/categories/{id}     | Get category with articles |
| GET    | /api/kb/articles            | List all articles          |
| GET    | /api/kb/articles/{id}       | Get specific article       |
| GET    | /api/kb/search?q={query}    | Search articles            |
| GET    | /api/kb/health              | Health check               |

## Security Model

```
┌────────────────────────────────────────────────────────────┐
│                    Request Security                        │
└────────────────────────────────────────────────────────────┘

PUBLIC ENDPOINTS (/api/kb/public/*)
├── No authentication required
├── Only shows articles with visibility='public'
├── Only shows categories with visibility='public'
└── CORS enabled (configurable)

PROTECTED ENDPOINTS (/api/kb/*)
├── Requires X-FreeScout-API-Key header
├── Shows all articles (public and private)
├── Shows all categories (public and private)
├── Rate limiting (configurable)
└── CORS enabled (configurable)

DATABASE QUERIES
├── SQL injection prevention via parameterized queries
├── Input sanitization
└── Status checks (only 'published' articles)
```

## Component Responsibilities

### 1. FreeScout Module (PHP)
- **Routes** (`Routes/api.php`): Define URL endpoints
- **Controller** (`Http/Controllers/KnowledgeBaseAPIController.php`): Handle requests
- **Service Provider** (`Providers/KnowledgeBaseAPIServiceProvider.php`): Register module
- **Config** (`Config/config.php`): Module settings

### 2. JavaScript Client
- **FreeScoutKBClient**: Low-level API wrapper
- **KnowledgeBaseWidget**: High-level UI component
- **Demo Page**: Interactive testing interface

### 3. Python Client
- **FreeScoutClient**: Main client class
- **KB Methods**: Wrapper methods for KB endpoints
- **Legacy Support**: Backward compatibility

## Integration Patterns

### Pattern 1: Embedded Widget
```html
<!-- Include the client library -->
<script src="knowledge-base-client.js"></script>

<!-- Create container -->
<div id="kb-widget"></div>

<!-- Initialize widget -->
<script>
    new KnowledgeBaseWidget('kb-widget', 'https://your-freescout.com');
</script>
```

### Pattern 2: Custom Integration
```javascript
const client = new FreeScoutKBClient('https://your-freescout.com');

// Custom search implementation
async function myCustomSearch() {
    const results = await client.search(document.getElementById('q').value);
    // Custom rendering logic
    displayResults(results);
}
```

### Pattern 3: Backend Integration
```python
from GlobalDeskClient import FreeScoutClient

client = FreeScoutClient()

# Sync KB articles to another system
articles = client.list_kb_articles(public=False)  # All articles
for article in articles.get('data', []):
    sync_to_external_system(article)
```

## Performance Considerations

1. **Caching**: Consider adding caching layer for frequently accessed articles
2. **Pagination**: Use pagination for large article lists
3. **Indexing**: Ensure database indexes on:
   - `kb_categories.id`
   - `kb_articles.category_id`
   - `kb_articles.status`
   - `kb_articles.visibility`
4. **Rate Limiting**: Configured in module config (default: 60 requests/minute)

## Extension Points

The module can be extended by:

1. **Adding Custom Routes**: Modify `Routes/api.php`
2. **Custom Controllers**: Add more controllers in `Http/Controllers/`
3. **Middleware**: Add custom middleware for additional security
4. **Event Hooks**: Use Laravel events for article views, searches, etc.
5. **Custom Responses**: Modify controller methods to include additional data

## Deployment Checklist

- [ ] Upload module to FreeScout's Modules directory
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Activate module in FreeScout admin panel
- [ ] Configure CORS settings if needed
- [ ] Test public endpoints (no auth)
- [ ] Test protected endpoints (with API key)
- [ ] Verify database tables exist (kb_categories, kb_articles)
- [ ] Test JavaScript client from your domain
- [ ] Monitor logs for errors
- [ ] Set up rate limiting if needed

## Troubleshooting Flow

```
Issue: API returns empty results
    ↓
Check 1: Are there articles in database?
    └─→ No  → Create test articles in FreeScout
    └─→ Yes → Continue
    ↓
Check 2: Are articles set to 'published' status?
    └─→ No  → Update articles to 'published'
    └─→ Yes → Continue
    ↓
Check 3: Using public endpoint for public articles?
    └─→ No  → Use /api/kb/public/* for public content
    └─→ Yes → Continue
    ↓
Check 4: Are articles set to 'public' visibility?
    └─→ No  → Update visibility or use protected endpoint
    └─→ Yes → Check logs for errors
```
