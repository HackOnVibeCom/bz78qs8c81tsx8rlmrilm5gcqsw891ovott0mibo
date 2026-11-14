#!/usr/bin/env bash
# Run on the actual VPS: sudo bash scripts/activate-production.sh
set -euo pipefail
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

app_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
domain="cc.tendry.mg"

if [[ "${EUID}" -ne 0 ]]; then
    echo "Executez ce script sur le VPS avec sudo." >&2
    exit 1
fi
for dependency in php nginx curl setfacl runuser systemctl; do
    command -v "$dependency" >/dev/null || { echo "Commande manquante : $dependency" >&2; exit 1; }
done
[[ -f "$app_root/.env" && -f "$app_root/vendor/autoload.php" ]] || {
    echo "Le fichier .env et l'autoloader PHP doivent etre presents." >&2; exit 1;
}
[[ -f /etc/nginx/sites-enabled/challengechain.conf ]] || {
    echo "Le vhost /etc/nginx/sites-enabled/challengechain.conf est requis." >&2; exit 1;
}
cd -- "$app_root"
public_url="$(php -r '
require "bootstrap.php";
if (config("app.env") !== "production" || config("app.debug")
    || !in_array(config("app.url"), ["http://cc.tendry.mg", "https://cc.tendry.mg"], true)
    || !in_array("sqlite", PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "Verifier APP_ENV, APP_DEBUG, APP_URL et PDO SQLite.\n");
    exit(1);
}
echo config("app.url");
')"
if [[ "$public_url" == https://* ]]; then
    command -v certbot >/dev/null || { echo "Commande manquante : certbot" >&2; exit 1; }
fi
nginx -t

# Update the existing application vhost before checking the new hostname.
# Keep a backup and restore it if Nginx rejects the updated configuration.
site_config="$(readlink -f /etc/nginx/sites-enabled/challengechain.conf)"
site_backup="$(mktemp /var/backups/challengechain-nginx.XXXXXX)"
cp -p -- "$site_config" "$site_backup"
if ! php -r '
    $file = $argv[1];
    $domain = $argv[2];
    $updated = preg_replace_callback(
        "/^(\\h*server_name\\h+)[^;]+;/m",
        static fn (array $match): string => $match[1] . $domain . ";",
        file_get_contents($file),
        -1,
        $count
    );
    if ($updated === null || $count === 0 || file_put_contents($file, $updated) === false) {
        fwrite(STDERR, "Impossible de modifier le domaine du vhost.\n");
        exit(1);
    }
' "$site_config" "$domain" || ! nginx -t; then
    cp -p -- "$site_backup" "$site_config"
    echo "Configuration Nginx restauree : verifier le vhost." >&2
    exit 1
fi

# Allow the web worker to traverse the private home without listing it.
parent_dir="$app_root"
while [[ "$parent_dir" != / ]]; do
    setfacl -m u:www-data:--x "$parent_dir"
    parent_dir="$(dirname -- "$parent_dir")"
done
setfacl -m u:www-data:r-- "$app_root/.env"
mkdir -p storage/logs
setfacl -R -m u:www-data:rwX storage
find storage -type d -exec setfacl -m d:u:www-data:rwx {} +

# Preserve existing data; never run the demo seed script on this database.
runuser -u www-data -- php scripts/migrate.php
runuser -u www-data -- php -r '
require "bootstrap.php";
use ChallengeChain\Core\Database;
Database::transaction(static function (): void {
    Database::query("UPDATE schema_migrations SET migration = migration WHERE 0");
});
echo "Acces SQLite en lecture/ecriture OK.\n";
'
systemctl start php8.4-fpm nginx
systemctl reload nginx

check_health() {
    # A reload can return before the new workers accept the hostname.
    curl --fail --silent --show-error --location --noproxy '*' --max-time 10 \
        --retry 5 --retry-all-errors --retry-delay 1 --retry-max-time 30 \
        --resolve "$domain:80:127.0.0.1" \
        --resolve "$domain:443:127.0.0.1" "$1" |
        php -r '
            $health = json_decode(stream_get_contents(STDIN), true);
            if (($health["ok"] ?? false) !== true || ($health["app"] ?? "") !== "ChallengeChain") {
                fwrite(STDERR, "Le controle de disponibilite a echoue.\n");
                exit(1);
            }
        '
}
check_health "http://$domain/api/health"

if [[ "$public_url" == https://* ]]; then
    # HTTPS is activated only when explicitly configured in APP_URL.
    certbot --nginx --redirect --domain "$domain"
    nginx -t
    systemctl reload nginx
    check_health "$public_url/api/health"
fi

check_asset() {
    local content_type
    content_type="$(curl --fail --silent --show-error --location --noproxy '*' --max-time 10 \
        --retry 5 --retry-all-errors --retry-delay 1 --retry-max-time 30 \
        --resolve "$domain:80:127.0.0.1" \
        --resolve "$domain:443:127.0.0.1" \
        --output /dev/null --write-out '%{content_type}' "$public_url/assets/$1")" || return 1
    if [[ ! "$content_type" =~ $2 ]]; then
        echo "Type de contenu incorrect pour $1 : $content_type" >&2
        return 1
    fi
    echo "Fichier OK : $1"
}
for css in app.css theme.css; do check_asset "css/$css" '^text/css'; done
for js in icons.js app.js generate.js play.js; do
    check_asset "js/$js" '^(application|text)/(javascript|x-javascript)'
done
for illustration in friendly-rivalry.png quiz-time.png; do
    check_asset "illustrations/$illustration" '^image/png'
done
echo "Application, styles, scripts et images verifies sur $public_url"
