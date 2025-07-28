# Drupal 11 + Radicale Calendar Development Template

A local development environment for Drupal 11 with integrated Radicale CalDAV server for calendar synchronization. Events added via CalDAV appear automatically in Drupal with real-time sync.

## Features

- Drupal 11 with custom calendar module that subscribes to Radicale
- Radicale CalDAV server as the main source of truth for calendar events
- Real-time synchronization between Radicale and Drupal
- FullCalendar interface for calendar display
- PostgreSQL database pre-configured for Drupal

## Prerequisites

**Required Software:**
- Linux, macOS, or Windows with WSL2 (tested on WSL2 Ubuntu and Linux Mint)
- Git
- Nix Package Manager and devenv

## Setup Instructions

### 1. Clone and Install Dependencies

```bash
git clone <repository-url>
cd drupal_radicale_dev

# Install Nix (if not already installed)
curl -L https://nixos.org/nix/install | sh
source ~/.nix-profile/etc/profile.d/nix.sh

# Install devenv
nix profile add --accept-flake-config github:cachix/devenv/latest
```

### 2. Initialize Environment

```bash
# Make scripts executable
chmod +x setup.sh cleanup.sh

# Run initial setup
./setup.sh

# Enter development environment
devenv shell

# Install Drupal dependencies
cd web && composer install && cd ..

# Start all services
devenv up -d
```

### 3. Install Drupal

1. Open http://127.0.0.1:8000 in your browser
2. Choose "Radicale Calendar Starter" installation profile
3. Enter database credentials:
   - **Database type**: PostgreSQL
   - **Database name**: drupal
   - **Username**: drupaluser
   - **Password**: drupalpass
   - **Host**: 127.0.0.1
   - **Port**: 5432
4. Complete the installation wizard

### 4. Access Points

- **Welcome Page**: http://127.0.0.1:8000/welcome
- **Calendar View**: http://127.0.0.1:8000/calendar
- **Radicale Web UI**: http://127.0.0.1:5232 (username: `admin`, no password)

## Usage

### How It Works

Radicale serves as the main calendar server using the CalDAV protocol. Drupal subscribes to Radicale and displays events through a FullCalendar interface. Events can be added via:

- Radicale web interface at http://127.0.0.1:5232
- Any CalDAV client (mobile apps, desktop applications)
- Events automatically sync to Drupal

### CalDAV Client Configuration

**Connection Details:**
- **Server**: `http://[YOUR-COMPUTER-IP]:5232`
- **Username**: `admin`
- **Password**: (leave empty)

### Daily Development Workflow

```bash
# Start development session
cd drupal_radicale_dev
devenv shell
devenv up -d

# Check service status
devenv processes

# Clear Drupal cache
cd web && ../vendor/bin/drush cr && cd ..

# View logs if needed
devenv logs postgres
devenv logs radicale
devenv logs webserver

# End session
exit
```

### Reset Environment

```bash
# Exit devenv shell first
exit

# Run cleanup (terminal will freeze - this is normal)
./cleanup.sh

# Close terminal window completely, then open new terminal
cd drupal_radicale_dev
chmod +x setup.sh cleanup.sh
./setup.sh
devenv shell
cd web && composer install && cd ..
devenv up -d
# Reinstall Drupal at http://127.0.0.1:8000
```

## Network Access

### Linux Mint Users

**Enable external Radicale access:**
```bash
sudo ufw allow 5232
ip address  # Get your computer's IP
```

**Disable external Radicale access:**
```bash
sudo ufw delete allow 5232
```

### WSL2 Users

**Enable external Radicale access (PowerShell as Admin):**
```powershell
# Get WSL2 IP first: ip addr show | grep eth0
# Get Windows IP: ipconfig

netsh interface portproxy add v4tov4 listenport=5232 listenaddress=0.0.0.0 connectport=5232 connectaddress=[WSL2-IP]
New-NetFirewallRule -DisplayName "Radicale CalDAV" -Direction Inbound -Protocol TCP -LocalPort 5232 -Action Allow
```

**Disable external Radicale access:**
```powershell
netsh interface portproxy delete v4tov4 listenport=5232 listenaddress=0.0.0.0
Remove-NetFirewallRule -DisplayName "Radicale CalDAV"
```

## Configuration

### Radicale Settings

Configure the Radicale server connection in Drupal:
- **Location**: Configuration → System → Radicale Calendar Settings
- **URL**: `/admin/config/system/radicale-calendar`
- **Default Server**: `http://127.0.0.1:5232`
- **Default Username**: `admin`
- **Default Password**: (empty)

To modify defaults before installation, edit:
`web/web/modules/custom/radicale_calendar/config/install/radicale_calendar.settings.yml`

### Database Credentials

PostgreSQL development credentials (hardcoded):
- **Username**: `drupaluser`
- **Password**: `drupalpass`
- **Database**: `drupal`

**Security Note**: This setup has no authentication on Radicale for easy development. Do not use in production. Change database credentials for any non-development use.

## Project Structure

```
drupal_radicale_dev/
├── devenv.nix              # Nix environment configuration
├── setup.sh                # Initial setup script
├── cleanup.sh              # Reset environment script
├── web/                    # Drupal root
│   ├── composer.json       # PHP dependencies
│   ├── web/                # Drupal document root
│   │   ├── modules/custom/radicale_calendar/  # Custom calendar integration
│   │   └── profiles/custom/radicale_starter/   # Installation profile
│   └── vendor/             # Composer packages (git-ignored)
└── .devenv/                # Devenv state (git-ignored)
    └── state/
        ├── postgres/       # Database files
        └── radicale-data/  # Calendar storage
```

## Troubleshooting

**Permission denied on scripts:**
```bash
chmod +x setup.sh cleanup.sh
```

**Port conflicts:**
```bash
sudo lsof -i :8000
sudo lsof -i :5232
sudo lsof -i :5432
sudo pkill -f process-compose
```

**Services won't start:**
```bash
exit
devenv shell
devenv up -d
```

**Database connection failed:**
```bash
devenv processes
psql -h 127.0.0.1 -p 5432 -U drupaluser -d drupal
```

## Contributing

When contributing:
1. Create feature branches from this project
2. Never commit: `.devenv/`, `web/vendor/`, `web/composer.lock`, database dumps, `.env` files
3. Test with fresh clone before submitting PRs
4. Keep credentials and URLs generic

## Resources

- [Devenv Documentation](https://devenv.sh/)
- [Drupal 11 Documentation](https://www.drupal.org/docs)
- [Radicale Documentation](https://radicale.org/)
- [CalDAV Protocol](https://tools.ietf.org/html/rfc4791)

## License

MIT License - Copyright (c) 2025 Matt Black

---

**Need help?** Open an issue with your operating system, complete error messages, output of `devenv processes`, and steps to reproduce the problem.
