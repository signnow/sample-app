#!/usr/bin/env bash

apt autoremove -y >/dev/null 2>&1 || true
apt clean -y >/dev/null 2>&1 || true
apt autoclean -y >/dev/null 2>&1 || true

rm -rf \
	/app/storage/framework/views/* \
	/app/storage/app/public/* \
	/app/storage/logs/* \
	/app/storage/framework/cache/* \
	/var/lib/apt/lists/* \
	/tmp/* \
	/var/tmp/* \
	/var/cache \
	/etc/nginx/sites-enabled/default

echo "Cleanup complete."
exit 0
