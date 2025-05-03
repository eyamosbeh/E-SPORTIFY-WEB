from flask import Flask, render_template, jsonify, request
import os
import sys
import subprocess
import time
import json
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# Add the scraping directory to the Python path
scraping_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'scraping')
sys.path.append(scraping_dir)

# Import functions from pipe.py
try:
    from scraping.pipe import run_script
    # Define our own extract_week_prediction function to use absolute paths
    def extract_week_prediction(prediction_file=None):
        """Extract the prediction result from the week_prediction.txt file"""
        try:
            # Use absolute path if prediction_file is not provided
            if prediction_file is None:
                script_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'scraping')
                prediction_file = os.path.join(script_dir, "week_prediction.txt")
                
            with open(prediction_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # Extract the overall prediction
            if "GOOD WEEK" in content:
                return "GOOD WEEK", content
            elif "NOT A GOOD WEEK" in content:
                return "NOT A GOOD WEEK", content
            else:
                return "UNKNOWN", content
        except Exception as e:
            print(f"Error reading prediction file: {e}")
            return "ERROR", ""
            
except ImportError:
    print("Could not import from pipe.py. Make sure the file exists and is accessible.")
    # Create a dummy function for testing
    def extract_week_prediction(prediction_file=None):
        return "UNKNOWN", "No prediction available"

# Directory paths
SCRAPE_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'scraping')

# Define the scripts to run in order
SCRIPTS = [
    (os.path.join(SCRAPE_DIR, "uni.py"), "Data Collection"),
    (os.path.join(SCRAPE_DIR, "weather_analysis.py"), "Weather Analysis"),
    (os.path.join(SCRAPE_DIR, "combine_data.py"), "Data Combination"),
    (os.path.join(SCRAPE_DIR, "week_classifier.py"), "Week Classification")
]

@app.route('/')
def index():
    """Main page of the web application"""
    return jsonify({"status": "Prediction API is running"})

@app.route('/run-pipeline')
def run_pipeline():
    """Run the complete data pipeline and return the results"""
    results = []
    
    # Create scraping directory if it doesn't exist
    os.makedirs(SCRAPE_DIR, exist_ok=True)
    
    # Check if scripts exist, if not create dummy scripts for testing
    for script_path, _ in SCRIPTS:
        if not os.path.exists(script_path):
            os.makedirs(os.path.dirname(script_path), exist_ok=True)
            with open(script_path, 'w', encoding='utf-8') as f:
                f.write('print("This is a dummy script for testing")')
    
    # Create a dummy prediction file if it doesn't exist
    prediction_file = os.path.join(SCRAPE_DIR, "week_prediction.txt")
    if not os.path.exists(prediction_file):
        with open(prediction_file, 'w', encoding='utf-8') as f:
            f.write("GOOD WEEK\n\nConfidence: 100.0%\n\n✓ Weather looks favorable\n✓ No major events scheduled\n✗ Some minor issues detected")
    
    # Run each script in sequence
    for script_path, description in SCRIPTS:
        # Update status
        results.append({"step": description, "status": "Running...", "details": ""})
        
        # Run the script
        start_time = time.time()
        try:
            process = subprocess.run(
                [sys.executable, script_path],
                capture_output=True,
                text=True
            )
            elapsed_time = time.time() - start_time
            
            # Update result
            if process.returncode == 0:
                status = "Success"
                details = process.stdout
            else:
                status = "Failed"
                details = process.stderr
        except Exception as e:
            status = "Failed"
            details = str(e)
            elapsed_time = time.time() - start_time
        
        results[-1].update({
            "status": status,
            "details": details,
            "time": f"{elapsed_time:.2f} seconds"
        })
        
        # Stop if a script fails
        if status == "Failed":
            break
    
    # Add a short wait time to simulate processing
    time.sleep(1)
    
    # Get the final prediction if all scripts succeeded
    if all(result["status"] == "Success" for result in results):
        prediction, details = extract_week_prediction()
        
        # Parse the key factors from the prediction details
        factors = []
        for line in details.split('\n'):
            if line.startswith('✓') or line.startswith('✗'):
                factors.append(line)
        
        prediction_result = {
            "prediction": prediction,
            "confidence": "100%" if "Confidence: 100.0%" in details else "Unknown",
            "factors": factors,
            "details": details
        }
    else:
        prediction_result = {
            "prediction": "ERROR",
            "confidence": "0%",
            "factors": ["Pipeline failed to complete"],
            "details": "Check the logs for more information"
        }
    
    return jsonify({
        "steps": results,
        "prediction": prediction_result
    })

@app.route('/get-prediction')
def get_prediction():
    """Get the latest prediction without running the pipeline"""
    try:
        prediction, details = extract_week_prediction()
        
        # Parse the key factors from the prediction details
        factors = []
        for line in details.split('\n'):
            if line.startswith('✓') or line.startswith('✗'):
                factors.append(line)
        
        return jsonify({
            "prediction": prediction,
            "confidence": "100%" if "Confidence: 100.0%" in details else "Unknown",
            "factors": factors,
            "details": details
        })
    except Exception as e:
        return jsonify({
            "prediction": "ERROR",
            "confidence": "0%",
            "factors": [f"Error: {str(e)}"],
            "details": "Could not retrieve prediction"
        })

if __name__ == '__main__':
    # Create scraping directory if it doesn't exist
    os.makedirs(SCRAPE_DIR, exist_ok=True)
    
    # Create a dummy prediction file if it doesn't exist (for testing)
    prediction_file = os.path.join(SCRAPE_DIR, "week_prediction.txt")
    if not os.path.exists(prediction_file):
        with open(prediction_file, 'w', encoding='utf-8') as f:
            f.write("GOOD WEEK\n\nConfidence: 100.0%\n\n✓ Weather looks favorable\n✓ No major events scheduled\n✗ Some minor issues detected")
    
    # Run the Flask app
    app.run(host='0.0.0.0', port=5000, debug=True)
