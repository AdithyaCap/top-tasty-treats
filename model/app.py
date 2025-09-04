# This system uses both machine learning and semantic embeddings for mood-based food recommendations
from flask import Flask, request, jsonify
from flask_cors import CORS
import pandas as pd
from difflib import get_close_matches
import chromadb
from sentence_transformers import SentenceTransformer
import mysql.connector
import logging
import re
from typing import List, Dict, Optional
import random
import pickle
import os
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import classification_report
import numpy as np


app = Flask(__name__)
CORS(app)


logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


MOOD_FOOD_MAPPING = {
    # Happy moods
    'happy': ['desserts', 'sweet', 'celebration', 'colorful', 'vibrant', 'cake', 'ice cream'],
    'excited': ['spicy', 'energizing', 'bold', 'stimulating', 'pizza', 'burger'],
    'cheerful': ['fresh', 'light', 'colorful', 'sweet', 'salad', 'fruit'],
    'energetic': ['protein', 'spicy', 'coffee', 'energizing', 'power', 'meat', 'nuts'],
    'celebratory': ['desserts', 'special', 'indulgent', 'fancy', 'wine', 'chocolate'],
    'joyful': ['sweet', 'colorful', 'festive', 'cake', 'candy'],
    
    # Sad/comfort moods
    'sad': ['comfort', 'warm', 'hearty', 'creamy', 'soothing', 'soup', 'pasta'],
    'depressed': ['comfort', 'chocolate', 'warm', 'indulgent', 'ice cream', 'cookies'],
    'lonely': ['comfort', 'familiar', 'homestyle', 'warm', 'mac and cheese', 'pizza'],
    'comfort': ['hearty', 'warm', 'creamy', 'filling', 'homestyle', 'stew', 'bread'],
    'down': ['comfort', 'sweet', 'warm', 'indulgent', 'hot chocolate', 'cake'],
    'melancholy': ['warm', 'soothing', 'tea', 'soup', 'comfort'],
    
    # Stressed moods
    'stressed': ['comfort', 'soothing', 'tea', 'light', 'calming', 'herbal tea', 'smoothie'],
    'anxious': ['calming', 'herbal', 'light', 'soothing', 'chamomile', 'yogurt'],
    'overwhelmed': ['simple', 'comforting', 'familiar', 'easy', 'sandwich', 'soup'],
    'tired': ['energizing', 'coffee', 'protein', 'revitalizing', 'energy drink', 'nuts'],
    'exhausted': ['comfort', 'easy', 'nourishing', 'restorative', 'smoothie', 'soup'],
    'nervous': ['calming', 'light', 'soothing', 'herbal', 'tea'],
    
    # Romantic moods
    'romantic': ['elegant', 'fancy', 'wine', 'intimate', 'special', 'chocolate', 'strawberry'],
    'loving': ['sweet', 'chocolate', 'romantic', 'special', 'dessert'],
    'passionate': ['spicy', 'intense', 'bold', 'exotic', 'chili', 'wine'],
    'intimate': ['elegant', 'refined', 'special', 'sophisticated', 'wine', 'cheese'],
    
    # Active/Adventure moods
    'adventurous': ['exotic', 'international', 'unique', 'bold', 'curry', 'sushi'],
    'curious': ['fusion', 'experimental', 'international', 'unique', 'ethnic'],
    'playful': ['fun', 'colorful', 'creative', 'playful', 'rainbow', 'candy'],
    'bold': ['spicy', 'intense', 'strong', 'flavorful'],
    
    # Calm/Peaceful moods
    'calm': ['light', 'healthy', 'fresh', 'peaceful', 'salad', 'green tea'],
    'peaceful': ['herbal', 'light', 'natural', 'soothing', 'tea', 'vegetables'],
    'relaxed': ['easy', 'casual', 'comfortable', 'light', 'snacks'],
    'zen': ['healthy', 'fresh', 'natural', 'balanced', 'green', 'tofu'],
    'serene': ['light', 'peaceful', 'gentle', 'herbal'],
    
    # Weather/Seasonal
    'cold': ['warm', 'hearty', 'soup', 'hot', 'comforting', 'stew', 'cocoa'],
    'hot': ['cold', 'refreshing', 'iced', 'light', 'cooling', 'ice cream', 'salad'],
    'rainy': ['warm', 'comfort', 'hearty', 'cozy', 'soup', 'coffee'],
    'sunny': ['fresh', 'light', 'bright', 'energizing', 'fruit', 'smoothie'],
    
    # Energy levels
    'sleepy': ['coffee', 'energizing', 'caffeine', 'stimulating'],
    'lazy': ['easy', 'simple', 'comfort', 'casual'],
    'motivated': ['healthy', 'energizing', 'protein', 'fresh']
}

#  MACHINE LEARNING COMPONENTS 
class MoodFoodClassifier:
    def __init__(self):
        self.vectorizer = None
        self.model = None
        self.is_trained = False
        self.model_path = 'mood_food_model.pkl'
        self.vectorizer_path = 'mood_vectorizer.pkl'
    
    def load_or_train_model(self, csv_path: str = None):
        """Load existing model or train a new one"""
        if self._load_existing_model():
            logger.info("Loaded existing ML model")
            return True
        
        if csv_path and os.path.exists(csv_path):
            return self._train_new_model(csv_path)
        else:
            # Create synthetic training data from mood mapping
            return self._create_and_train_synthetic_model()
    
    def _load_existing_model(self) -> bool:
        """Try to load existing trained model"""
        try:
            if os.path.exists(self.model_path) and os.path.exists(self.vectorizer_path):
                with open(self.model_path, 'rb') as f:
                    self.model = pickle.load(f)
                with open(self.vectorizer_path, 'rb') as f:
                    self.vectorizer = pickle.load(f)
                self.is_trained = True
                return True
        except Exception as e:
            logger.warning(f"Could not load existing model: {e}")
        return False
    
    def _train_new_model(self, csv_path: str) -> bool:
        """Train model from CSV data with correct column names"""
        try:
            logger.info(f"Attempting to load CSV from: {csv_path}")
            df = pd.read_csv(csv_path)
            logger.info(f"CSV loaded successfully. Shape: {df.shape}")
            logger.info(f"CSV columns: {df.columns.tolist()}")
            
            
            if 'keyword' not in df.columns or 'foods' not in df.columns:
                logger.error(f"CSV must have 'keyword' and 'foods' columns. Found: {df.columns.tolist()}")
                return False
            
            
            df = df.dropna(subset=['keyword', 'foods'])  
            X = df['keyword'].astype(str)
            y = df['foods'].astype(str)
            
            logger.info(f"Training data prepared. X shape: {X.shape}, y unique values: {y.nunique()}")
            
           
            if len(X) < 10:
                logger.warning("Not enough training data in CSV, falling back to synthetic model")
                return self._create_and_train_synthetic_model()
            
            self.vectorizer = TfidfVectorizer(max_features=1000, stop_words='english')
            X_vec = self.vectorizer.fit_transform(X)
            
            X_train, X_test, y_train, y_test = train_test_split(
                X_vec, y, test_size=0.2, random_state=42, stratify=y if y.nunique() > 1 else None
            )
            
            self.model = LogisticRegression(max_iter=1000, random_state=42)
            self.model.fit(X_train, y_train)
            
           
            y_pred = self.model.predict(X_test)
            logger.info("Model training completed from CSV")
            logger.info(f"Classification Report:\n{classification_report(y_test, y_pred)}")
            
            self._save_model()
            self.is_trained = True
            return True
            
        except FileNotFoundError:
            logger.error(f"CSV file not found at: {csv_path}")
            return self._create_and_train_synthetic_model()
        except pd.errors.EmptyDataError:
            logger.error("CSV file is empty")
            return self._create_and_train_synthetic_model()
        except Exception as e:
            logger.error(f"Error training model from CSV: {e}")
            return self._create_and_train_synthetic_model()
    
    def _create_and_train_synthetic_model(self) -> bool:
        """Create synthetic training data from mood mapping"""
        try:
            logger.info("Creating synthetic training data from mood mapping")
            training_data = []
            
            
            for mood, food_keywords in MOOD_FOOD_MAPPING.items():
                for food_keyword in food_keywords:
                    training_data.append((mood, food_keyword))
                    
                    training_data.append((f"feeling {mood}", food_keyword))
                    training_data.append((f"I am {mood}", food_keyword))
                    training_data.append((f"{mood} mood", food_keyword))
            
            
            additional_data = [
                ("happy birthday", "cake"), ("celebration", "dessert"),
                ("winter cold", "soup"), ("summer heat", "ice cream"),
                ("work stress", "coffee"), ("late night", "snacks"),
                ("morning", "breakfast"), ("lunch time", "sandwich")
            ]
            training_data.extend(additional_data)
            
            df = pd.DataFrame(training_data, columns=['keyword', 'foods'])
            X = df['keyword'].astype(str)
            y = df['foods'].astype(str)
            
            logger.info(f"Synthetic training data created. Shape: {df.shape}")
            
            self.vectorizer = TfidfVectorizer(max_features=1000, stop_words='english')
            X_vec = self.vectorizer.fit_transform(X)
            
            self.model = LogisticRegression(max_iter=1000, random_state=42)
            self.model.fit(X_vec, y)
            
            self._save_model()
            self.is_trained = True
            logger.info("Synthetic model training completed")
            return True
            
        except Exception as e:
            logger.error(f"Error creating synthetic model: {e}")
            return False
    
    def _save_model(self):
        """Save trained model and vectorizer"""
        try:
            with open(self.model_path, 'wb') as f:
                pickle.dump(self.model, f)
            with open(self.vectorizer_path, 'wb') as f:
                pickle.dump(self.vectorizer, f)
            logger.info("Model and vectorizer saved successfully")
        except Exception as e:
            logger.warning(f"Could not save model: {e}")
    
    def predict_food_categories(self, mood_text: str, n_predictions: int = 3) -> List[str]:
        """Predict food categories based on mood"""
        if not self.is_trained:
            return []
        
        try:
            mood_vec = self.vectorizer.transform([mood_text])
            
            
            probabilities = self.model.predict_proba(mood_vec)[0]
            classes = self.model.classes_
            
            
            top_indices = np.argsort(probabilities)[-n_predictions:][::-1]
            top_predictions = [classes[i] for i in top_indices if probabilities[i] > 0.1]
            
            return top_predictions
        except Exception as e:
            logger.error(f"Error in ML prediction: {e}")
            return []

#  CHROMA DB AND EMBEDDING MODEL SETUP 
chroma_client = chromadb.Client()
recommendation_collection = chroma_client.get_or_create_collection("food_recommendations")
embedding_model = SentenceTransformer('all-MiniLM-L6-v2')


DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'gallerycafe'
}

# CSV Dataset Configuration 
CSV_DATASET_PATH = r"D:\UNI\Top Tasty treats new\model\ai_food_search_dataset_8.csv"  



food_data_details = {}
food_keyword_dict = {}
mood_classifier = MoodFoodClassifier()

def analyze_food_content(name: str, description: str) -> Dict[str, List[str]]:
    """
    Comprehensive food content analysis using both name and description.
    Returns categorized food attributes and mood associations.
    """
    
    full_text = f"{name} {description}".lower()
    
    
    food_categories = {
        'desserts': {
            'keywords': ['cake', 'ice cream', 'dessert', 'sweet', 'chocolate', 'candy', 'cookie', 
                        'pastry', 'pie', 'tart', 'pudding', 'mousse', 'brownie', 'donut', 'muffin',
                        'cheesecake', 'tiramisu', 'gelato', 'sorbet', 'macaron', 'cupcake'],
            'moods': ['happy', 'celebration', 'joyful', 'indulgent', 'romantic', 'loving']
        },
        'comfort_food': {
            'keywords': ['soup', 'stew', 'warm', 'broth', 'casserole', 'mac and cheese', 'mashed potato',
                        'comfort', 'hearty', 'filling', 'creamy', 'rich', 'homestyle', 'traditional',
                        'gravy', 'butter', 'cheese sauce', 'baked', 'roasted'],
            'moods': ['sad', 'lonely', 'comfort', 'stressed', 'cold', 'tired', 'down']
        },
        'fresh_healthy': {
            'keywords': ['salad', 'fresh', 'healthy', 'green', 'vegetable', 'fruit', 'raw', 'organic',
                        'quinoa', 'kale', 'spinach', 'avocado', 'tomato', 'cucumber', 'lettuce',
                        'smoothie', 'juice', 'lean', 'grilled', 'steamed', 'natural'],
            'moods': ['calm', 'peaceful', 'energetic', 'motivated', 'zen', 'healthy', 'light']
        },
        'spicy_bold': {
            'keywords': ['spicy', 'hot', 'chili', 'pepper', 'jalapeño', 'habanero', 'cayenne', 'sriracha',
                        'curry', 'wasabi', 'ginger', 'garlic', 'bold', 'intense', 'fiery', 'burning',
                        'tangy', 'zesty', 'peppery'],
            'moods': ['excited', 'energetic', 'passionate', 'adventurous', 'bold', 'stimulating']
        },
        'beverages': {
            'keywords': ['coffee', 'tea', 'drink', 'beverage', 'juice', 'smoothie', 'latte', 'espresso',
                        'cappuccino', 'mocha', 'chai', 'herbal tea', 'green tea', 'black tea',
                        'milkshake', 'cocktail', 'wine', 'beer', 'soda', 'water'],
            'moods': ['tired', 'energetic', 'relaxed', 'social', 'calm', 'stressed', 'morning']
        },
        'casual_comfort': {
            'keywords': ['pizza', 'burger', 'sandwich', 'pasta', 'noodles', 'fries', 'wings', 'tacos',
                        'burrito', 'hot dog', 'sub', 'wrap', 'quesadilla', 'nachos', 'chips'],
            'moods': ['casual', 'happy', 'excited', 'social', 'comfort', 'relaxed', 'playful']
        },
        'exotic_international': {
            'keywords': ['sushi', 'thai', 'indian', 'chinese', 'japanese', 'mexican', 'italian', 'greek',
                        'moroccan', 'korean', 'vietnamese', 'exotic', 'ethnic', 'international',
                        'fusion', 'authentic', 'traditional', 'foreign', 'curry', 'pad thai'],
            'moods': ['adventurous', 'curious', 'bold', 'exotic', 'cultural', 'exploring']
        },
        'protein_rich': {
            'keywords': ['meat', 'chicken', 'beef', 'pork', 'fish', 'salmon', 'tuna', 'shrimp', 'seafood',
                        'protein', 'steak', 'turkey', 'lamb', 'bacon', 'sausage', 'eggs', 'tofu',
                        'nuts', 'beans', 'lentils', 'quinoa'],
            'moods': ['energetic', 'strong', 'powerful', 'motivated', 'athletic', 'building']
        },
        'light_refreshing': {
            'keywords': ['light', 'refreshing', 'cool', 'cold', 'iced', 'chilled', 'crisp', 'clean',
                        'water', 'cucumber', 'mint', 'lime', 'lemon', 'citrus', 'sorbet', 'granita'],
            'moods': ['hot', 'sunny', 'refreshed', 'clean', 'pure', 'cooling']
        },
        'indulgent_rich': {
            'keywords': ['rich', 'creamy', 'buttery', 'decadent', 'indulgent', 'luxurious', 'gourmet',
                        'premium', 'expensive', 'fancy', 'elegant', 'sophisticated', 'truffle',
                        'caviar', 'foie gras', 'wagyu'],
            'moods': ['romantic', 'special', 'celebrating', 'luxurious', 'treating', 'sophisticated']
        }
    }
    
   
    detected_categories = []
    mood_associations = []
    
    for category, data in food_categories.items():
        keywords = data['keywords']
        moods = data['moods']
        
      
        matches = [keyword for keyword in keywords if keyword in full_text]
        if matches:
            detected_categories.append(category)
            mood_associations.extend(moods)
    
   
    texture_keywords = []
    if any(word in full_text for word in ['crispy', 'crunchy', 'crisp']):
        texture_keywords.append('crispy crunchy satisfying')
    if any(word in full_text for word in ['soft', 'tender', 'fluffy', 'moist']):
        texture_keywords.append('soft tender comforting')
    if any(word in full_text for word in ['chewy', 'dense', 'thick']):
        texture_keywords.append('substantial filling hearty')
    
    temperature_keywords = []
    if any(word in full_text for word in ['hot', 'warm', 'heated', 'steaming']):
        temperature_keywords.append('hot warm comforting cozy')
    if any(word in full_text for word in ['cold', 'chilled', 'frozen', 'iced']):
        temperature_keywords.append('cold refreshing cooling')
    
    return {
        'categories': detected_categories,
        'moods': list(set(mood_associations)),  
        'textures': texture_keywords,
        'temperatures': temperature_keywords
    }

def create_enhanced_embeddings(item_data: Dict) -> str:
    """Create enhanced text for better embeddings using comprehensive food analysis."""
    name = item_data.get('name', '')
    description = item_data.get('description', '')
    
   
    enhanced_text = f"{name} {description}"
    
    
    analysis = analyze_food_content(name, description)
    
   
    category_keywords = []
    for category in analysis['categories']:
        if category == 'desserts':
            category_keywords.append('dessert sweet indulgent celebration happy joyful')
        elif category == 'comfort_food':
            category_keywords.append('warm comfort hearty soothing sad cold tired down')
        elif category == 'fresh_healthy':
            category_keywords.append('fresh healthy light calm peaceful energetic zen')
        elif category == 'spicy_bold':
            category_keywords.append('spicy bold energizing excited passionate adventurous')
        elif category == 'beverages':
            category_keywords.append('beverage energizing tired stressed relaxed morning')
        elif category == 'casual_comfort':
            category_keywords.append('comfort casual satisfying happy excited social playful')
        elif category == 'exotic_international':
            category_keywords.append('fresh healthy adventurous exotic cultural exploring')
        elif category == 'protein_rich':
            category_keywords.append('protein energetic strong powerful motivated athletic')
        elif category == 'light_refreshing':
            category_keywords.append('light refreshing cooling hot sunny clean pure')
        elif category == 'indulgent_rich':
            category_keywords.append('rich indulgent romantic special luxurious sophisticated')
    
    
    mood_keywords = ' '.join(analysis['moods'])
    
    
    texture_keywords = ' '.join(analysis['textures'])
    temperature_keywords = ' '.join(analysis['temperatures'])
    
    
    all_enhancements = ' '.join(category_keywords + [mood_keywords, texture_keywords, temperature_keywords])
    enhanced_text += " " + all_enhancements
    
    return enhanced_text

def load_and_process_data():
    """Enhanced data loading with better categorization and error handling."""
    global food_data_details, food_keyword_dict

    try:
        db_connection = mysql.connector.connect(**DB_CONFIG)
        cursor = db_connection.cursor(dictionary=True)
        
        cursor.execute("SELECT i.id, i.name, i.des, i.price, i.img, u.name AS shop_name, u.id AS shop_id, u.address AS shop_address FROM items i JOIN users u ON i.shop_id = u.id")
        db_items = cursor.fetchall()
        
        db_connection.close()
        logger.info(f"Successfully loaded {len(db_items)} items from database")

    except mysql.connector.Error as err:
        logger.error(f"Database error: {err}")
        return None

    if not db_items:
        logger.warning("No items found in database")
        return None

    documents_for_chroma = []
    ids_for_chroma = []

    for item in db_items:
        
        item_details = {
            "id": item['id'],
            "name": item['name'],
            "description": item['des'] or "Delicious dish",
            "price": float(item['price']) if item['price'] else 0.0,
            "image": item['img'],
            "shop_name": item['shop_name'],
            "shop_address": item['shop_address'],
            "shop_id": item['shop_id']
        }
        
        food_data_details[item['name'].lower()] = item_details
        food_keyword_dict[item['name'].lower()] = item['name']
        
        
        enhanced_text = create_enhanced_embeddings(item_details)
        documents_for_chroma.append(enhanced_text)
        ids_for_chroma.append(f"item_{item['id']}")

   
    if recommendation_collection.count() == 0:
        logger.info("Populating ChromaDB with enhanced embeddings...")
        
        try:
            embeddings = embedding_model.encode(documents_for_chroma).tolist()
            recommendation_collection.add(
                documents=documents_for_chroma,
                embeddings=embeddings,
                ids=ids_for_chroma
            )
            logger.info("ChromaDB successfully populated")
        except Exception as e:
            logger.error(f"Error populating ChromaDB: {e}")
            return None
    else:
        logger.info("ChromaDB already populated")

    return food_keyword_dict

def get_mood_keywords(user_input: str) -> List[str]:
    """Extract mood-related keywords and map them to food categories."""
    user_input_lower = user_input.lower()
    mood_keywords = []
    
   
    for mood, keywords in MOOD_FOOD_MAPPING.items():
        if mood in user_input_lower:
            mood_keywords.extend(keywords)
    
   
    food_related_words = [
        'sweet', 'spicy', 'sour', 'salty', 'bitter', 'umami',
        'hot', 'cold', 'warm', 'fresh', 'crispy', 'creamy',
        'light', 'heavy', 'healthy', 'indulgent', 'comfort'
    ]
    
    for word in food_related_words:
        if word in user_input_lower:
            mood_keywords.append(word)
    
    return list(set(mood_keywords))  

def ml_based_search(user_input: str, num_results: int = 3) -> List[Dict]:
    """Use ML classifier to predict food categories and find matching items using comprehensive analysis."""
    if not mood_classifier.is_trained:
        return []
    
    try:
        
        predicted_categories = mood_classifier.predict_food_categories(user_input, n_predictions=5)
        
        if not predicted_categories:
            return []
        
        
        matching_items = []
        scored_items = []
        
        for name, details in food_data_details.items():
           
            analysis = analyze_food_content(details['name'], details['description'])
            
            
            item_categories = ' '.join(analysis['categories'])
            item_moods = ' '.join(analysis['moods'])
            item_text = f"{details['name']} {details['description']} {item_categories} {item_moods}".lower()
            
            
            score = 0
            matched_categories = []
            
            for category in predicted_categories:
                category_words = category.lower().split()
                
               
                if category.lower() in item_text:
                    score += 3
                    matched_categories.append(category)
                
                
                word_matches = sum(1 for word in category_words if word in item_text)
                if word_matches > 0:
                    score += word_matches
                    if word_matches >= len(category_words) * 0.6:  
                        matched_categories.append(category)
            
            
            user_mood_keywords = get_mood_keywords(user_input)
            mood_matches = sum(1 for mood in user_mood_keywords if mood in item_text)
            score += mood_matches * 2
            
            if score > 0:
                scored_items.append({
                    'item': details,
                    'score': score,
                    'matched_categories': matched_categories,
                    'analysis': analysis
                })
        
    
        scored_items.sort(key=lambda x: x['score'], reverse=True)
        matching_items = [item['item'] for item in scored_items[:num_results]]
        
        
        if not matching_items and predicted_categories:
            category_query = " ".join(predicted_categories)
            return vector_search(category_query, num_results)
        
        
        if scored_items:
            logger.info(f"ML search found {len(matching_items)} items with scores: {[item['score'] for item in scored_items[:num_results]]}")
        
        return matching_items
        
    except Exception as e:
        logger.error(f"ML search error: {e}")
        return []

def keyword_search(user_input: str, num_results: int = 3) -> List[Dict]:
    """Enhanced keyword search with fuzzy matching using both name and description."""
    user_input_lower = user_input.lower()
    user_words = set(user_input_lower.split())
    
    
    scored_items = []
    
    for name, details in food_data_details.items():
        score = 0
        
        
        analysis = analyze_food_content(details['name'], details['description'])
        full_item_text = f"{details['name']} {details['description']}".lower()
        
        
        if user_input_lower in details['name'].lower():
            score += 10
        
       
        description_words = set(details['description'].lower().split())
        word_matches = len(user_words.intersection(description_words))
        score += word_matches * 2
        
        
        for category in analysis['categories']:
            if any(word in category for word in user_words):
                score += 3
        
        
        mood_keywords = get_mood_keywords(user_input)
        for mood in analysis['moods']:
            if mood in mood_keywords:
                score += 2
        
        
        available_names = [details['name'].lower()]
        fuzzy_matches = get_close_matches(user_input_lower, available_names, n=1, cutoff=0.4)
        if fuzzy_matches:
            score += 5
        
        
        for word in user_words:
            if word in full_item_text and len(word) > 2:  
                score += 1
        
        if score > 0:
            scored_items.append({
                'item': details,
                'score': score,
                'analysis': analysis
            })
    
    
    scored_items.sort(key=lambda x: x['score'], reverse=True)
    results = [item['item'] for item in scored_items[:num_results]]
    
    if scored_items:
        logger.info(f"Keyword search found {len(results)} items with scores: {[item['score'] for item in scored_items[:num_results]]}")
    
    return results

def vector_search(query_text: str, num_results: int = 3) -> List[Dict]:
    """Enhanced vector search with better result processing."""
    try:
        
        mood_keywords = get_mood_keywords(query_text)
        enhanced_query = f"{query_text} {' '.join(mood_keywords)}"
        
        user_vector = embedding_model.encode([enhanced_query]).tolist()
        
        results = recommendation_collection.query(
            query_embeddings=user_vector,
            n_results=min(num_results * 2, 10)  
        )
        
        recommendations = []
        if results['documents'] and results['documents'][0]:
            for i, doc in enumerate(results['documents'][0]):
                
                words = doc.lower().split()
                
                
                for name, details in food_data_details.items():
                    if any(word in name for word in words[:3]) or name.split()[0] in doc.lower():
                        if details not in recommendations:  
                            recommendations.append(details)
                        break
                
                if len(recommendations) >= num_results:
                    break
        
        return recommendations[:num_results]
    
    except Exception as e:
        logger.error(f"Vector search error: {e}")
        return []


food_keyword_data = load_and_process_data()
mood_classifier.load_or_train_model(CSV_DATASET_PATH)  

@app.route('/recommend', methods=['POST'])
def recommend_enhanced():
    """
    Enhanced recommendation endpoint combining ML classification and semantic search.
    """
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON data provided"}), 400
            
        user_input = data.get('query', '').strip()
        num_results = min(data.get('num_results', 3), 10)  
        use_ml = data.get('use_ml', True) 

        if not user_input:
            return jsonify({"error": "Query not provided or empty"}), 400

        logger.info(f"Processing query: '{user_input}'")

        all_recommendations = []
        methods_used = []

       
        if use_ml and mood_classifier.is_trained:
            ml_results = ml_based_search(user_input, num_results)
            if ml_results:
                all_recommendations.extend(ml_results)
                methods_used.append("ml_classification")
                logger.info(f"Found {len(ml_results)} ML-based matches")

       
        if len(all_recommendations) < num_results:
            remaining_needed = num_results - len(all_recommendations)
            keyword_results = keyword_search(user_input, remaining_needed)
            
            
            for result in keyword_results:
                if not any(r['id'] == result['id'] for r in all_recommendations):
                    all_recommendations.append(result)
            
            if keyword_results:
                methods_used.append("keyword_search")
                logger.info(f"Added {len([r for r in keyword_results if not any(rec['id'] == r['id'] for rec in all_recommendations[:-len(keyword_results)])])} keyword matches")

        
        if len(all_recommendations) < num_results:
            remaining_needed = num_results - len(all_recommendations)
            vector_results = vector_search(user_input, remaining_needed)
            
            
            for result in vector_results:
                if not any(r['id'] == result['id'] for r in all_recommendations):
                    all_recommendations.append(result)
            
            if vector_results:
                methods_used.append("vector_search")
                logger.info(f"Added {len([r for r in vector_results if not any(rec['id'] == r['id'] for rec in all_recommendations[:-len(vector_results)])])} vector search results")

        
        if not all_recommendations:
            mood_keywords = get_mood_keywords(user_input)
            if mood_keywords:
               
                mood_matches = []
                for name, details in food_data_details.items():
                    item_text = f"{details['name']} {details['description']}".lower()
                    if any(keyword in item_text for keyword in mood_keywords):
                        mood_matches.append(details)
                
                if mood_matches:
                    all_recommendations.extend(random.sample(mood_matches, min(3, len(mood_matches))))
                    methods_used.append("mood_keyword_fallback")
                else:
                  
                    random_items = random.sample(list(food_data_details.values()), min(3, len(food_data_details)))
                    all_recommendations.extend(random_items)
                    methods_used.append("random_fallback")
            else:
                
                random_items = random.sample(list(food_data_details.values()), min(3, len(food_data_details)))
                all_recommendations.extend(random_items)
                methods_used.append("random_fallback")
            
            logger.info("Using fallback recommendations")

      
        final_recommendations = all_recommendations[:num_results]
        
        
        ml_predictions = []
        if mood_classifier.is_trained:
            ml_predictions = mood_classifier.predict_food_categories(user_input, 3)
        
        response = {
            "methods": methods_used,
            "recommendations": final_recommendations,
            "query": user_input,
            "total_found": len(final_recommendations),
            "ml_predictions": ml_predictions,
            "mood_keywords": get_mood_keywords(user_input)
        }
        
        logger.info(f"Returning {len(final_recommendations)} recommendations using methods: {methods_used}")
        return jsonify(response)

    except Exception as e:
        logger.error(f"Error in recommend_enhanced: {e}")
        return jsonify({"error": "Internal server error"}), 500

@app.route('/analyze-food', methods=['POST'])
def analyze_food_item():
    """Endpoint to analyze a specific food item's characteristics and mood associations."""
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON data provided"}), 400
            
        food_name = data.get('name', '').strip()
        food_description = data.get('description', '').strip()
        
        if not food_name:
            return jsonify({"error": "Food name not provided"}), 400

        
        analysis = analyze_food_content(food_name, food_description)
        
       
        item_data = {'name': food_name, 'description': food_description}
        enhanced_text = create_enhanced_embeddings(item_data)
        
        return jsonify({
            "food_name": food_name,
            "description": food_description,
            "analysis": {
                "detected_categories": analysis['categories'],
                "mood_associations": analysis['moods'],
                "texture_keywords": analysis['textures'],
                "temperature_keywords": analysis['temperatures']
            },
            "enhanced_embedding_text": enhanced_text,
            "total_categories": len(analysis['categories']),
            "total_mood_associations": len(analysis['moods'])
        })
    
    except Exception as e:
        logger.error(f"Error in analyze_food_item: {e}")
        return jsonify({"error": "Internal server error"}), 500

@app.route('/mood-predict', methods=['POST'])
def predict_mood_categories():
    """Endpoint to get ML predictions for mood-based food categories."""
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON data provided"}), 400
            
        user_input = data.get('mood', '').strip()
        if not user_input:
            return jsonify({"error": "Mood not provided"}), 400

        if not mood_classifier.is_trained:
            return jsonify({"error": "ML model not available"}), 503

        predictions = mood_classifier.predict_food_categories(user_input, 5)
        mood_keywords = get_mood_keywords(user_input)
        
        return jsonify({
            "mood": user_input,
            "ml_predictions": predictions,
            "mood_keywords": mood_keywords
        })

    except Exception as e:
        logger.error(f"Error in mood prediction: {e}")
        return jsonify({"error": "Internal server error"}), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Enhanced health check endpoint."""
    return jsonify({
        "status": "healthy",
        "total_items": len(food_data_details),
        "chromadb_count": recommendation_collection.count(),
        "ml_model_trained": mood_classifier.is_trained,
        "available_endpoints": ["/recommend", "/analyze-food", "/mood-predict", "/health"]
    })

@app.errorhandler(404)
def not_found(error):
    return jsonify({"error": "Endpoint not found"}), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({"error": "Internal server error"}), 500


if __name__ == '__main__':
    if food_keyword_data is None:
        logger.error("Failed to load data. Server cannot start properly.")
    else:
        logger.info(f"Server starting with {len(food_data_details)} food items loaded")
        logger.info(f"ML model trained: {mood_classifier.is_trained}")
        app.run(debug=True, host='127.0.0.1', port=5000)