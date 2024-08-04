from flask import Flask, request, Response, jsonify
import json
import logging
import requests

app = Flask(__name__)
SHARED_STATUS_FILE = "D:/laragon/www/product-analysis/status.json"  # Path to the shared network file
HEALTH_CHECK_URL = "http://localhost:8000/health"  # Health check URL for the backend servers

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def read_status():
    try:
        with open(SHARED_STATUS_FILE, 'r') as file:
            return json.load(file)
    except (FileNotFoundError, json.JSONDecodeError) as e:
        logger.error(f"Error reading status file: {e}")
        return {"active_server": None}

def get_active_server():
    status = read_status()
    return status.get("active_server", None)

def is_server_alive(url):
    try:
        response = requests.get(url, timeout=5)  # Set a timeout for the health check
        return response.status_code == 200
    except requests.RequestException as e:
        logger.error(f"Health check failed for {url}: {e}")
        return False

@app.route('/<path:path>', methods=['GET', 'POST', 'PUT', 'DELETE'])
def proxy(path):
    active_server = get_active_server()
    if active_server:
        target_url = f"http://localhost:8000/{path}"  # Ensure this URL is correct
        if is_server_alive(HEALTH_CHECK_URL):
            try:
                # Forward the request to the active server
                response = requests.request(
                    method=request.method,
                    url=target_url,
                    headers={k: v for k, v in request.headers if k != 'Host'},
                    data=request.get_data(),
                    cookies=request.cookies,
                    allow_redirects=False
                )
                # Return the response from the backend server
                return Response(
                    response.content,
                    status=response.status_code,
                    headers=dict(response.headers)
                )
            except requests.RequestException as e:
                logger.error(f"Request to backend server failed: {e}")
                return jsonify({"error": "Request to backend server failed"}), 502
        else:
            logger.warning(f"Active server {active_server} is not responding.")
            return jsonify({"error": "Active server is not responding"}), 502
    logger.error("No active server found.")
    return jsonify({"error": "No active server available"}), 502

if __name__ == "__main__":
    app.run(host='0.0.0.0', port=8080, debug=True)
