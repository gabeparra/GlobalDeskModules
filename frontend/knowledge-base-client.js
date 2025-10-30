/**
 * FreeScout Knowledge Base API - JavaScript Client
 * 
 * This file demonstrates how to interact with the FreeScout Knowledge Base API
 * from JavaScript/frontend applications.
 */

// Helper function to escape HTML and prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

class FreeScoutKBClient {
    /**
     * Initialize the Knowledge Base API client
     * @param {string} baseUrl - Base URL of your FreeScout installation (e.g., 'https://support.example.com')
     * @param {string} apiKey - Optional API key for authenticated endpoints
     */
    constructor(baseUrl, apiKey = null) {
        this.baseUrl = baseUrl.replace(/\/$/, ''); // Remove trailing slash
        this.apiKey = apiKey;
        this.publicEndpoint = '/api/kb/public';
        this.protectedEndpoint = '/api/kb';
    }

    /**
     * Make HTTP request to the API
     * @private
     */
    async _request(endpoint, useAuth = false) {
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };

        if (useAuth && this.apiKey) {
            headers['X-FreeScout-API-Key'] = this.apiKey;
        }

        const response = await fetch(this.baseUrl + endpoint, {
            method: 'GET',
            headers: headers
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Get all public categories
     * @returns {Promise<Array>} Array of categories with hierarchical structure
     */
    async getCategories() {
        const response = await this._request(`${this.publicEndpoint}/categories`);
        return response.data;
    }

    /**
     * Get a specific category with its articles
     * @param {number} categoryId - Category ID
     * @returns {Promise<Object>} Category object with articles array
     */
    async getCategory(categoryId) {
        const response = await this._request(`${this.publicEndpoint}/categories/${categoryId}`);
        return response.data;
    }

    /**
     * Get all public articles
     * @param {Object} options - Query options
     * @param {number} options.categoryId - Filter by category ID
     * @param {number} options.page - Page number (default: 1)
     * @param {number} options.perPage - Items per page (default: 20)
     * @returns {Promise<Object>} Object with articles array and pagination info
     */
    async getArticles(options = {}) {
        const params = new URLSearchParams();
        if (options.categoryId) params.append('category_id', options.categoryId);
        if (options.page) params.append('page', options.page);
        if (options.perPage) params.append('per_page', options.perPage);

        const queryString = params.toString();
        const endpoint = `${this.publicEndpoint}/articles${queryString ? '?' + queryString : ''}`;
        
        return await this._request(endpoint);
    }

    /**
     * Get a specific article by ID
     * @param {number} articleId - Article ID
     * @returns {Promise<Object>} Article object with full content
     */
    async getArticle(articleId) {
        const response = await this._request(`${this.publicEndpoint}/articles/${articleId}`);
        return response.data;
    }

    /**
     * Search articles
     * @param {string} query - Search query
     * @returns {Promise<Array>} Array of matching articles
     */
    async search(query) {
        const endpoint = `${this.publicEndpoint}/search?q=${encodeURIComponent(query)}`;
        const response = await this._request(endpoint);
        return response.data;
    }

    /**
     * Check API health
     * @returns {Promise<Object>} Health status
     */
    async healthCheck() {
        const response = await this._request(`${this.protectedEndpoint}/health`);
        return response;
    }
}

// ==================== USAGE EXAMPLES ====================

/**
 * Example 1: Display all KB categories in a list
 */
async function displayCategories() {
    const client = new FreeScoutKBClient('https://your-freescout.com');
    
    try {
        const categories = await client.getCategories();
        const listElement = document.getElementById('kb-categories');
        
        categories.forEach(category => {
            const li = document.createElement('li');
            li.innerHTML = `
                <strong>${category.name}</strong>
                <p>${category.description || ''}</p>
                <button onclick="loadCategoryArticles(${category.id})">View Articles</button>
            `;
            listElement.appendChild(li);
        });
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

/**
 * Example 2: Load articles for a specific category
 */
async function loadCategoryArticles(categoryId) {
    const client = new FreeScoutKBClient('https://your-freescout.com');
    
    try {
        const data = await client.getCategory(categoryId);
        const articles = data.articles;
        const container = document.getElementById('articles-container');
        
        container.innerHTML = `<h2>${data.category.name}</h2>`;
        
        articles.forEach(article => {
            const articleDiv = document.createElement('div');
            articleDiv.className = 'article-item';
            articleDiv.innerHTML = `
                <h3>${article.title}</h3>
                <p>${article.excerpt || ''}</p>
                <small>Views: ${article.views}</small>
                <button onclick="showArticle(${article.id})">Read More</button>
            `;
            container.appendChild(articleDiv);
        });
    } catch (error) {
        console.error('Error loading articles:', error);
    }
}

/**
 * Example 3: Display full article content
 */
async function showArticle(articleId) {
    const client = new FreeScoutKBClient('https://your-freescout.com');
    
    try {
        const data = await client.getArticle(articleId);
        const article = data.article;
        const category = data.category;
        
        const container = document.getElementById('article-content');
        container.innerHTML = `
            <div class="breadcrumb">
                <a href="#" onclick="loadCategoryArticles(${category.id})">${category.name}</a>
                &gt; ${article.title}
            </div>
            <h1>${article.title}</h1>
            <div class="article-meta">
                <span>Views: ${article.views}</span>
                <span>Updated: ${new Date(article.updated_at).toLocaleDateString()}</span>
            </div>
            <div class="article-body">
                ${article.content}
            </div>
        `;
    } catch (error) {
        console.error('Error loading article:', error);
    }
}

/**
 * Example 4: Search functionality
 */
async function searchKnowledgeBase(searchQuery) {
    const client = new FreeScoutKBClient('https://your-freescout.com');
    
    try {
        const results = await client.search(searchQuery);
        const resultsContainer = document.getElementById('search-results');
        
        resultsContainer.innerHTML = `<h2>Search Results for "${searchQuery}"</h2>`;
        
        if (results.length === 0) {
            resultsContainer.innerHTML += '<p>No results found.</p>';
            return;
        }
        
        results.forEach(article => {
            const resultDiv = document.createElement('div');
            resultDiv.className = 'search-result';
            resultDiv.innerHTML = `
                <h3><a href="#" onclick="showArticle(${article.id})">${article.title}</a></h3>
                <p>${article.excerpt || ''}</p>
                <small>Category: ${article.category ? article.category.name : 'N/A'}</small>
            `;
            resultsContainer.appendChild(resultDiv);
        });
    } catch (error) {
        console.error('Error searching:', error);
    }
}

/**
 * Example 5: Build a complete KB widget
 */
class KnowledgeBaseWidget {
    constructor(containerId, freescoutUrl) {
        this.container = document.getElementById(containerId);
        this.client = new FreeScoutKBClient(freescoutUrl);
        this.init();
    }

    async init() {
        this.container.innerHTML = `
            <div class="kb-widget">
                <div class="kb-search">
                    <input type="text" id="kb-search-input" placeholder="Search knowledge base...">
                    <button id="kb-search-btn">Search</button>
                </div>
                <div id="kb-content"></div>
            </div>
        `;

        document.getElementById('kb-search-btn').addEventListener('click', () => {
            const query = document.getElementById('kb-search-input').value;
            if (query) {
                this.showSearchResults(query);
            }
        });

        // Show categories by default
        await this.showCategories();
    }

    async showCategories() {
        try {
            const categories = await this.client.getCategories();
            const content = document.getElementById('kb-content');
            
            content.innerHTML = '<h3>Browse by Category</h3>';
            const list = document.createElement('ul');
            list.className = 'kb-category-list';
            
            categories.forEach(category => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <a href="#" data-category-id="${category.id}">
                        ${category.name} 
                        ${category.children && category.children.length > 0 ? 
                            `(${category.children.length} subcategories)` : ''}
                    </a>
                `;
                li.querySelector('a').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showCategory(category.id);
                });
                list.appendChild(li);
            });
            
            content.appendChild(list);
        } catch (error) {
            console.error('Error showing categories:', error);
            document.getElementById('kb-content').innerHTML = 
                '<p class="error">Error loading categories. Please try again.</p>';
        }
    }

    async showCategory(categoryId) {
        try {
            const data = await this.client.getCategory(categoryId);
            const content = document.getElementById('kb-content');
            
            content.innerHTML = `
                <button id="kb-back-btn">← Back to Categories</button>
                <h3>${data.category.name}</h3>
                <p>${data.category.description || ''}</p>
            `;

            document.getElementById('kb-back-btn').addEventListener('click', () => {
                this.showCategories();
            });
            
            if (data.articles.length === 0) {
                content.innerHTML += '<p>No articles in this category yet.</p>';
                return;
            }
            
            const list = document.createElement('ul');
            list.className = 'kb-article-list';
            
            data.articles.forEach(article => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <a href="#" data-article-id="${article.id}">
                        ${article.title}
                    </a>
                    <small> (${article.views} views)</small>
                `;
                li.querySelector('a').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showArticle(article.id);
                });
                list.appendChild(li);
            });
            
            content.appendChild(list);
        } catch (error) {
            console.error('Error showing category:', error);
        }
    }

    async showArticle(articleId) {
        try {
            const data = await this.client.getArticle(articleId);
            const article = data.article;
            const category = data.category;
            
            const content = document.getElementById('kb-content');
            content.innerHTML = `
                <button id="kb-back-btn">← Back to ${category.name}</button>
                <article class="kb-article">
                    <h2>${article.title}</h2>
                    <div class="article-meta">
                        <span>Updated: ${new Date(article.updated_at).toLocaleDateString()}</span>
                        <span>Views: ${article.views}</span>
                    </div>
                    <div class="article-content">
                        ${article.content}
                    </div>
                </article>
            `;

            document.getElementById('kb-back-btn').addEventListener('click', () => {
                this.showCategory(category.id);
            });
        } catch (error) {
            console.error('Error showing article:', error);
        }
    }

    async showSearchResults(query) {
        try {
            const results = await this.client.search(query);
            const content = document.getElementById('kb-content');
            
            content.innerHTML = `
                <button id="kb-back-btn">← Back to Categories</button>
                <h3>Search Results for "${escapeHtml(query)}"</h3>
            `;

            document.getElementById('kb-back-btn').addEventListener('click', () => {
                this.showCategories();
            });
            
            if (results.length === 0) {
                content.innerHTML += '<p>No results found.</p>';
                return;
            }
            
            const list = document.createElement('ul');
            list.className = 'kb-search-results';
            
            results.forEach(article => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <a href="#" data-article-id="${article.id}">
                        <strong>${article.title}</strong>
                    </a>
                    <p>${article.excerpt || ''}</p>
                    <small>Category: ${article.category ? article.category.name : 'N/A'}</small>
                `;
                li.querySelector('a').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showArticle(article.id);
                });
                list.appendChild(li);
            });
            
            content.appendChild(list);
        } catch (error) {
            console.error('Error showing search results:', error);
        }
    }
}

// ==================== INITIALIZATION ====================

/**
 * Initialize the Knowledge Base widget when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Example: Initialize the widget
    // Uncomment and update with your FreeScout URL
    // const kbWidget = new KnowledgeBaseWidget('kb-container', 'https://your-freescout.com');
});

// Export for use as module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FreeScoutKBClient, KnowledgeBaseWidget };
}
