#!/bin/sh
cd /var/www/html

# fix key if needed
if [ -z "$APP_KEY" ]
then
  echo "Please re-run this container with an environment variable \$APP_KEY"
  echo "An example APP_KEY you could use is: "
  php artisan key:generate --show
  exit
fi

# create data directories
for dir in 'data/private_uploads' 'data/uploads' 'data/uploads/avatars' 'data/uploads/barcodes' 'data/uploads/models' 'data/uploads/suppliers' 'dumps'; do
	mkdir -p "/var/lib/snipeit/$dir"
done

. /etc/apache2/envvars
exec apache2 -DNO_DETACH < /dev/null
