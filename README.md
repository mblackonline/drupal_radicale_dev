# Drupal 11 + Radicale Calendar Development Template

A local development environment for Drupal 11 with integrated Radicale CalDAV server for calendar synchronization. Events added via CalDAV appear automatically in Drupal with real-time sync.

## Screenshots

**Drupal Welcome Page**
![Welcome Page](docs/images/welcome-page.png)

**Calendar View**
![Calendar View](docs/images/calendar-view.png)

**Radicale CalDAV Server**
![Radicale Interface](docs/images/radicale-interface.png)

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
   - **Click the "Advanced" drop down** and use the following settings: 
     - **Host**: 127.0.0.1
     - **Port**: 5432
4. Complete the installation wizard

## Access

### Local Access (from your development machine)

**Drupal Frontend:**
- Welcome Page: http://127.0.0.1:8000/welcome
- Calendar View: http://127.0.0.1:8000/calendar

**Radicale CalDAV Server:**
- URL: http://127.0.0.1:5232
- Username: `admin`
- Password: (leave empty)

### External Access (for testing CalDAV clients)

To test CalDAV clients from mobile devices or other computers:

1. **Get your computer's IP address:**
   ```bash
   ip address  # Linux/WSL
   ipconfig    # Windows CMD
   ```

2. **Enable firewall access:**
   
   **Linux Mint/Ubuntu:**
   ```bash
   sudo ufw allow 5232
   ```
   
   **WSL2 (PowerShell as Admin):**
   ```powershell
   # Replace [WSL2-IP] with IP from: ip addr show | grep eth0
   netsh interface portproxy add v4tov4 listenport=5232 listenaddress=0.0.0.0 connectport=5232 connectaddress=[WSL2-IP]
   New-NetFirewallRule -DisplayName "Radicale CalDAV" -Direction Inbound -Protocol TCP -LocalPort 5232 -Action Allow
   ```

3. **Connect from external device:**
   - Server: `http://[YOUR-COMPUTER-IP]:5232`
   - Username: `admin`
   - Password: (leave empty)

4. **Disable external access when done:**
   
   **Linux Mint/Ubuntu:**
   ```bash
   sudo ufw delete allow 5232
   ```
   
   **WSL2:**
   ```powershell
   netsh interface portproxy delete v4tov4 listenport=5232 listenaddress=0.0.0.0
   Remove-NetFirewallRule -DisplayName "Radicale CalDAV"
   ```

### Daily Development Workflow

```bash
# Start development session
cd drupal_radicale_dev
devenv shell
devenv up -d

# Check service status
devenv processes

# Clear Drupal cache
cd web 
./vendor/bin/drush cr 
cd ..

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
├── LICENSE
├── README.md
├── cleanup.sh                   # Environment cleanup script
├── devenv.lock                  # Locked Nix dependencies
├── devenv.nix                   # Nix environment configuration
├── devenv.yaml                  # Devenv metadata
├── docs/                        # Documentation assets
│   └── images/                  # Screenshots and images
│       ├── calendar-view.png
│       ├── radicale-interface.png
│       └── welcome-page.png
├── setup.sh                    # Initial setup script
└── web/                        # Drupal root directory
    ├── composer.json           # PHP dependencies configuration
    ├── recipes/                # Drupal recipes
    └── web/                    # Drupal document root
        ├── core/               # Drupal core (git-ignored)
        ├── modules/
        │   └── custom/
        │       └── radicale_calendar/  # Custom calendar integration module
        │           ├── config/
        │           ├── src/
        │           └── templates/
        │               └── radicale-welcome.html.twig
        ├── profiles/
        │   └── custom/
        │       └── radicale_starter/   # Custom installation profile
        ├── sites/
        │   └── default/        # Site configuration (git-ignored when populated)
        └── themes/
            └── custom/         # Custom themes directory
```

**Key Directories:**
- **`.devenv/`** - Devenv state including PostgreSQL data and Radicale storage (git-ignored)
- **`web/vendor/`** - Composer packages (git-ignored)
- **`docs/images/`** - Project screenshots and documentation images
- **`web/web/modules/custom/radicale_calendar/`** - Main integration module
- **`web/web/profiles/custom/radicale_starter/`** - Installation profile for quick setup

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
