# Cart API Directory Security and Configuration
# TechSavvyGenLtd Project

# Enable URL rewriting
RewriteEngine On

# Security headers for API responses
<IfModule mod_headers.c>
    # Prevent content type sniffing
    Header always set X-Content-Type-Options nosniff
    
    # Prevent clickjacking
    Header always set X-Frame-Options SAMEORIGIN
    
    # XSS protection
    Header always set X-XSS-Protection "1; mode=block"
    
    # Content Security Policy for API
    Header always set Content-Security-Policy "default-src 'self'; script-src 'none'; object-src 'none';"
    
    # API-specific headers
    Header always set X-API-Version "1.0.0"
    Header always set X-Powered-By "TechSavvyGenLtd-Cart-API"
    
    # CORS headers (adjust as needed)
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, X-Requested-With, Authorization, X-CSRF-Token"
    Header always set Access-Control-Max-Age "3600"
</IfModule>

# Handle preflight OPTIONS requests
RewriteCond %{REQUEST_METHOD} ^OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Deny access to sensitive files
<FilesMatch "\.(log|ini|conf|bak|old|tmp)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent direct access to PHP includes/config files if any
<FilesMatch "^(config|include|lib).*\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Rate limiting (if mod_evasive is available)
<IfModule mod_evasive24.c>
    DOSHashTableSize    1024
    DOSPageCount        10
    DOSPageInterval     1
    DOSSiteCount        50
    DOSSiteInterval     1
    DOSBlockingPeriod   300
</IfModule>

# Compression for JSON responses
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/json
    AddOutputFilterByType DEFLATE text/plain
</IfModule>

# Cache control for API responses
<IfModule mod_expires.c>
    ExpiresActive On
    
    # Don't cache API responses by default
    ExpiresByType application/json "access plus 0 seconds"
    
    # Cache static documentation for a short time
    <FilesMatch "index\.php$">
        ExpiresDefault "access plus 5 minutes"
    </FilesMatch>
</IfModule>

# Logging (if you want separate logs for cart API)
<IfModule mod_log_config.c>
    LogFormat "%h %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\" %D" cart_api
    # CustomLog logs/cart_api.log cart_api
</IfModule>

# Error pages
ErrorDocument 400 /api/errors/400.json
ErrorDocument 401 /api/errors/401.json
ErrorDocument 403 /api/errors/403.json
ErrorDocument 404 /api/errors/404.json
ErrorDocument 405 /api/errors/405.json
ErrorDocument 500 /api/errors/500.json

# PHP settings for API
<IfModule mod_php.c>
    # Disable PHP errors in production
    php_flag display_errors Off
    php_flag display_startup_errors Off
    
    # Log errors instead
    php_flag log_errors On
    
    # Increase memory limit for cart operations
    php_value memory_limit 128M
    
    # Set execution time limit
    php_value max_execution_time 30
    
    # Input size limits
    php_value max_input_vars 1000
    php_value post_max_size 2M
    php_value upload_max_filesize 0
    
    # Session settings
    php_value session.cookie_httponly 1
    php_value session.cookie_secure 1
    php_value session.use_strict_mode 1
</IfModule>

# Deny access to version control and other sensitive directories
RedirectMatch 404 /\.git
RedirectMatch 404 /\.svn
RedirectMatch 404 /\.hg
RedirectMatch 404 /\.bzr

# Block common attack patterns
<IfModule mod_rewrite.c>
    RewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]
    RewriteCond %{QUERY_STRING} GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]
    RewriteCond %{QUERY_STRING} _REQUEST(=|\[|\%[0-9A-Z]{0,2}) [OR]
    RewriteCond %{QUERY_STRING} \.\./\.\./\.\./\.\./\.\./\.\./ [OR]
    RewriteCond %{QUERY_STRING} ^.*(\(|\)|<|>|%3c|%3e).* [NC,OR]
    RewriteCond %{QUERY_STRING} ^.*(\\x00|\\x04|\\x08|\\x0d|\\x1b|\\x20|\\x3c|\\x3e|\\x7f).* [NC]
    RewriteRule ^(.*)$ - [F,L]
</IfModule>

# File upload protection (though cart API shouldn't handle uploads)
<IfModule mod_rewrite.c>
    RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK)
    RewriteRule .* - [F]
</IfModule>

# Enable file type verification
<IfModule mod_mime.c>
    AddType application/json .json
    AddType text/plain .txt
    AddType text/plain .log
</IfModule>