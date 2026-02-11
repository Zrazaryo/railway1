# IPv6 Detection Troubleshooting Guide

## Masalah: IPv6 tidak terdeteksi di sistem log

### Kemungkinan Penyebab dan Solusi

## 1. **Server Configuration**

### Cek dukungan IPv6 di server:
```bash
# Cek apakah server mendukung IPv6
cat /proc/net/if_inet6

# Cek interface IPv6
ip -6 addr show

# Test koneksi IPv6
ping6 google.com
```

### Apache Configuration:
```apache
# Pastikan Apache listen di IPv6
Listen [::]:80
Listen [::]:443 ssl

# Virtual host untuk IPv6
<VirtualHost [::]:80>
    ServerName example.com
    DocumentRoot /var/www/html
</VirtualHost>
```

### Nginx Configuration:
```nginx
server {
    listen 80;
    listen [::]:80;
    listen 443 ssl;
    listen [::]:443 ssl;
    
    server_name example.com;
    # ... rest of config
}
```

## 2. **PHP Configuration**

### Cek dukungan IPv6 di PHP:
```php
<?php
// Test di test_ipv6_detection.php
echo "IPv6 Support: " . (defined('AF_INET6') ? 'Yes' : 'No') . "\n";
echo "Filter Extension: " . (extension_loaded('filter') ? 'Yes' : 'No') . "\n";

// Test manual validation
$test_ipv6 = '2001:db8::1';
var_dump(filter_var($test_ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
?>
```

## 3. **Network Configuration**

### Cek routing IPv6:
```bash
# Cek routing table IPv6
ip -6 route show

# Cek DNS IPv6
nslookup -type=AAAA google.com
```

### Firewall Configuration:
```bash
# Pastikan firewall tidak memblokir IPv6
ip6tables -L

# Allow IPv6 HTTP/HTTPS
ip6tables -A INPUT -p tcp --dport 80 -j ACCEPT
ip6tables -A INPUT -p tcp --dport 443 -j ACCEPT
```

## 4. **Proxy/Load Balancer**

### Cloudflare:
- Pastikan IPv6 enabled di dashboard Cloudflare
- Cek header `HTTP_CF_CONNECTING_IP`

### Nginx Proxy:
```nginx
location / {
    proxy_pass http://backend;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header Host $host;
}
```

### HAProxy:
```
frontend web_frontend
    bind *:80
    bind :::80 v4v6
    option forwardfor
    http-request set-header X-Forwarded-For %[src]
```

## 5. **Client-Side Issues**

### Browser IPv6 Support:
- Chrome: `chrome://net-internals/#dns`
- Firefox: `about:networking#dns`

### Test IPv6 connectivity:
```bash
# Test dari client
curl -6 http://your-site.com/test_ipv6_detection.php
wget -6 http://your-site.com/test_ipv6_detection.php
```

## 6. **Code Fixes Applied**

### Updated `get_client_ip()` function:
- ✅ Added `HTTP_X_REAL_IP` header support
- ✅ Improved IPv6 detection logic
- ✅ Prioritize public IPs over private
- ✅ Better handling of comma-separated IP lists

### Enhanced IPv6 analysis:
- ✅ Comprehensive IPv6 range detection
- ✅ Proper formatting for display
- ✅ Geolocation support for public IPv6

## 7. **Testing Steps**

1. **Run test files:**
   ```
   http://your-site.com/test_ipv6_detection.php
   http://your-site.com/test_ipv6_simple.php
   ```

2. **Check server logs:**
   ```bash
   tail -f /var/log/apache2/access.log
   tail -f /var/log/nginx/access.log
   ```

3. **Test with curl:**
   ```bash
   # Force IPv6
   curl -6 -H "X-Forwarded-For: 2001:db8::1" http://your-site.com/test_ipv6_simple.php
   
   # Check headers
   curl -H "X-Real-IP: 2001:4860:4860::8888" http://your-site.com/test_ipv6_simple.php
   ```

## 8. **Common IPv6 Addresses for Testing**

```php
$test_addresses = [
    '::1',                           // Loopback
    'fe80::1',                       // Link-local
    '2001:db8::1',                   // Documentation
    '2001:4860:4860::8888',         // Google DNS
    '2606:4700:4700::1111',         // Cloudflare DNS
    'fc00::1',                       // Unique local
    '2001:0:9d38:6ab8:1c48:3a1c:a95a:b1c2' // Teredo
];
```

## 9. **Debugging Commands**

### Check current IP detection:
```php
<?php
require_once 'includes/functions.php';
$ip = get_client_ip();
echo "Detected: $ip\n";

if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    echo "Type: IPv6\n";
    $analysis = analyze_ipv6($ip);
    echo "Range: " . $analysis['range_info'] . "\n";
} else {
    echo "Type: Not IPv6\n";
}
?>
```

### Check all headers:
```php
<?php
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0 || $key === 'REMOTE_ADDR') {
        echo "$key: $value\n";
    }
}
?>
```

## 10. **Expected Results**

After fixes, you should see:
- ✅ IPv6 addresses in activity logs
- ✅ Proper IPv6 formatting with badges
- ✅ IPv6 geolocation (for public addresses)
- ✅ Detailed IPv6 analysis in IP detail modal

## 11. **If Still Not Working**

1. **Check hosting provider:**
   - Some shared hosting doesn't support IPv6
   - Contact support to enable IPv6

2. **Check DNS:**
   - Ensure domain has AAAA record
   - Test: `dig AAAA your-domain.com`

3. **Check CDN/Proxy:**
   - Disable temporarily to test direct connection
   - Check proxy configuration for IPv6 forwarding

4. **Network environment:**
   - Test from different networks
   - Some ISPs don't provide IPv6
   - Use IPv6 test sites: test-ipv6.com

## 12. **Monitoring IPv6 Usage**

Add to your monitoring:
```sql
-- Count IPv6 vs IPv4 usage
SELECT 
    CASE 
        WHEN ip_address LIKE '%:%' THEN 'IPv6'
        WHEN ip_address LIKE '%.%.%.%' THEN 'IPv4'
        ELSE 'Unknown'
    END as ip_type,
    COUNT(*) as count
FROM activity_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY ip_type;
```

## Contact Information
If IPv6 still not detected after following this guide, check:
1. Server IPv6 configuration
2. Network infrastructure
3. Client IPv6 connectivity
4. Proxy/CDN IPv6 support