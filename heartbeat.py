import requests
import time

CONSUL_URL = "http://localhost:8500/v1/health/service/laravel_app"

def get_service_status():
    try:
        response = requests.get(CONSUL_URL)
        if response.status_code == 200:
            services = response.json()
            for service in services:
                checks = service.get('Checks', [])
                for check in checks:
                    if check['Status'] != 'passing':
                        return False
            return True
    except requests.exceptions.RequestException:
        pass
    return False

while True:
    if get_service_status():
        print("Laravel app is alive")
    else:
        print("Laravel app is down! Taking action...")
        # Implement the logic to switch traffic or restart services

    time.sleep(10)
