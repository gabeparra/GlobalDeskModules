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

if __name__ == '__main__':
    main()