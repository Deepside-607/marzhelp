#!/bin/bash

set -e

# Function to read environment variables
read_env_variable() {
    local var_name="$1"
    local env_file="$2"
    grep -E "^\s*${var_name}=" "$env_file" | grep -v '^\s*#' | head -n1 | cut -d'=' -f2- | tr -d '"'
    
}

# Function to ensure the script runs as root
ensure_root() {
    if [ "$(id -u)" -ne 0 ]; then
        echo "Please run the script as root."
         read -p "Press Enter to exit..."
        exit 1
    fi
}

# Function to display colored messages
display_message() {
    local color="$1"
    local message="$2"
    echo -e "\033[${color}m${message}\033[0m"
}

# Function to retrieve or prompt for the MySQL root password
get_mysql_root_password() {
    local env_file="/opt/marzban/.env"
    local password=""
    local config_file="/root/marzhelp.txt"
 

    if [ -f "$env_file" ]; then
        password=$(read_env_variable "MYSQL_ROOT_PASSWORD" "$env_file")
    fi

    while [ -z "$password" ]; do
        if [ -f "$env_file" ]; then
            echo -e "\033[1;33mWarning:\033[0m MYSQL_ROOT_PASSWORD is empty in \033[1;34m$env_file\033[0m."
        else
            echo -e "\033[1;33mWarning:\033[0m .env file not found at \033[1;34m$env_file\033[0m."
        fi
        read -p $'\033[1;36mEnter the MySQL root password: \033[0m' password
        echo
        if [ -z "$password" ]; then
            echo -e "\033[1;31mError:\033[0m MySQL root password cannot be empty. Please try again."
        fi
    done

    echo -e "\033[1;32mSuccess:\033[0m MySQL root password: \033[1;34m$password\033[0m"
    # Save the password to /root/marzhelp.txt
    echo -e "\033[1;32mSuccess:\033[0m MySQL root password set and saved to \033[1;34m$config_file\033[0m"
    
    # Save or update MYSQL_ROOT_PASSWORD in the config file
    if grep -q "^MYSQL_ROOT_PASSWORD=" "$config_file"; then
        sed -i "s/^MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=$password/" "$config_file"
    else
        echo "MYSQL_ROOT_PASSWORD=$password" >> "$config_file"
    fi

  
read -p "Press Enter to continue..."
}
cat_config_with_color() {
    config_file="/root/marzhelp.txt"

    # Check if the file exists
    if [ -f "$config_file" ]; then
        
        
        # Use sed to colorize the values after '='
        while IFS='=' read -r key value; do
            echo -e "\033[1;37m$key=\033[1;32m$value\033[0m"
        done < "$config_file"
    else
        echo -e "\033[1;31mError: $config_file not found.\033[0m"
    fi
}

check_marzhelp_config() {
    # File path
    marzhelp_file="/root/marzhelp.txt"

    # Initialize variables
    botToken=""
    adminIds=""
    botDomain=""
    serverIP=""
    allowedUsers=""

    # Check if the marzhelp.txt file exists
    if [ -f "$marzhelp_file" ]; then
        echo -e "\033[1;34mChecking if all required fields are present in marzhelp.txt...\033[0m"

        # Read the file and extract key-value pairs
        while IFS='=' read -r key value; do
            case "$key" in
                bot-api) botToken="$value" ;;
                admin-id) adminIds="$value" ;;
                domain) botDomain="$value" ;;
                ip) serverIP="$value" ;;
                allowed_users) allowedUsers="$value" ;;
            esac
        done < "$marzhelp_file"

        # If any of the fields are missing, prompt the user to provide them
        if [ -z "$botToken" ]; then
            echo -e "\033[1;31mError: bot-api missing in marzhelp.txt. Please provide it.\033[0m"
            read -p "Enter the Telegram bot token: " botToken
            if [ -z "$botToken" ]; then
                echo -e "\033[1;31mError: Telegram bot token cannot be empty. Exiting.\033[0m"
                exit 1
            fi
        fi

        if [ -z "$adminIds" ]; then
            echo -e "\033[1;31mError: admin-id missing in marzhelp.txt. Please provide it.\033[0m"
            read -p "Enter the admin user ID(s) (comma separated, e.g., 123456,789012): " adminIds
            if [ -z "$adminIds" ]; then
                echo -e "\033[1;31mError: Admin user ID(s) cannot be empty. Exiting.\033[0m"
                exit 1
            fi
        fi

        if [ -z "$botDomain" ]; then
            echo -e "\033[1;31mError: domain missing in marzhelp.txt. Please provide it.\033[0m"
            read -p "Please enter a new domain for Marzhelp (e.g., example.com). Ensure it's a new domain. " botDomain
            if [ -z "$botDomain" ]; then
                echo -e "\033[1;31mError: Domain cannot be empty. Exiting.\033[0m"
                exit 1
            fi
        fi

        if [ -z "$serverIP" ]; then
            echo -e "\033[1;31mError: ip missing in marzhelp.txt. Please provide it.\033[0m"
            # Automatically detect the server's public IPv4 address
            serverIP=$(curl -s http://checkip.amazonaws.com)
            read -p "Enter your server's public IP address (default: $serverIP): " inputServerIP
            if [ -z "$inputServerIP" ]; then
                serverIP="$serverIP"
            else
                serverIP="$inputServerIP"
            fi
        fi

        # Format allowed_users based on adminIds
        IFS=',' read -r -a adminIdsArray <<< "$adminIds"
        allowedUsers=$(printf ",%s" "${adminIdsArray[@]}")
        allowedUsers=${allowedUsers:1}  # Remove leading comma

        # Save the updated values back to the file (overwriting it)
        echo -e "\033[1;32mUpdated marzhelp.txt with the required fields.\033[0m"
        echo "bot-api=$botToken" > "$marzhelp_file"
        echo "admin-id=$adminIds" >> "$marzhelp_file"
        echo "domain=$botDomain" >> "$marzhelp_file"
        echo "ip=$serverIP" >> "$marzhelp_file"
        echo "allowed_users=$allowedUsers" >> "$marzhelp_file"

    else
        # If the file doesn't exist, prompt for all the values
        echo -e "\033[1;33mmarzhelp.txt not found. Please provide the information.\033[0m"

        read -p "Enter the Telegram bot token: " botToken
        if [ -z "$botToken" ]; then
            echo -e "\033[1;31mError: Telegram bot token cannot be empty. Exiting.\033[0m"
            exit 1
        fi

        read -p "Enter the admin user ID(s) separated by commas (e.g., 123456,789012): " adminIds
        if [ -z "$adminIds" ]; then
            echo -e "\033[1;31mError: Admin user ID(s) cannot be empty. Exiting.\033[0m"
            exit 1
        fi

        read -p "Please enter a new domain for Marzhelp (e.g., example.com). Ensure it's a new domain. " botDomain
        if [ -z "$botDomain" ]; then
            echo -e "\033[1;31mError: Domain cannot be empty. Exiting.\033[0m"
            exit 1
        fi

        # Automatically detect the server's public IPv4 address
        serverIP=$(curl -s http://checkip.amazonaws.com)
        read -p "Enter your server's public IP address (default: $serverIP): " inputServerIP
        if [ -z "$inputServerIP" ]; then
            serverIP="$serverIP"
        else
            serverIP="$inputServerIP"
        fi

        # Set allowed_users to the value of adminIds
        allowedUsers="$adminIds"

        # Save the values to marzhelp.txt
        echo -e "\033[1;32mInformation saved to marzhelp.txt\033[0m"
        echo "bot-api=$botToken" > "$marzhelp_file"
        echo "admin-id=$adminIds" >> "$marzhelp_file"
        echo "domain=$botDomain" >> "$marzhelp_file"
        echo "ip=$serverIP" >> "$marzhelp_file"
        echo "allowed_users=$allowedUsers" >> "$marzhelp_file"
    fi
}
# Function to install and configure Nginx
install_nginx() {
    echo -e "\033[1;34m=== Checking Nginx Installation ===\033[0m"
    
    handle_apache_conflicts

    if ! dpkg -l | grep -q "^ii  nginx"; then
        echo -e "\033[1;33mNginx is not installed. Proceeding with installation...\033[0m"
        
        # Update package lists
        echo -e "\033[1;34mUpdating package lists...\033[0m"
        if ! apt update -y; then
            echo -e "\033[1;31mError: Failed to update package lists.\033[0m"
            return 1
        fi

        # Install dependencies and Nginx
        echo -e "\033[1;34mInstalling required packages...\033[0m"
        if ! apt install -y software-properties-common; then
            echo -e "\033[1;31mError: Failed to install required dependencies.\033[0m"
            return 1
        fi

        # Add PHP repository
        echo -e "\033[1;34mAdding PHP repository...\033[0m"
        if ! add-apt-repository -y ppa:ondrej/php; then
            echo -e "\033[1;31mError: Failed to add PHP repository.\033[0m"
            return 1
        fi

        # Update package lists again
        if ! apt update -y; then
            echo -e "\033[1;31mError: Failed to update package lists after adding repository.\033[0m"
            return 1
        fi

        # Install Nginx and related packages
        echo -e "\033[1;34mInstalling Nginx, PHP, and related packages...\033[0m"
        if ! apt install -y nginx php php-cli php-fpm php-curl php-mbstring php-xml php-zip php-soap \
            libssh2-1-dev libssh2-1 git wget unzip curl certbot python3-certbot-nginx ufw; then
            echo -e "\033[1;31mError: Failed to install Nginx and required packages.\033[0m"
            return 1
        fi

        # Enable and start Nginx
        echo -e "\033[1;34mEnabling and starting Nginx...\033[0m"
        systemctl enable nginx && systemctl start nginx

        # Allow HTTP traffic in UFW
        echo -e "\033[1;34mConfiguring firewall to allow HTTP traffic...\033[0m"
        if ! ufw allow 80; then
            echo -e "\033[1;31mError: Failed to configure UFW for HTTP traffic.\033[0m"
            return 1
        fi

        # Handle conflicts with Apache (if defined)
        if declare -f handle_apache_conflicts > /dev/null; then
            handle_apache_conflicts
        else
            echo -e "\033[1;33mWarning:\033[0m \033[1;31mhandle_apache_conflicts function is not defined.\033[0m"
        fi

        echo -e "\033[1;32mNginx installation and configuration completed successfully.\033[0m"
    else
        echo -e "\033[1;32mNginx is already installed.\033[0m"
    fi
}


# Function to handle conflicts with Apache and ensure Nginx runs alone on port 80
handle_apache_conflicts() {
    echo -e "\033[1;34mChecking for Apache conflicts...\033[0m"

    if systemctl is-active --quiet apache2; then
        echo -e "\033[1;31mApache is running. Stopping Apache...\033[0m"
        
        systemctl stop apache2
        
        if systemctl is-active --quiet apache2; then
            echo -e "\033[1;31mApache did not stop correctly. Forcing shutdown...\033[0m"
            pkill -9 apache2  
            sleep 2  
        fi

        systemctl disable apache2
    else
        echo -e "\033[1;32mApache is not running.\033[0m"
    fi

    if lsof -i :80 | grep -q 'apache2'; then
        echo -e "\033[1;31mPort 80 is still in use by Apache. Killing processes...\033[0m"
        pkill -9 apache2
        sleep 2
    else
        echo -e "\033[1;32mPort 80 is free.\033[0m"
    fi
}



edit_domain_ip() {
    # Check if marzhelp.txt exists and read from it
    if [ -f "/root/marzhelp.txt" ]; then
        echo -e "\033[1;34mReading domain and IP from marzhelp.txt...\033[0m"
        
        # Initialize variables
        botDomain=""
        serverIP=""
        
        # Read the file line by line and check for required fields
        while IFS='=' read -r key value; do
            case "$key" in
                domain) botDomain="$value" ;;
                ip) serverIP="$value" ;;
            esac
        done < /root/marzhelp.txt
        
        # Always prompt for domain and IP, but use existing values as defaults
        echo -e "\033[1;32mCurrent domain:\033[0m $botDomain"
        read -p "Please enter a new domain for Marzhelp (default: $botDomain): " inputDomain
        if [ -z "$inputDomain" ]; then
            inputDomain="$botDomain"
        fi
        
        echo -e "\033[1;32mCurrent IP:\033[0m $serverIP"
        read -p "Enter your server's public IP address (default: $serverIP): " inputServerIP
        if [ -z "$inputServerIP" ]; then
            inputServerIP="$serverIP"
        fi
        
        # Update marzhelp.txt without clearing the entire file
        # Overwrite or append the domain and IP lines
        sed -i "s/^domain=.*/domain=$inputDomain/" /root/marzhelp.txt
        sed -i "s/^ip=.*/ip=$inputServerIP/" /root/marzhelp.txt

        # If the lines weren't found (for example, if they didn't exist), append them
        grep -q "^domain=" /root/marzhelp.txt || echo "domain=$inputDomain" >> /root/marzhelp.txt
        grep -q "^ip=" /root/marzhelp.txt || echo "ip=$inputServerIP" >> /root/marzhelp.txt

        echo -e "\033[1;32mDomain and IP saved to /root/marzhelp.txt\033[0m"
    else
        # If the file doesn't exist, always ask for domain and IP
        echo -e "\033[1;31mmarzhelp.txt not found, please provide the information.\033[0m"
        
        # Prompt for domain with no default value
        read -p "Please enter a new domain for Marzhelp (e.g., example.com). Ensure it's a new domain. " inputDomain
        if [ -z "$inputDomain" ]; then
            echo -e "\033[1;31mError: Domain cannot be empty. Exiting.\033[0m"
            exit 1
        fi
        
        # Automatically detect the server's public IPv4 address
        serverIP=$(curl -s http://checkip.amazonaws.com)
        
        # Prompt for server IP with the current value as default
        echo -e "\033[1;32mDetected server IP:\033[0m $serverIP"
        read -p "Enter your server's public IP address (default: $serverIP): " inputServerIP
        if [ -z "$inputServerIP" ]; then
            inputServerIP="$serverIP"
        fi
        
        # Validate that the server IP is not empty
        if [ -z "$inputServerIP" ]; then
            echo -e "\033[1;31mError: Server IP cannot be empty. Exiting.\033[0m"
            exit 1
        fi
        
        # Save the values to marzhelp.txt
        echo "domain=$inputDomain" > /root/marzhelp.txt
        echo "ip=$inputServerIP" >> /root/marzhelp.txt
        echo -e "\033[1;32mDomain and IP saved to /root/marzhelp.txt\033[0m"
    fi
}

configure_nginx_ssl() {
    handle_apache_conflicts

    # Check if SSL certificate already exists
    if [ -f "/etc/letsencrypt/live/$botDomain/fullchain.pem" ]; then
        read -p "SSL certificate for $botDomain already exists. Do you want to renew it? (y/n): " renew_choice
        if [[ "$renew_choice" =~ ^[Yy]$ ]]; then
            echo "Renewing SSL certificate for $botDomain..."
            certbot renew --cert-name "$botDomain"
            if [ $? -ne 0 ]; then
                echo "SSL renewal failed. Please check Certbot logs."
                exit 1
            else
                echo "SSL certificate for $botDomain has been renewed successfully."
            fi
        else
            echo "Skipping SSL renewal."
        fi
    else
        echo "Obtaining SSL certificate for $botDomain..."
        certbot certonly --nginx --agree-tos --no-eff-email --redirect --hsts --staple-ocsp --preferred-challenges http -d "$botDomain" --http-01-port 80
        if [ ! -f "/etc/letsencrypt/live/$botDomain/fullchain.pem" ]; then
            echo "SSL certificate not issued. Check Certbot logs."
            exit 1
        fi
        echo "SSL certificate obtained successfully."
    fi

    # Proceed with Nginx configuration
    echo "Configuring Nginx to run on port 88 with SSL..."

    # Detect installed PHP version
    php_version=$(php -v | grep -oP '^PHP \K[0-9]+\.[0-9]+' | head -1)
    if [ -z "$php_version" ]; then
        echo "Could not detect PHP version. Please ensure PHP is installed."
        exit 1
    fi
    echo "Detected PHP version: $php_version"

    # Modify Nginx configuration to listen on port 88 with SSL enabled
    sed -i 's/listen 80 default_server;/listen 88 ssl default_server;/' /etc/nginx/sites-available/default
    sed -i 's/listen \[::\]:80 default_server;/listen \[::\]:88 ssl default_server;/' /etc/nginx/sites-available/default

    # Remove existing SSL configuration to avoid duplication
    sed -i '/ssl_certificate /d' /etc/nginx/sites-available/default
    sed -i '/ssl_certificate_key /d' /etc/nginx/sites-available/default
    sed -i '/ssl_protocols /d' /etc/nginx/sites-available/default
    sed -i '/ssl_ciphers /d' /etc/nginx/sites-available/default

    # Add the SSL configuration and PHP handler after the listen directive
    sudo sed -i "/listen \[::\]:88 ssl default_server;/a \
ssl_certificate /etc/letsencrypt/live/$botDomain/fullchain.pem;\n\
ssl_certificate_key /etc/letsencrypt/live/$botDomain/privkey.pem;\n\
ssl_protocols TLSv1.2 TLSv1.3;\n\
ssl_ciphers HIGH:!aNULL:!MD5;\n\
location ~ \.php$ {\n\
    include snippets/fastcgi-php.conf;\n\
    fastcgi_pass unix:/var/run/php/php${php_version}-fpm.sock;\n\
}" /etc/nginx/sites-available/default

    # Restart Nginx to apply the changes
    systemctl restart nginx

    echo "Nginx SSL configuration completed. Nginx restarted."
}

configure_telegram_bot() {
    echo -e "\033[1;34mStarting Telegram bot configuration...\033[0m"

    # Initialize variables to store existing bot token, admin ID, and allowed users
    currentBotToken=""
    currentAdminIds=""
    allowedUsers=""

    # Check if marzhelp.txt exists and read the existing values
    if [ -f "/root/marzhelp.txt" ]; then
        echo -e "\033[1;32mmarzhelp.txt found. Reading Telegram bot token, admin IDs, and allowed users from it...\033[0m"

        # Read the file line by line and check for required fields
        while IFS='=' read -r key value; do
            case "$key" in
                bot-api) currentBotToken="$value" ;;
                admin-id) currentAdminIds="$value" ;;
                allowed_users) allowedUsers="$value" ;;
            esac
        done < /root/marzhelp.txt

        # Display the existing bot token, admin ID, and allowed users if found
        if [ -n "$currentBotToken" ]; then
            echo -e "\033[1;33mExisting Bot Token: \033[0m$currentBotToken"
        else
            echo -e "\033[1;31mBot Token not found in marzhelp.txt.\033[0m"
        fi

        if [ -n "$currentAdminIds" ]; then
            echo -e "\033[1;33mExisting Admin User ID: \033[0m$currentAdminIds"
        else
            echo -e "\033[1;31mAdmin User ID not found in marzhelp.txt.\033[0m"
        fi

        if [ -n "$allowedUsers" ]; then
            echo -e "\033[1;33mExisting Allowed Users: \033[0m$allowedUsers"
        else
            echo -e "\033[1;31mAllowed Users not found in marzhelp.txt.\033[0m"
        fi
    else
        echo -e "\033[1;31mError: marzhelp.txt not found. Exiting.\033[0m"
        exit 1
    fi

    # Ask for new bot token, admin user ID, and allowed users if not already configured
    read -p "Enter the Telegram bot token (leave empty to keep existing): " botToken
    if [ -z "$botToken" ] && [ -z "$currentBotToken" ]; then
        echo -e "\033[1;31mError: Telegram bot token cannot be empty. Exiting.\033[0m"
        exit 1
    elif [ -z "$botToken" ]; then
        botToken="$currentBotToken"
    fi

    read -p "Enter the admin user ID (leave empty to keep existing): " adminIds
    if [ -z "$adminIds" ] && [ -z "$currentAdminIds" ]; then
        echo -e "\033[1;31mError: Admin user ID cannot be empty. Exiting.\033[0m"
        exit 1
    elif [ -z "$adminIds" ]; then
        adminIds="$currentAdminIds"
    fi

    # Format allowed users (adminIds) as comma-separated list
    IFS=',' read -r -a adminIdsArray <<< "$adminIds"
    allowed_users_formatted=$(printf ",%s" "${adminIdsArray[@]}")
    allowed_users_formatted=${allowed_users_formatted:1}

    # Save the bot token, admin ID, and allowed users to marzhelp.txt, overwriting only existing lines
    echo -e "\033[1;32mSaving the new bot token, admin ID, and allowed users to /root/marzhelp.txt...\033[0m"

    # Use sed to overwrite the bot-api, admin-id, and allowed_users lines, or add them if not present
    sed -i "s/^bot-api=.*/bot-api=$botToken/" /root/marzhelp.txt || echo "bot-api=$botToken" >> /root/marzhelp.txt
    sed -i "s/^admin-id=.*/admin-id=$adminIds/" /root/marzhelp.txt || echo "admin-id=$adminIds" >> /root/marzhelp.txt
    sed -i "s/^allowed_users=.*/allowed_users=$allowed_users_formatted/" /root/marzhelp.txt || echo "allowed_users=$allowed_users_formatted" >> /root/marzhelp.txt

    echo -e "\033[1;32mTelegram bot token, admin ID, and allowed users saved to /root/marzhelp.txt\033[0m"
    read -p "Press Enter to continue..."
}



# Function to install required PHP packages
install_php_packages() {
    local php_version
    php_version=$(php -v | grep -oP 'PHP \K[0-9]+\.[0-9]+' | head -1)

    local required_packages=(
        "php${php_version}-mysql"
        "php${php_version}-curl"
        "php${php_version}-mbstring"
        "php${php_version}-xml"
        "php${php_version}-zip"
        "php${php_version}-soap"
    )

    for package in "${required_packages[@]}"; do
        if dpkg -l | grep -q "^ii  $package "; then
            echo "$package is already installed."
        else
            echo "Installing $package..."
            apt-get install --no-install-recommends -y "$package"
        fi
    done

    # Check if Apache is installed and active, then disable it
if dpkg -l | grep -q "^ii  apache2"; then
    if systemctl is-active --quiet apache2; then
        echo -e "\033[1;33mApache detected and running. Disabling...\033[0m"
        systemctl stop apache2
        systemctl disable apache2
    else
        echo -e "\033[1;32mApache is installed but not running. No action needed.\033[0m"
    fi
    apt remove --purge -y apache2
else
    echo -e "\033[1;32mApache is not installed. Skipping...\033[0m"
fi
}
install_marzhelp() {

    # Check if mysql-client is installed
    if ! command -v mysql &> /dev/null; then
        echo "MySQL client not found. Installing mysql-client..."
        sudo apt update && sudo apt install mysql-client -y
    fi

    local config_file="/root/marzhelp.txt"

    # Read values from the config file
    botToken=$(grep "^bot-api=" "$config_file" | cut -d'=' -f2)
    adminID=$(grep "^admin-id=" "$config_file" | cut -d'=' -f2)
    botDomain=$(grep "^domain=" "$config_file" | cut -d'=' -f2)
    serverIP=$(grep "^ip=" "$config_file" | cut -d'=' -f2)
    MYSQL_ROOT_PASSWORD=$(grep "^MYSQL_ROOT_PASSWORD=" "$config_file" | cut -d'=' -f2)
    allowedUsers=$(grep "^allowed_users=" "$config_file" | cut -d'=' -f2)

    # Ensure all required values are set
    if [ -z "$MYSQL_ROOT_PASSWORD" ] || [ -z "$botToken" ] || [ -z "$allowedUsers" ] || [ -z "$botDomain" ] || [ -z "$serverIP" ] || [ -z "$adminID" ]; then
        echo "Error: Required values (MYSQL_ROOT_PASSWORD, botToken, allowedUsers, botDomain, serverIP, or adminID) are not set. Exiting."
        exit 1
    fi

    vpnDbName="marzban"

    # Create database if it doesn't exist
    mysql -h 127.0.0.1 -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS marzhelp;"

    # Create the user_deletions table in the Marzban database
    mysql -h 127.0.0.1 -u root -p"$MYSQL_ROOT_PASSWORD" "$vpnDbName" <<EOF
CREATE TABLE IF NOT EXISTS user_deletions (
  user_id int DEFAULT NULL,
  admin_id int DEFAULT NULL,
  deleted_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  used_traffic bigint DEFAULT NULL,
  reseted_usage bigint DEFAULT NULL
);
EOF

    # Remove any existing Marzhelp directory from /var/www/html
    if [ -d "/var/www/html/marzhelp" ]; then
        rm -rf /var/www/html/marzhelp
    fi

    echo "Cloning Marzhelp repository from GitHub..."
    git clone https://github.com/ppouria/marzhelp.git /var/www/html/marzhelp

    # Define commands and permissions to apply
    commands=(
        "sudo chown -R www-data:www-data /var/www/html/marzhelp/"
        "sudo chmod -R 755 /var/www/html/marzhelp/"
        "sudo chown www-data:www-data /usr/local/bin/marzban"
        "sudo chmod +x /usr/local/bin/marzban"
        "sudo chmod +x /usr/bin/crontab"
        "sudo chmod +x /usr/bin/wget"
    )

    # Loop over each command and execute
    for cmd in "${commands[@]}"; do
        echo "Executing: $cmd"
        eval "$cmd"
    done

    # Add www-data to sudoers for Marzhelp commands
    permissions=(
    "www-data ALL=(ALL) NOPASSWD: /usr/local/bin/marzban"
    "www-data ALL=(ALL) NOPASSWD: /usr/bin/crontab"
    "www-data ALL=(ALL) NOPASSWD: /usr/bin/wget"
        )

    for prm in "${permissions[@]}"; do
    sudo bash -c "grep -qxF '$prm' /etc/sudoers.d/marzhelp || echo '$prm' >> /etc/sudoers.d/marzhelp"
    done

    # Write the config.php file with bot token and database credentials
    cat <<EOL > /var/www/html/marzhelp/config.php
<?php
\$botToken = '$botToken';
\$apiURL = "https://api.telegram.org/bot\$botToken/";
\$botdomain = '$botDomain';

\$allowedUsers = [$allowedUsers];

\$botDbHost = '127.0.0.1';
\$botDbUser = 'root';
\$botDbPass = '$MYSQL_ROOT_PASSWORD';
\$botDbName = 'marzhelp';

\$vpnDbHost = '127.0.0.1'; 
\$vpnDbUser = 'root';
\$vpnDbPass = '$MYSQL_ROOT_PASSWORD';
\$vpnDbName = '$vpnDbName';

\$marzbanUrl = 'https://your-marzban-server.com'; 
\$marzbanAdminUsername = 'your_admin_username';  
\$marzbanAdminPassword = 'your_admin_password';  
?>
EOL

    # Execute table.php if it exists
    if [ -f "/var/www/html/marzhelp/table.php" ]; then
        php /var/www/html/marzhelp/table.php
        echo "table.php executed successfully."
    else
        echo "table.php not found."
    fi

    # Set webhook for the bot using the domain and IP
    curl "https://api.telegram.org/bot$botToken/deleteWebhook"
    curl "https://api.telegram.org/bot$botToken/setWebhook?url=https://$botDomain:88/marzhelp/webhook.php&ip_address=$serverIP&max_connections=40"

    # Restart Nginx to apply changes
    systemctl restart nginx

    # Display successful message
    echo "Webhook set to https://$botDomain:88/marzhelp/webhook.php"
    echo "Marzhelp has been successfully installed."
}

# Function to uninstall Marzhelp and remove related cron jobs, files, and Nginx
uninstall_marzhelp() {
    display_message "1;34" "=== Uninstalling Marzhelp ==="

    display_message "1;33" "Removing Cron jobs related to Marzhelp..."

    # Find and remove cron jobs containing /var/www/html/marzhelp/
    crontab -l | grep "/var/www/html/marzhelp/" | while read -r cron_job; do
        # Remove cron job that matches
        (crontab -l | grep -v "$cron_job") | crontab -
        display_message "1;32" "Removed Cron job: $cron_job"
    done

    display_message "1;33" "Removing Marzhelp directory from /var/www/html/..."
    if [ -d "/var/www/html/marzhelp" ]; then
        rm -rf /var/www/html/marzhelp
        display_message "1;32" "Marzhelp directory removed successfully."
    else
        display_message "1;31" "Error: Marzhelp directory not found."
    fi

    display_message "1;33" "Removing Nginx..."
    if sudo apt remove --purge -y nginx nginx-common; then
        display_message "1;32" "Nginx removed successfully."
    else
        display_message "1;31" "Error: Failed to remove Nginx."
    fi

    display_message "1;32" "Marzhelp uninstallation completed."
}

remove_nginx() {
                echo -e "\033[1;33mRemoving Nginx...\033[0m"
                if sudo apt remove --purge -y nginx nginx-common; then
                    echo -e "\033[1;32mNginx removed successfully.\033[0m"
                else
                    echo -e "\033[1;31mError: Failed to remove Nginx.\033[0m"
                fi
 }               

# Function to display the menu
display_menu() {
    clear
    server_ip=$(curl -s http://checkip.amazonaws.com)  
    uptime_info=$(uptime -p)  
    github_link="https://github.com/ppouria/marzhelp"  

    echo -e "\033[1;36m=======MarzHelp=======\033[0m"
    echo -e "\033[1;32mServer IP: \033[1;37m$server_ip\033[0m"  
    echo -e "\033[1;32mUptime: \033[1;37m$uptime_info\033[0m"  
    echo -e "\033[1;32mGitHub Project: \033[1;37m$github_link\033[0m"  

    echo -e "\033[1;33m1. \033[1;37mInstall Nginx\033[0m"
    echo -e "\033[1;33m2. \033[1;37mConfigure Nginx with SSL\033[0m"
    echo -e "\033[1;33m3. \033[1;37mEdit bot domain\033[0m"
    echo -e "\033[1;33m4. \033[1;37mEdit telegram_bot\033[0m"
    echo -e "\033[1;33m5. \033[1;37mInstall PHP Packages\033[0m"
    echo -e "\033[1;33m6. \033[1;37mInstall marzhelp\033[0m"
    echo -e "\033[1;33m7. \033[1;37mRemove nginx\033[0m"
    echo -e "\033[1;33m8. \033[1;37mManage nginx\033[0m"
    echo -e "\033[1;33m9. \033[1;37mManage UFW\033[0m"
    echo -e "\033[1;33m10. \033[1;37mStart full setup\033[0m"
    echo -e "\033[1;33m11. \033[1;37mUninstall Marzhelp\033[0m"   
    echo -e "\033[1;33m0. \033[1;37mExit\033[0m"
}

# Main function
main() {
    ensure_root
    while true; do
        display_menu
        read -p "Select an option: " choice
        case $choice in
            1) install_nginx ;;
            2) configure_nginx_ssl ;;
            3) edit_domain_ip ;;
            4) configure_telegram_bot ;;
            5) install_php_packages ;;
            6) install_marzhelp ;;
            7) remove_nginx ;;
            8) curl -Ls https://github.com/Mmdd93/v2ray-assistance/raw/refs/heads/main/nginx.sh -o nginx.sh; sudo bash nginx.sh ;;
            9) curl -Ls https://raw.githubusercontent.com/Mmdd93/v2ray-assistance/main/ufw.sh -o ufw.sh; sudo bash ufw.sh ;;
            10) install_nginx; install_php_packages; configure_nginx_ssl; install_marzhelp ;;
            11) uninstall_marzhelp ;;   # New case for uninstalling Marzhelp
            0) echo "Exiting..."; exit 0 ;;
            *) echo "Invalid option. Please try again." ;;
        esac
    done
}

ensure_root
read_env_variable
check_marzhelp_config
cat_config_with_color
get_mysql_root_password
main
