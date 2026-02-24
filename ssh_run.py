import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('167.172.102.14', username='root', password='Aa32283228@Aa')

# Get a specific range from the response file
stdin, stdout, stderr = client.exec_command("sed -n '1,50p' /tmp/webhook_response.html | grep -v '^$' | head -30")
print("First 30 non-empty lines:", stdout.read().decode('utf-8', 'replace'))

# Try to find any text that looks like an error
stdin2, stdout2, stderr2 = client.exec_command("""grep -i 'error\|exception\|class not found\|undefined\|fatal' /tmp/webhook_response.html | grep -v 'javascript\|css\|<script\|function\|var ' | head -10""")
print("Error lines:", stdout2.read().decode('utf-8', 'replace'))

# Check if the response may not be from Ignition (maybe it's the SPA)
# Look for React/Vue/Inertia indicators
stdin3, stdout3, stderr3 = client.exec_command("""grep -o 'Inertia\|__Inertia\|inertia\|<div id="app"' /tmp/webhook_response.html | head -5""")
print("Framework indicators:", stdout3.read().decode('utf-8', 'replace'))

# Look for the data payload in the SPA
stdin4, stdout4, stderr4 = client.exec_command("""grep -o 'data-page="[^"]*"' /tmp/webhook_response.html | head -c 500""")
print("Inertia data-page:", stdout4.read().decode('utf-8', 'replace'))

client.close()
