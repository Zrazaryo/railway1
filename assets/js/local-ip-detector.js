/**
 * Local IP Detector using WebRTC
 * Detects client's local IP addresses (IPv4 and IPv6)
 */
class LocalIPDetector {
    constructor() {
        this.localIPs = [];
        this.callbacks = [];
        this.isDetecting = false;
    }

    /**
     * Detect local IP addresses
     */
    async detectLocalIPs() {
        if (this.isDetecting) {
            return this.localIPs;
        }

        this.isDetecting = true;
        this.localIPs = [];

        try {
            // Method 1: WebRTC STUN servers
            await this.detectViaWebRTC();
            
            // Method 2: Network Information API (if available)
            await this.detectViaNetworkAPI();
            
            // Method 3: Fallback methods
            await this.detectFallback();
            
        } catch (error) {
            console.warn('Local IP detection error:', error);
        }

        this.isDetecting = false;
        this.notifyCallbacks();
        return this.localIPs;
    }

    /**
     * WebRTC method for IP detection
     */
    async detectViaWebRTC() {
        return new Promise((resolve) => {
            const rtcConfig = {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                    { urls: 'stun:stun2.l.google.com:19302' },
                    { urls: 'stun:stun.services.mozilla.com' }
                ]
            };

            const pc = new RTCPeerConnection(rtcConfig);
            const ips = new Set();

            // Create data channel
            pc.createDataChannel('');

            // Handle ICE candidates
            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    const candidate = event.candidate.candidate;
                    const ipMatch = candidate.match(/(\d+\.\d+\.\d+\.\d+)|([0-9a-f]{1,4}:[0-9a-f:]*:[0-9a-f]{1,4})/gi);
                    
                    if (ipMatch) {
                        ipMatch.forEach(ip => {
                            if (this.isValidIP(ip) && !ips.has(ip)) {
                                ips.add(ip);
                                this.addLocalIP(ip, 'WebRTC');
                            }
                        });
                    }
                }
            };

            // Handle ICE gathering state
            pc.onicegatheringstatechange = () => {
                if (pc.iceGatheringState === 'complete') {
                    pc.close();
                    resolve();
                }
            };

            // Create offer
            pc.createOffer()
                .then(offer => pc.setLocalDescription(offer))
                .catch(error => {
                    console.warn('WebRTC offer creation failed:', error);
                    pc.close();
                    resolve();
                });

            // Timeout fallback
            setTimeout(() => {
                if (pc.iceGatheringState !== 'complete') {
                    pc.close();
                    resolve();
                }
            }, 5000);
        });
    }

    /**
     * Network Information API method
     */
    async detectViaNetworkAPI() {
        if ('connection' in navigator) {
            const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            
            if (connection) {
                // Get network type info
                const networkInfo = {
                    type: connection.type || 'unknown',
                    effectiveType: connection.effectiveType || 'unknown',
                    downlink: connection.downlink || 0,
                    rtt: connection.rtt || 0
                };
                
                this.addNetworkInfo(networkInfo);
            }
        }
    }

    /**
     * Fallback detection methods
     */
    async detectFallback() {
        // Try to get IP from server-side detection
        try {
            const response = await fetch('/api/get_client_info.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_ip_info',
                    user_agent: navigator.userAgent,
                    screen: {
                        width: screen.width,
                        height: screen.height,
                        colorDepth: screen.colorDepth
                    },
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    language: navigator.language
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.server_detected_ip) {
                    this.addLocalIP(data.server_detected_ip, 'Server-detected');
                }
            }
        } catch (error) {
            console.warn('Server IP detection failed:', error);
        }
    }

    /**
     * Add detected IP to list
     */
    addLocalIP(ip, source) {
        const ipInfo = {
            ip: ip,
            type: this.getIPType(ip),
            source: source,
            timestamp: new Date().toISOString(),
            isLocal: this.isLocalIP(ip),
            isPublic: this.isPublicIP(ip)
        };

        // Avoid duplicates
        const exists = this.localIPs.find(item => item.ip === ip);
        if (!exists) {
            this.localIPs.push(ipInfo);
            console.log('Detected local IP:', ipInfo);
        }
    }

    /**
     * Add network information
     */
    addNetworkInfo(networkInfo) {
        this.networkInfo = networkInfo;
    }

    /**
     * Validate IP address
     */
    isValidIP(ip) {
        // IPv4 validation
        const ipv4Regex = /^(\d{1,3}\.){3}\d{1,3}$/;
        if (ipv4Regex.test(ip)) {
            const parts = ip.split('.');
            return parts.every(part => parseInt(part) >= 0 && parseInt(part) <= 255);
        }

        // IPv6 validation (basic)
        const ipv6Regex = /^([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}$/i;
        const ipv6CompressedRegex = /^([0-9a-f]{0,4}:){1,7}:([0-9a-f]{0,4}:){0,6}[0-9a-f]{0,4}$/i;
        
        return ipv6Regex.test(ip) || ipv6CompressedRegex.test(ip) || ip === '::1';
    }

    /**
     * Get IP type (IPv4 or IPv6)
     */
    getIPType(ip) {
        if (ip.includes(':')) {
            return 'IPv6';
        } else if (ip.includes('.')) {
            return 'IPv4';
        }
        return 'Unknown';
    }

    /**
     * Check if IP is local/private
     */
    isLocalIP(ip) {
        if (ip.includes(':')) {
            // IPv6 local addresses
            return ip.startsWith('fe80:') || // Link-local
                   ip.startsWith('fc00:') || // Unique local
                   ip.startsWith('fd00:') || // Unique local
                   ip === '::1';             // Loopback
        } else {
            // IPv4 private ranges
            const parts = ip.split('.').map(Number);
            return (parts[0] === 10) ||                                    // 10.0.0.0/8
                   (parts[0] === 172 && parts[1] >= 16 && parts[1] <= 31) || // 172.16.0.0/12
                   (parts[0] === 192 && parts[1] === 168) ||               // 192.168.0.0/16
                   (parts[0] === 127) ||                                   // 127.0.0.0/8 (loopback)
                   (parts[0] === 169 && parts[1] === 254);                 // 169.254.0.0/16 (link-local)
        }
    }

    /**
     * Check if IP is public
     */
    isPublicIP(ip) {
        return this.isValidIP(ip) && !this.isLocalIP(ip);
    }

    /**
     * Register callback for IP detection completion
     */
    onIPDetected(callback) {
        this.callbacks.push(callback);
    }

    /**
     * Notify all callbacks
     */
    notifyCallbacks() {
        this.callbacks.forEach(callback => {
            try {
                callback(this.localIPs, this.networkInfo);
            } catch (error) {
                console.error('Callback error:', error);
            }
        });
    }

    /**
     * Get formatted IP information
     */
    getFormattedInfo() {
        const info = {
            localIPs: this.localIPs,
            networkInfo: this.networkInfo || {},
            summary: {
                totalIPs: this.localIPs.length,
                ipv4Count: this.localIPs.filter(ip => ip.type === 'IPv4').length,
                ipv6Count: this.localIPs.filter(ip => ip.type === 'IPv6').length,
                localCount: this.localIPs.filter(ip => ip.isLocal).length,
                publicCount: this.localIPs.filter(ip => ip.isPublic).length
            }
        };

        return info;
    }

    /**
     * Send detected IPs to server
     */
    async sendToServer(additionalData = {}) {
        const data = {
            action: 'save_local_ip',
            local_ips: this.localIPs,
            network_info: this.networkInfo,
            client_info: {
                user_agent: navigator.userAgent,
                screen: {
                    width: screen.width,
                    height: screen.height,
                    colorDepth: screen.colorDepth
                },
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                language: navigator.language,
                platform: navigator.platform,
                cookieEnabled: navigator.cookieEnabled,
                onLine: navigator.onLine
            },
            ...additionalData
        };

        try {
            const response = await fetch('/api/save_local_ip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (response.ok) {
                const result = await response.json();
                console.log('Local IP data sent to server:', result);
                return result;
            } else {
                console.error('Failed to send local IP data to server');
                return null;
            }
        } catch (error) {
            console.error('Error sending local IP data:', error);
            return null;
        }
    }
}

// Global instance
window.localIPDetector = new LocalIPDetector();

// Auto-detect on page load
document.addEventListener('DOMContentLoaded', function() {
    // Start detection after a short delay
    setTimeout(() => {
        window.localIPDetector.detectLocalIPs().then(ips => {
            console.log('Local IP detection completed:', ips);
            
            // Send to server if user is logged in
            if (document.body.classList.contains('logged-in') || 
                document.querySelector('.navbar-nav')) {
                window.localIPDetector.sendToServer();
            }
        });
    }, 1000);
});

// Utility functions for global access
window.getLocalIPs = function() {
    return window.localIPDetector.localIPs;
};

window.detectLocalIPs = function() {
    return window.localIPDetector.detectLocalIPs();
};

window.showLocalIPInfo = function() {
    const info = window.localIPDetector.getFormattedInfo();
    console.table(info.localIPs);
    return info;
};