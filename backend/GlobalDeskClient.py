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
    
    # Knowledge Base operations
    def list_kb_folders(self) -> List[Dict]:
        """List all knowledge base folders/categories"""
        try:
            response = self._make_request('GET', '/folders')
            return response.get('_embedded', {}).get('folders', [])
        except Exception as e:
            # Knowledge base may not be available via standard API
            print(f"Note: Knowledge base folders endpoint may not be available: {e}")
            return []
    
    def get_kb_folder(self, folder_id: int) -> Dict:
        """Get a specific knowledge base folder"""
        return self._make_request('GET', f'/folders/{folder_id}')
    
    def list_kb_articles(self, folder_id: Optional[int] = None) -> List[Dict]:
        """
        List knowledge base articles
        
        Args:
            folder_id: Optional folder ID to filter articles
        """
        try:
            endpoint = '/articles'
            if folder_id:
                endpoint += f'?folderId={folder_id}'
            
            response = self._make_request('GET', endpoint)
            return response.get('_embedded', {}).get('articles', [])
        except Exception as e:
            # Knowledge base may require a module or have different endpoints
            print(f"Note: Knowledge base articles endpoint may not be available: {e}")
            return []
    
    def get_kb_article(self, article_id: int) -> Dict:
        """Get a specific knowledge base article"""
        return self._make_request('GET', f'/articles/{article_id}')
    
    def search_kb_articles(self, query: str) -> List[Dict]:
        """Search knowledge base articles"""
        try:
            response = self._make_request('GET', f'/articles?search={query}')
            return response.get('_embedded', {}).get('articles', [])
        except Exception as e:
            print(f"Note: Knowledge base search may not be available: {e}")
            return []


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
        print("FREESCOUT KNOWLEDGE BASE")
        print("="*60 + "\n")
        
        # Try to access knowledge base
        print("Fetching knowledge base folders...")
        folders = client.list_kb_folders()
        if folders:
            print(f"Found {len(folders)} knowledge base folders:")
            for folder in folders:
                print(f"  - {folder.get('name')} (ID: {folder.get('id')})")
        else:
            print("No folders found or knowledge base may require a module.")
        
        print("\nFetching knowledge base articles...")
        articles = client.list_kb_articles()
        if articles:
            print(f"Found {len(articles)} articles:")
            for article in articles[:5]:  # Show first 5
                print(f"  - {article.get('title', 'Untitled')} (ID: {article.get('id')})")
        else:
            print("No articles found. Knowledge base may require a specific module or different endpoints.")

    except Exception as e:
        print(f"Error: {e}")