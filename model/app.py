from flask import Flask, request, jsonify
import pandas as pd
from difflib import get_close_matches
import chromadb

# --- YOUR KEYWORD MODEL CODE ---
df = pd.read_csv("D:/UNI/Top Tasty treats/model/ai_food_search_dataset_8.csv")
food_dict = dict(zip(df['keyword'], df['foods']))

def suggest_food_keyword(user_input):
    user_input = user_input.lower()
    if user_input in food_dict:
        return food_dict[user_input]
    matches = get_close_matches(user_input, food_dict.keys(), n=1, cutoff=0.5)
    if matches:
        return food_dict[matches[0]]
    for key in food_dict.keys():
        if key in user_input:
            return food_dict[key]
    return None

# --- CHROMA DB SETUP ---
chroma_client = chromadb.Client()
recommendation_collection = chroma_client.get_or_create_collection("my_recommendation_data")

def get_embedding(text):
    return [1.2, 2.2, 3.1]

# --- FLASK APP WITH HYBRID LOGIC ---
app = Flask(__name__)

@app.route('/recommend', methods=['POST'])
def recommend_hybrid():
    data = request.get_json()
    user_input = data.get('query')

    if not user_input:
        return jsonify({"error": "Query not provided"}), 400

    keyword_result = suggest_food_keyword(user_input)

    if keyword_result:
        return jsonify({"method": "keyword_search", "recommendations": [keyword_result]})
    else:
        print("No keyword match, performing vector search...")
        user_vector = get_embedding(user_input)
        results = recommendation_collection.query(
            query_embeddings=[user_vector],
            n_results=1
        )
        vector_recommendation = results['documents'][0][0] if results['documents'] and results['documents'][0] else "No recommendation found."
        return jsonify({"method": "vector_search", "recommendations": [vector_recommendation]})

if __name__ == '__main__':
    app.run(debug=True)