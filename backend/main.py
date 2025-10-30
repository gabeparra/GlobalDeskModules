from GlobalDeskClient import FreeScoutClient

def main():
    """Example usage of FreeScout client"""
    client = FreeScoutClient()
    
    print("="*60)
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

if __name__ == '__main__':
    main()