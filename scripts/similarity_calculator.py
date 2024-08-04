import sys
import json
import torch
from transformers import BertTokenizer, BertModel

def calculate_similarity(product_name, product_titles):
    tokenizer = BertTokenizer.from_pretrained('bert-base-uncased')
    model = BertModel.from_pretrained('bert-base-uncased')

    inputs = tokenizer([product_name] + product_titles, return_tensors='pt', padding=True, truncation=True)
    outputs = model(**inputs)

    # Assuming outputs.last_hidden_state contains the embeddings
    embeddings = outputs.last_hidden_state[:, 0, :]  # Take the [CLS] token

    product_embedding = embeddings[0]
    title_embeddings = embeddings[1:]

    similarities = torch.nn.functional.cosine_similarity(product_embedding.unsqueeze(0), title_embeddings)
    top_similarities = similarities.topk(3).indices  # Get the indices of top 3 similarities

    return [product_titles[idx] for idx in top_similarities]

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps([]))
        sys.exit(1)

    product_name = sys.argv[1]
    product_titles = sys.argv[2:]
    
    top_matches = calculate_similarity(product_name, product_titles)
    
    print(json.dumps(top_matches))
