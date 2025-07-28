# Drupal 11 + Radicale Calendar Development Template

A local development environment for **Drupal 11** with integrated **Radicale CalDAV server** for calendar synchronization. This template provides a working calendar application with real-time sync between Radicale and Drupal.

## 🎯 Features

- **Drupal 11** with custom calendar module that subscribes to Radicale
- **Radicale CalDAV server** as the main source of truth for calendar events
- **Real-time sync** - Events added via CalDAV appear automatically in Drupal
- **FullCalendar** interface for a  nicer looking calendar display
- **PostgreSQL** database pre-configured for Drupal

## 📋 Prerequisites

### Required Software
- **Operating System**: Linux, macOS, or Windows with WSL2 (NOTE: this guide has only been tested on WSL2 Ubuntu and Linux Mint). 
- **Git** (to clone this repository)
- **Nix Package Manager and devenv ** (installation instructions below)

## 🚀 Quick Start Guide

### 1. Clone the Repository
```bash
git clone <repository-url>
cd drupal_radicale_dev
```

### 2. Install Nix (if not already installed)
```bash
# Install Nix
curl -L https://nixos.org/nix/install | sh

# Reload your shell configuration
source ~/.nix-profile/etc/profile.d/nix.sh

# Install devenv
nix profile add --accept-flake-config github:cachix/devenv/latest
```

### 3. Make Scripts Executable (IMPORTANT!)
```bash
chmod +x setup.sh cleanup.sh
```

### 4. Run Initial Setup
```bash
./setup.sh
```

### 5. Enter Development Environment
```bash
devenv shell
```
Your prompt will change to indicate you're in the development environment.

### 6. Install Drupal Dependencies
```bash
cd web && composer install && cd ..
```

### 7. Start All Services
```bash
devenv up -d
```
This starts PostgreSQL, Radicale, and the PHP development server.

### 8. Install Drupal
1. Open your browser to **http://127.0.0.1:8000**
2. Choose **"Radicale Calendar Starter"** installation profile
3. Enter database credentials when prompted:
   - **Database type**: PostgreSQL
   - **Database name**: drupal
   - **Username**: drupaluser
   - **Password**: drupalpass
   - **Host**: 127.0.0.1
   - **Port**: 5432
4. Complete the installation wizard

### 9. Access Your Calendar System
- **Welcome Page**: http://127.0.0.1:8000/welcome
- **Calendar View**: http://127.0.0.1:8000/calendar
- **Radicale Web UI**: http://127.0.0.1:5232
  - Username: `admin` (no password required)

## 📅 Using the Calendar System

### How It Works
1. **Radicale** is the main calendar server (CalDAV)
2. **Drupal** subscribes to Radicale and displays events
3. Events can be added via:
   - Radicale web interface
   - Any CalDAV client (mobile apps, desktop clients)
   - Events sync automatically to Drupal

### Adding Events via CalDAV Clients

#### Web Browser
- Visit http://127.0.0.1:5232
- Login with username `admin` (no password)
- Create calendars and add events

#### Mobile Devices (iOS/Android)
- Add CalDAV account in your device settings
- Server: `http://[YOUR-COMPUTER-IP]:5232`
- Username: `admin`
- Password: (leave empty)

#### Desktop Applications
- Thunderbird, Outlook, etc. with CalDAV support
- Use same connection details as mobile

## 🔧 Development Workflow

### Daily Development
```bash
# Start your day
cd drupal_radicale_dev
devenv shell
devenv up -d

# Check service status
devenv processes

# View logs if needed
devenv logs postgres
devenv logs radicale
devenv logs webserver

# Clear Drupal cache
cd web
../vendor/bin/drush cr
cd ..

# Stop working
exit  # Exit devenv shell
```

### Reset Everything (Start Fresh)
```bash
# Exit devenv shell first
exit

# Run cleanup
./cleanup.sh

# Terminal will freeze - this is normal!
# Close the terminal window completely

# Open new terminal
cd drupal_radicale_dev
chmod +x setup.sh cleanup.sh  # Scripts lose permissions
./setup.sh
devenv shell
cd web && composer install && cd ..
devenv up -d
# Reinstall Drupal at http://127.0.0.1:8000
```

## 🌐 Accessing from Other Devices

### For Linux Mint Users

To access Radicale from other devices on your network:

#### Enable External Access
1. Allow incoming connections on port5232:
   ```bash
   sudo ufw allow 5232
   ```
2. Find your computer's IP address:
   ```bash
   ip address
   ```
3. Use your computer's IP address to connect to Radicale from other devices (e.g., `http://Computer-IP:5232`)

#### To Disable External Access To Radicale When You Are Done Testing
1. Remove the firewall rule:
   ```bash
   sudo ufw delete allow 5232
   ```

### For WSL2 Users

If using WSL2 and need to access Radicale from phones/tablets on your network:

### Enable External Radicale Access
1. Get WSL2 IP: `ip addr show | grep eth0`
2. Get Windows IP: Open CMD and run `ipconfig`
3. Setup port forwarding (PowerShell as Admin):
```powershell
netsh interface portproxy add v4tov4 listenport=5232 listenaddress=0.0.0.0 connectport=5232 connectaddress=[WSL2-IP]
New-NetFirewallRule -DisplayName "Radicale CalDAV" -Direction Inbound -Protocol TCP -LocalPort 5232 -Action Allow
```

### Disable External Radicale Access
```powershell
netsh interface portproxy delete v4tov4 listenport=5232 listenaddress=0.0.0.0
Remove-NetFirewallRule -DisplayName "Radicale CalDAV"
```

## 📁 Project Structure

```
drupal_radicale_dev/
├── devenv.nix              # Nix environment configuration
├── devenv.yaml             # Devenv metadata
├── devenv.lock             # Locked dependencies
├── setup.sh                # Initial setup script
├── cleanup.sh              # Reset environment script
├── web/                    # Drupal root
│   ├── composer.json       # PHP dependencies
│   ├── web/                # Drupal document root
│   │   ├── modules/
│   │   │   └── custom/
│   │   │       └── radicale_calendar/  # Custom calendar integration
│   │   ├── profiles/
│   │   │   └── custom/
│   │   │       └── radicale_starter/   # Installation profile
│   │   └── sites/
│   └── vendor/             # Composer packages (git-ignored)
└── .devenv/                # Devenv state (git-ignored)
    └── state/
        ├── postgres/       # Database files
        └── radicale-data/  # Calendar storage
```

## ⚙️ Configuration

### Radicale Server Settings
The Radicale server URL is configurable in Drupal:
1. Go to **Configuration** → **System** → **Radicale Calendar Settings**
2. Or edit directly at `/admin/config/system/radicale-calendar`
3. Default settings:
   - Server URL: `http://127.0.0.1:5232`
   - Username: `admin`
   - Password: (empty)

To change these defaults before installation, edit:
`web/web/modules/custom/radicale_calendar/config/install/radicale_calendar.settings.yml`

```

### Security Warning
This setup has **no authentication on Radicale** for easy development. **DO NOT use in production!**

### Database Credentials
The PostgreSQL credentials are hardcoded for development:
- Username: `drupaluser`
- Password: `drupalpass`
- Database: `drupal`

**Change these for any non-development use!**

## 🐛 Troubleshooting

### Permission Denied on Scripts
```bash
chmod +x setup.sh cleanup.sh
```

### Port Already in Use
```bash
# Check what's using ports
sudo lsof -i :8000
sudo lsof -i :5232
sudo lsof -i :5432

# Kill if needed
sudo pkill -f process-compose
```

### Services Won't Start
```bash
# Exit and re-enter environment
exit
devenv shell
devenv up -d
```

### Database Connection Failed
```bash
# Check PostgreSQL is running
devenv processes

# Test connection
psql -h 127.0.0.1 -p 5432 -U drupaluser -d drupal
```

## 🤝 Contributing

When contributing:
1. Create a new feature branch from this project
2. Never commit:
   - `.devenv/` directory
   - `web/vendor/`
   - `web/composer.lock`
   - Database dumps
   - `.env` files
3. Test with a fresh clone before submitting PRs
4. Keep credentials and URLs generic (no hardcoded IPs/paths)

## 📚 Additional Resources

- [Devenv Documentation](https://devenv.sh/)
- [Drupal 11 Documentation](https://www.drupal.org/docs)
- [Radicale Documentation](https://radicale.org/)
- [CalDAV Protocol](https://tools.ietf.org/html/rfc4791)

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Copyright (c) 2025 Matt Black

---

**Need help?** Open an issue with:
- Your operating system
- Complete error messages
- Output of `devenv processes`
- Steps to reproduce the problem
