# GDGoC Certificates - XAMPP Deployment Guide

This document provides instructions for deploying the GDGoC Certificate Generation Platform on XAMPP for local development.

## Table of Contents

- [Prerequisites](#prerequisites)
- [XAMPP Installation](#xampp-installation)
- [PHP Configuration](#php-configuration)
- [Database Setup](#database-setup)
- [Application Setup](#application-setup)
- [Virtual Host Configuration](#virtual-host-configuration)
- [Mercury Mail Configuration](#mercury-mail-configuration-email-testing)
- [Running the Application](#running-the-application)
- [Troubleshooting](#troubleshooting)

## Prerequisites

- **XAMPP** 8.2+ (includes PHP 8.2+, Apache, MySQL)
- **Composer** 2.0+
- **Node.js** 20+
- **npm** 10+
- **Git**

## XAMPP Installation

### Windows

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Run the installer and select at least:
   - Apache
   - MySQL
   - PHP
   - phpMyAdmin
3. Install to `C:\xampp` (default location)
4. Open XAMPP Control Panel and start **Apache** and **MySQL**

### macOS

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Open the `.dmg` file and drag XAMPP to Applications
3. Open XAMPP from Applications and start **Apache** and **MySQL**

### Linux

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Run the installer:
   ```bash
   chmod +x xampp-linux-*-installer.run
   sudo ./xampp-linux-*-installer.run
   ```
3. Start XAMPP:
   ```bash
   sudo /opt/lampp/lampp start
   ```

## PHP Configuration

Laravel 11 requires specific PHP extensions. Ensure they are enabled in your `php.ini` file.

### Windows

Edit `C:\xampp\php\php.ini`

### macOS

Edit `/Applications/XAMPP/xamppfiles/etc/php.ini`

### Linux

Edit `/opt/lampp/etc/php.ini`

### Required Extensions

Uncomment (remove `;` from the beginning) the following lines:

```ini
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
```

**Restart Apache** after making changes.

## Database Setup

### 1. Access phpMyAdmin

Open your browser and go to: `http://localhost/phpmyadmin`

### 2. Create Database

1. Click **"New"** in the left sidebar
2. Enter database name: `gdgoc_certs`
3. Select collation: `utf8mb4_unicode_ci`
4. Click **"Create"**

### 3. Create Database User (Optional but Recommended)

1. Go to **"User accounts"** tab
2. Click **"Add user account"**
3. Configure:
   - User name: `gdgoc_user`
   - Host name: `localhost`
   - Password: Choose a secure password
4. Under **"Database for user account"**:
   - Check **"Grant all privileges on database gdgoc_certs"**
5. Click **"Go"**

## Application Setup

### 1. Clone the Repository

Navigate to XAMPP's `htdocs` directory:

**Windows:**
```bash
cd C:\xampp\htdocs
git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git
cd GDGoC-certs-v3
```

**macOS:**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs
git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git
cd GDGoC-certs-v3
```

**Linux:**
```bash
cd /opt/lampp/htdocs
git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git
cd GDGoC-certs-v3
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` file and update the database configuration for MySQL:

```env
APP_NAME="GDGoC Certs"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/GDGoC-certs-v3/public

# Database Configuration for XAMPP MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gdgoc_certs
DB_USERNAME=root
DB_PASSWORD=

# If you created a dedicated user:
# DB_USERNAME=gdgoc_user
# DB_PASSWORD=your_password

# Cache and Queue (use database for XAMPP)
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# Domain Configuration (for local development)
DOMAIN_PUBLIC=localhost
DOMAIN_ADMIN=localhost
VALIDATION_DOMAIN=localhost
```

### 3. Install PHP Dependencies

Ensure Composer is installed and run:

```bash
composer install
```

### 4. Install Node.js Dependencies

```bash
npm install
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Seed the Database

```bash
php artisan db:seed
```

### 8. Build Frontend Assets

For development (with hot reload):
```bash
npm run dev
```

For production build:
```bash
npm run build
```

## Virtual Host Configuration (Recommended)

For a cleaner URL (e.g., `http://gdgoc-certs.local`), configure a virtual host.

### 1. Enable Virtual Hosts in Apache

**Windows:** Edit `C:\xampp\apache\conf\httpd.conf`  
**macOS:** Edit `/Applications/XAMPP/xamppfiles/etc/httpd.conf`  
**Linux:** Edit `/opt/lampp/etc/httpd.conf`

Uncomment this line (remove `#`):
```apache
Include conf/extra/httpd-vhosts.conf
```

### 2. Configure Virtual Host

**Windows:** Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`  
**macOS:** Edit `/Applications/XAMPP/xamppfiles/etc/extra/httpd-vhosts.conf`  
**Linux:** Edit `/opt/lampp/etc/extra/httpd-vhosts.conf`

Add the following configuration:

**Windows:**
```apache
<VirtualHost *:80>
    ServerAdmin webmaster@gdgoc-certs.local
    DocumentRoot "C:/xampp/htdocs/GDGoC-certs-v3/public"
    ServerName gdgoc-certs.local
    
    <Directory "C:/xampp/htdocs/GDGoC-certs-v3/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/gdgoc-certs-error.log"
    CustomLog "logs/gdgoc-certs-access.log" common
</VirtualHost>
```

**macOS:**
```apache
<VirtualHost *:80>
    ServerAdmin webmaster@gdgoc-certs.local
    DocumentRoot "/Applications/XAMPP/xamppfiles/htdocs/GDGoC-certs-v3/public"
    ServerName gdgoc-certs.local
    
    <Directory "/Applications/XAMPP/xamppfiles/htdocs/GDGoC-certs-v3/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/gdgoc-certs-error.log"
    CustomLog "logs/gdgoc-certs-access.log" common
</VirtualHost>
```

**Linux:**
```apache
<VirtualHost *:80>
    ServerAdmin webmaster@gdgoc-certs.local
    DocumentRoot "/opt/lampp/htdocs/GDGoC-certs-v3/public"
    ServerName gdgoc-certs.local
    
    <Directory "/opt/lampp/htdocs/GDGoC-certs-v3/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/gdgoc-certs-error.log"
    CustomLog "logs/gdgoc-certs-access.log" common
</VirtualHost>
```

### 3. Update Hosts File

**Windows:** Edit `C:\Windows\System32\drivers\etc\hosts` (as Administrator)  
**macOS/Linux:** Edit `/etc/hosts` (with sudo)

Add:
```
127.0.0.1    gdgoc-certs.local
```

### 4. Update .env File

```env
APP_URL=http://gdgoc-certs.local
DOMAIN_PUBLIC=gdgoc-certs.local
DOMAIN_ADMIN=gdgoc-certs.local
VALIDATION_DOMAIN=gdgoc-certs.local
```

### 5. Restart Apache

Restart Apache from XAMPP Control Panel or via command line.

## Mercury Mail Configuration (Email Testing)

Mercury Mail is included with XAMPP (Windows only) and provides a local mail server for testing email functionality. This section covers how to configure Mercury for SMTP and IMAP to test the application's mailing system.

> **Note:** Mercury Mail is only available in the Windows version of XAMPP. For macOS/Linux, see the [Alternative: MailHog](#alternative-mailhog-macos--linux) section below.

### 1. Enable Mercury Mail in XAMPP

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Mercury**
3. If Mercury doesn't appear, you may need to reinstall XAMPP with Mercury selected

### 2. Configure Mercury Mail Server

1. Click **Admin** next to Mercury in XAMPP Control Panel (or open `C:\xampp\MercuryMail\MERCURY.EXE`)
2. The Mercury Mail Transport System window will open

#### Configure SMTP (Outgoing Mail)

1. Go to **Configuration** → **MercuryS SMTP Server**
2. Configure the following:
   - **Listen on TCP/IP port:** `25` (default) or `587`
   - **Announce myself as:** `localhost`
3. Under **Connection control** tab:
   - Check **"Allow relay for local addresses"**
   - Check **"Allow relay for authenticated users"**
4. Click **OK** to save

#### Configure POP3/IMAP (Incoming Mail)

**For POP3:**
1. Go to **Configuration** → **MercuryP POP3 Server**
2. Ensure **Listen on TCP/IP port** is set to `110`
3. Click **OK**

**For IMAP:**
1. Go to **Configuration** → **MercuryI IMAP4rev1 Server**
2. Ensure **Listen on TCP/IP port** is set to `143`
3. Click **OK**

### 3. Create a Test Mail User

1. In Mercury Mail, go to **Configuration** → **Manage local users**
2. Click **New**
3. Configure:
   - **Username:** `testuser`
   - **Personal name:** `Test User`
   - **Password:** Choose a password
4. Click **Add** and then **Close**

### 4. Configure Laravel .env for Mercury

Update your `.env` file with the following mail settings:

```env
# Mail Configuration for Mercury (XAMPP)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@localhost"
MAIL_FROM_NAME="${APP_NAME}"
```

If you enabled authentication in Mercury:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=25
MAIL_USERNAME=testuser
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="testuser@localhost"
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Test Email Sending

Use Laravel's Tinker to test sending an email:

```bash
php artisan tinker
```

Then run:

```php
Mail::raw('Test email from GDGoC Certs', function($message) {
    $message->to('testuser@localhost')->subject('Test Email');
});
```

### 6. View Received Emails

1. Open Mercury Mail Admin
2. Go to **File** → **Open mail folder** → Select user folder
3. Or configure an email client (Thunderbird, Outlook) with:
   - **IMAP Server:** `127.0.0.1` (Port `143`)
   - **POP3 Server:** `127.0.0.1` (Port `110`)
   - **SMTP Server:** `127.0.0.1` (Port `25`)
   - **Username:** `testuser`
   - **Password:** Your configured password

### Alternative: MailHog (macOS / Linux)

For macOS and Linux users, MailHog is recommended as Mercury is not available.

#### Install MailHog

**macOS (with Homebrew):**
```bash
brew install mailhog
```

**Linux:**
```bash
# Download the latest release
wget https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_linux_amd64
chmod +x MailHog_linux_amd64
sudo mv MailHog_linux_amd64 /usr/local/bin/mailhog
```

#### Run MailHog

```bash
mailhog
```

MailHog runs on:
- **SMTP Server:** `127.0.0.1:1025`
- **Web UI:** `http://localhost:8025`

#### Configure Laravel .env for MailHog

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@localhost"
MAIL_FROM_NAME="${APP_NAME}"
```

#### View Emails in MailHog

Open `http://localhost:8025` in your browser to see all captured emails.

### Alternative: Laravel Log Driver (All Platforms)

For quick testing without setting up a mail server, use Laravel's log driver:

```env
MAIL_MAILER=log
```

Emails will be written to `storage/logs/laravel.log` instead of being sent.

## Running the Application

### Without Virtual Host

Access the application at:
```
http://localhost/GDGoC-certs-v3/public
```

### With Virtual Host

Access the application at:
```
http://gdgoc-certs.local
```

### Default Admin Credentials

- **Email:** admin@example.com
- **Password:** password

**⚠️ Important:** Change these credentials immediately after first login!

### Start Development Server (Alternative)

If you prefer using Laravel's built-in development server:

```bash
php artisan serve
```

Access at: `http://localhost:8000`

## Troubleshooting

### 500 Internal Server Error

1. **Check Laravel logs:**
   ```bash
   # View the last 50 lines of the log
   tail -50 storage/logs/laravel.log
   ```

2. **Ensure storage permissions:**
   
   **Windows (run as Administrator in Command Prompt):**
   ```cmd
   icacls storage /grant Everyone:F /T
   icacls bootstrap\cache /grant Everyone:F /T
   ```
   
   **macOS/Linux:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R $USER:www-data storage bootstrap/cache
   ```
   
   > **Note:** If you encounter permission issues with the above, you may temporarily use `chmod -R 777 storage bootstrap/cache` for development purposes only. Never use 777 permissions in production.

3. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

### Database Connection Error

1. Verify MySQL is running in XAMPP Control Panel
2. Check database credentials in `.env`
3. Ensure the database `gdgoc_certs` exists
4. Try using `127.0.0.1` instead of `localhost` for `DB_HOST`

### Class Not Found Errors

Regenerate autoload files:
```bash
composer dump-autoload
```

### Asset Loading Issues

1. Ensure you've run `npm run build` or `npm run dev`
2. Check that `APP_URL` in `.env` matches your access URL
3. Clear view cache:
   ```bash
   php artisan view:clear
   ```

### Apache mod_rewrite Not Working

1. Ensure `mod_rewrite` is enabled in `httpd.conf`:
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

2. Ensure `AllowOverride All` is set for your directory

3. Restart Apache

### PHP Extension Missing

If you see errors about missing extensions:

1. Open `php.ini`
2. Search for the extension name
3. Uncomment the line (remove `;`)
4. Restart Apache

### Memory Limit Error

If you encounter memory errors during `composer install`:

1. Edit `php.ini`
2. Find and update:
   ```ini
   memory_limit = 512M
   ```
3. Restart Apache

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [XAMPP FAQ](https://www.apachefriends.org/faq_windows.html)
- [Composer Documentation](https://getcomposer.org/doc/)
- [Node.js Documentation](https://nodejs.org/docs/)

## Support

For issues or questions:
- Create an issue on GitHub
- Check the main [DEPLOYMENT.md](DEPLOYMENT.md) for Docker-based deployment
- Review Laravel logs: `storage/logs/laravel.log`
