#!/bin/bash
# Script pour vider le cache Symfony via terminal
# À exécuter dans le terminal O2Switch

cd /home/race8462/academy.clouddevfusion.com/race8462/backend

echo "🧹 Vidage du cache Symfony..."

# Vider le cache
php bin/console cache:clear --env=prod --no-warmup

echo ""
echo "✅ Cache vidé !"
echo ""
echo "📋 Testez maintenant l'API:"
echo "  curl https://academy.clouddevfusion.com/api/exam_vouchers"
