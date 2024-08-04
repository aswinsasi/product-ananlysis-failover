from flask import Flask, jsonify
import requests

app = Flask(__name__)

@app.route('/check')
def check():
    try:
        # Check if app1 is healthy
        response1 = requests.get('http://app1:8000/health', timeout=5)
        if response1.status_code == 200:
            return jsonify({"status": "app1 is healthy"}), 200

        # If app1 is not healthy, check app2
        response2 = requests.get('http://app2:8000/health', timeout=5)
        if response2.status_code == 200:
            return jsonify({"status": "app2 is healthy"}), 200

        return jsonify({"status": "both apps are down"}), 500

    except requests.RequestException:
        return jsonify({"status": "error"}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
