import os
import requests
from typing import Optional, Dict, List
from dotenv import load_dotenv

# Load environment variables
load_dotenv()


class FreeScoutClient:
    """Client for interacting with the FreeScout API"""
    
    def __init__(self, base_url: Optional[str] = None, api_key: Optional[str] = None):
        """
        Initialize FreeScout client
        
        Args:
            base_url: FreeScout API base URL (e.g., https://your-instance.com/api)
            api_key: FreeScout API key
        """
        self.base_url = base_url or os.getenv('FREESCOUT_BASE_URL')
        self.api_key = api_key or os.getenv('FREESCOUT_API_KEY')
        
        if not self.base_url:
            raise ValueError("FreeScout base URL is required. Set FREESCOUT_BASE_URL env var or pass it directly.")
        if not self.api_key:
            raise ValueError("FreeScout API key is required. Set FREESCOUT_API_KEY env var or pass it directly.")
        
        # Ensure base_url doesn't end with /api if already present
        if self.base_url.endswith('/api'):
            self.base_url = self.base_url.rstrip('/api')
        
        self.headers = {
            'X-FreeScout-API-Key': self.api_key,
            'Accept': 'application/json',
            'Content-Type': 'application/json; charset=UTF-8',
        }
    
    def _make_request(self, method: str, endpoint: str, data: Optional[Dict] = None) -> Dict:
        """Make HTTP request to FreeScout API"""
        url = f"{self.base_url}/api{endpoint}"
        
        try:
            response = requests.request(
                method=method,
                url=url,
                headers=self.headers,
                json=data if data else None
            )
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            print(f"Error making request to {url}: {e}")
            if hasattr(e.response, 'text'):
                print(f"Response: {e.response.text}")
            raise
    
    # Customer operations
    def list_customers(self) -> List[Dict]:
        """List all customers"""
        response = self._make_request('GET', '/customers')
        return response.get('_embedded', {}).get('customers', [])
    
    def get_customer(self, customer_id: int) -> Dict:
        """Get a specific customer by ID"""
        return self._make_request('GET', f'/customers/{customer_id}')
    
    def create_customer(self, first_name: str, last_name: str, email: str, 
                       additional_data: Optional[Dict] = None) -> Dict:
        """Create a new customer"""
        data = {
            'firstName': first_name,
            'lastName': last_name,
            'emails': [{'value': email, 'type': 'work'}],
        }
        if additional_data:
            data.update(additional_data)
        return self._make_request('POST', '/customers', data)
    
    # Ticket operations
    def list_tickets(self, mailbox_id: Optional[int] = None, 
                    customer_id: Optional[int] = None) -> List[Dict]:
        """List tickets, optionally filtered by mailbox or customer"""
        endpoint = '/tickets'
        params = []
        if mailbox_id:
            params.append(f'mailboxId={mailbox_id}')
        if customer_id:
            params.append(f'customerId={customer_id}')
        
        if params:
            endpoint += '?' + '&'.join(params)
        
        response = self._make_request('GET', endpoint)
        return response.get('_embedded', {}).get('tickets', [])
    
    def get_ticket(self, ticket_id: int) -> Dict:
        """Get a specific ticket by ID"""
        return self._make_request('GET', f'/tickets/{ticket_id}')
    
    def create_ticket(self, subject: str, text: str, mailbox_id: int, 
                     customer_id: Optional[int] = None,
                     email: Optional[str] = None,
                     additional_data: Optional[Dict] = None) -> Dict:
        """Create a new ticket"""
        data = {
            'subject': subject,
            'text': text,
            'mailboxId': mailbox_id,
        }
        if customer_id:
            data['customerId'] = customer_id
        if email:
            data['email'] = email
        if additional_data:
            data.update(additional_data)
        
        return self._make_request('POST', '/tickets', data)
    
    # Mailbox operations
    def list_mailboxes(self) -> List[Dict]:
        """List all mailboxes"""
        response = self._make_request('GET', '/mailboxes')
        return response.get('_embedded', {}).get('mailboxes', [])
    
    def get_mailbox(self, mailbox_id: int) -> Dict:
        """Get a specific mailbox by ID"""
        return self._make_request('GET', f'/mailboxes/{mailbox_id}')
    
    # Conversation/Email operations
    def list_conversations(self, mailbox_id: Optional[int] = None,
                          customer_id: Optional[int] = None,
                          status: Optional[str] = None,
                          page: Optional[int] = None,
                          per_page: Optional[int] = None) -> Dict:
        """
        List conversations (tickets with email threads)
        
        Args:
            mailbox_id: Filter by mailbox ID
            customer_id: Filter by customer ID
            status: Filter by status (active, pending, closed, etc.)
            page: Page number for pagination
            per_page: Items per page
        
        Returns:
            Dictionary with conversations and pagination info
        """
        endpoint = '/conversations'
        params = []
        if mailbox_id:
            params.append(f'mailboxId={mailbox_id}')
        if customer_id:
            params.append(f'customerId={customer_id}')
        if status:
            params.append(f'status={status}')
        if page:
            params.append(f'page={page}')
        if per_page:
            params.append(f'per_page={per_page}')
        
        if params:
            endpoint += '?' + '&'.join(params)
        
        return self._make_request('GET', endpoint)
    
    def get_conversation(self, conversation_id: int) -> Dict:
        """
        Get a specific conversation with all its threads (emails/messages)
        
        Args:
            conversation_id: The conversation ID
        
        Returns:
            Dictionary containing conversation details and threads array
        """
        return self._make_request('GET', f'/conversations/{conversation_id}')
    
    def get_conversation_threads(self, conversation_id: int) -> List[Dict]:
        """
        Get all threads (emails) for a conversation
        
        Args:
            conversation_id: The conversation ID
        
        Returns:
            List of thread dictionaries (each thread is an email/message)
        """
        conversation = self.get_conversation(conversation_id)
        return conversation.get('threads', [])
    
    def list_all_conversations(self, max_pages: Optional[int] = None) -> List[Dict]:
        """
        Get all conversations across all pages
        
        Args:
            max_pages: Maximum number of pages to fetch (None = all)
        
        Returns:
            List of all conversations
        """
        all_conversations = []
        page = 1
        
        while True:
            if max_pages and page > max_pages:
                break
                
            response = self.list_conversations(page=page, per_page=50)
            conversations = response.get('_embedded', {}).get('conversations', [])
            
            if not conversations:
                break
                
            all_conversations.extend(conversations)
            
            # Check if there are more pages
            page_info = response.get('page', {})
            if page >= page_info.get('totalPages', 1):
                break
                
            page += 1
        
        return all_conversations
    
    def search_conversations(self, query: str, mailbox_id: Optional[int] = None) -> List[Dict]:
        """
        Search conversations by query string
        
        Args:
            query: Search query string
            mailbox_id: Optional mailbox ID to limit search
        
        Returns:
            List of matching conversations
        """
        endpoint = '/conversations'
        params = [f'search={query}']
        if mailbox_id:
            params.append(f'mailboxId={mailbox_id}')
        
        endpoint += '?' + '&'.join(params)
        response = self._make_request('GET', endpoint)
        return response.get('_embedded', {}).get('conversations', [])
    
    # Thread operations
    def get_thread(self, thread_id: int) -> Dict:
        """Get a specific thread (email/message) by ID"""
        return self._make_request('GET', f'/threads/{thread_id}')
    
    def create_thread(self, conversation_id: int, body: str, 
                     type: str = 'message',
                     additional_data: Optional[Dict] = None) -> Dict:
        """
        Create a new thread (reply) in a conversation
        
        Args:
            conversation_id: The conversation ID
            body: The message body/content
            type: Thread type (message, note, etc.)
            additional_data: Additional thread data
        """
        data = {
            'body': body,
            'type': type,
        }
        if additional_data:
            data.update(additional_data)
        
        return self._make_request('POST', f'/conversations/{conversation_id}/threads', data)
    
    # Knowledge Base operations (using KnowledgeBaseAPI module)
    def list_kb_categories(self, public: bool = True) -> List[Dict]:
        """
        List all knowledge base categories
        
        Args:
            public: Use public endpoint (no auth) if True, otherwise use authenticated endpoint
        
        Returns:
            List of category dictionaries with hierarchical structure
        """
        try:
            endpoint = '/kb/public/categories' if public else '/kb/categories'
            response = self._make_request('GET', endpoint)
            if response.get('success'):
                return response.get('data', [])
            return []
        except Exception as e:
            print(f"Note: Knowledge base categories endpoint may not be available: {e}")
            return []
    
    def get_kb_category(self, category_id: int, public: bool = True) -> Dict:
        """
        Get a specific knowledge base category with its articles
        
        Args:
            category_id: Category ID
            public: Use public endpoint (no auth) if True
        
        Returns:
            Dictionary with category and articles
        """
        endpoint = f'/kb/public/categories/{category_id}' if public else f'/kb/categories/{category_id}'
        response = self._make_request('GET', endpoint)
        if response.get('success'):
            return response.get('data', {})
        return {}
    
    def list_kb_articles(self, category_id: Optional[int] = None, 
                        page: int = 1, per_page: int = 20,
                        public: bool = True) -> Dict:
        """
        List knowledge base articles
        
        Args:
            category_id: Optional category ID to filter articles
            page: Page number for pagination
            per_page: Items per page
            public: Use public endpoint (no auth) if True
        
        Returns:
            Dictionary with articles array and pagination info
        """
        try:
            endpoint = '/kb/public/articles' if public else '/kb/articles'
            params = []
            if category_id:
                params.append(f'category_id={category_id}')
            params.append(f'page={page}')
            params.append(f'per_page={per_page}')
            
            if params:
                endpoint += '?' + '&'.join(params)
            
            response = self._make_request('GET', endpoint)
            if response.get('success'):
                return response
            return {'data': [], 'pagination': {}}
        except Exception as e:
            print(f"Note: Knowledge base articles endpoint may not be available: {e}")
            return {'data': [], 'pagination': {}}
    
    def get_kb_article(self, article_id: int, public: bool = True) -> Dict:
        """
        Get a specific knowledge base article
        
        Args:
            article_id: Article ID
            public: Use public endpoint (no auth) if True
        
        Returns:
            Dictionary with article and category information
        """
        endpoint = f'/kb/public/articles/{article_id}' if public else f'/kb/articles/{article_id}'
        response = self._make_request('GET', endpoint)
        if response.get('success'):
            return response.get('data', {})
        return {}
    
    def search_kb_articles(self, query: str, public: bool = True) -> List[Dict]:
        """
        Search knowledge base articles
        
        Args:
            query: Search query string
            public: Use public endpoint (no auth) if True
        
        Returns:
            List of matching articles
        """
        try:
            endpoint = f'/kb/public/search' if public else f'/kb/search'
            endpoint += f'?q={query}'
            response = self._make_request('GET', endpoint)
            if response.get('success'):
                return response.get('data', [])
            return []
        except Exception as e:
            print(f"Note: Knowledge base search may not be available: {e}")
            return []
    
    # Legacy KB methods (for backward compatibility)
    def list_kb_folders(self) -> List[Dict]:
        """List all knowledge base folders/categories (legacy method)"""
        return self.list_kb_categories(public=True)
    
    def get_kb_folder(self, folder_id: int) -> Dict:
        """Get a specific knowledge base folder (legacy method)"""
        return self.get_kb_category(folder_id, public=True)


# Example usage
if __name__ == '__main__':
    try:
        # Initialize client
        client = FreeScoutClient()
        
        print("Connected to FreeScout!\n")
        
        # List mailboxes
        print("Fetching mailboxes...")
        mailboxes = client.list_mailboxes()
        print(f"Found {len(mailboxes)} mailboxes:")
        for mb in mailboxes:
            print(f"  - {mb.get('name')} (ID: {mb.get('id')})")
        
        print("\n" + "="*50 + "\n")
        
        # List customers
        print("Fetching customers...")
        customers = client.list_customers()
        print(f"Found {len(customers)} customers:")
        for customer in customers[:5]:  # Show first 5
            name = f"{customer.get('firstName', '')} {customer.get('lastName', '')}".strip()
            print(f"  - {name} (ID: {customer.get('id')})")
        
        print("\n" + "="*60)
        print("FREESCOUT EMAIL & CONVERSATION DATA")
        print("="*60 + "\n")
        
        # List conversations (these contain the email threads)
        print("Fetching conversations...")
        conversations_data = client.list_conversations(per_page=10)
        conversations = conversations_data.get('_embedded', {}).get('conversations', [])
        print(f"Found {len(conversations)} conversations (showing first 10)\n")
        
        # Show details of first conversation including email threads
        if conversations:
            first_conv = conversations[0]
            conv_id = first_conv.get('id')
            
            print(f"Getting full details for conversation #{conv_id}...")
            full_conversation = client.get_conversation(conv_id)
            
            print(f"\nSubject: {full_conversation.get('subject')}")
            print(f"Status: {full_conversation.get('status')}")
            
            customer = full_conversation.get('customer', {})
            if customer:
                print(f"Customer: {customer.get('firstName')} {customer.get('lastName')} ({customer.get('email')})")
            
            # Get all threads (emails) in this conversation
            threads = full_conversation.get('threads', [])
            print(f"\nTotal emails/messages in this conversation: {len(threads)}\n")
            
            for i, thread in enumerate(threads, 1):
                print(f"--- Email/Message #{i} ---")
                print(f"Type: {thread.get('type')}")
                print(f"From: {thread.get('from', 'N/A')}")
                print(f"Date: {thread.get('createdAt')}")
                print(f"Body preview: {thread.get('body', '')[:200]}...")
                print()
        
        print("\n" + "="*60)
        print("FREESCOUT KNOWLEDGE BASE API (New Module)")
        print("="*60 + "\n")
        
        # Try to access knowledge base using new API module
        print("Fetching knowledge base categories (public API)...")
        categories = client.list_kb_categories(public=True)
        if categories:
            print(f"Found {len(categories)} knowledge base categories:")
            for category in categories[:5]:  # Show first 5
                print(f"  - {category.get('name')} (ID: {category.get('id')})")
                if category.get('children'):
                    for child in category.get('children', []):
                        print(f"    └─ {child.get('name')} (ID: {child.get('id')})")
        else:
            print("No categories found. Make sure the KnowledgeBaseAPI module is installed and activated.")
        
        print("\nFetching knowledge base articles (public API)...")
        articles_response = client.list_kb_articles(public=True, per_page=5)
        articles = articles_response.get('data', [])
        if articles:
            print(f"Found articles (showing first 5):")
            for article in articles:
                print(f"  - {article.get('title', 'Untitled')} (ID: {article.get('id')}, Views: {article.get('views', 0)})")
                if article.get('category'):
                    print(f"    Category: {article['category'].get('name')}")
        else:
            print("No articles found. Make sure the KnowledgeBaseAPI module is installed.")
        
        # Test search
        if articles:
            print("\nTesting search functionality...")
            search_results = client.search_kb_articles("help", public=True)
            if search_results:
                print(f"Found {len(search_results)} results for 'help':")
                for result in search_results[:3]:  # Show first 3
                    print(f"  - {result.get('title')}")
            else:
                print("No search results found.")
        
        # Get detailed article if available
        if articles and len(articles) > 0:
            first_article_id = articles[0].get('id')
            print(f"\nFetching full details for article ID {first_article_id}...")
            article_data = client.get_kb_article(first_article_id, public=True)
            if article_data and article_data.get('article'):
                article = article_data['article']
                print(f"Title: {article.get('title')}")
                print(f"Excerpt: {article.get('excerpt', 'No excerpt')[:100]}...")
                print(f"Views: {article.get('views', 0)}")
                if article_data.get('category'):
                    print(f"Category: {article_data['category'].get('name')}")

    except Exception as e:
        print(f"Error: {e}")