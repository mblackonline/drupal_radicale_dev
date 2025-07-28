{ pkgs, config, ... }: {
  packages = [ pkgs.postgresql pkgs.radicale ];
  
  languages = {
    php = {
      enable = true;
      version = "8.3";
      packages.composer = pkgs.php83Packages.composer;
    };
  };
  
  services = {
    postgres = {
      enable = true;
      listen_addresses = "127.0.0.1";
      port = 5432;
      initialDatabases = [];
      initialScript = ''
        CREATE USER drupaluser WITH LOGIN PASSWORD 'drupalpass' CREATEDB SUPERUSER;
        CREATE DATABASE drupal WITH OWNER drupaluser;
        \c drupal;
        GRANT ALL ON SCHEMA public TO drupaluser;
        GRANT CREATE ON SCHEMA public TO drupaluser;
        ALTER DATABASE drupal SET bytea_output = 'escape';
      '';
    };

  };
  
  processes = {
    radicale = {
      exec = "${pkgs.radicale}/bin/radicale --config ${pkgs.writeText "radicale.cfg" ''
        [server]
        hosts = 0.0.0.0:5232
        
        [auth]
        type = none
        
        [rights]
        type = owner_only
        
        [storage]
        filesystem_folder = ${config.env.DEVENV_STATE}/radicale-data
      ''}";
    };
    webserver = {
      exec = "php -S 127.0.0.1:8000 -t web/web";
    };
  };
}
