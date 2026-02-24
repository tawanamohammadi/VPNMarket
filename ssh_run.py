import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('167.172.102.14', username='root', password='Aa32283228@Aa')

# Get DB password
stdin, stdout, stderr = client.exec_command("grep DB_PASSWORD /var/www/vpnmarket/.env | cut -d= -f2")
db_pass = stdout.read().decode().strip()

# Update the squad UUID in the database
cmd = f"mysql -utawana -p{db_pass} tawana -e \"INSERT INTO settings (\\`key\\`, value, created_at, updated_at) VALUES ('remnawave_squad_uuid', 'd6dfba60-6644-4470-a18f-b1fb89126495', NOW(), NOW()) ON DUPLICATE KEY UPDATE value='d6dfba60-6644-4470-a18f-b1fb89126495', updated_at=NOW();\" 2>&1"
stdin2, stdout2, stderr2 = client.exec_command(cmd)
result = stdout2.read().decode('utf-8', 'replace')
print('DB Update:', result if result else 'OK ✅')

# Verify it was saved
cmd2 = f"mysql -utawana -p{db_pass} tawana -e \"SELECT * FROM settings WHERE \\`key\\` = 'remnawave_squad_uuid';\" 2>/dev/null"
stdin3, stdout3, stderr3 = client.exec_command(cmd2)
print('Saved value:', stdout3.read().decode('utf-8', 'replace'))

# Clear cache
stdin4, stdout4, stderr4 = client.exec_command("cd /var/www/vpnmarket && php artisan cache:clear 2>&1 | tail -2")
print('Cache clear:', stdout4.read().decode('utf-8', 'replace'))

client.close()
