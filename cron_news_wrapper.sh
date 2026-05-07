#!/bin/bash
#set -x  # Включаем режим отладки - будет видно каждую команду

cd /home/sanapostgr/nazproj

# Запускаем команду
ddev exec php web/cron_news.php
