#!/bin/bash

# Script de déploiement du Backend Symfony
# Usage: ./deploy-backend.sh [--yes]

set -e

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration FTP O2Switch
FTP_HOST="academy.clouddevfusion.com"
FTP_PORT="21"
FTP_USER="race8462@academy.clouddevfusion.com"
FTP_PASS='@!tK%b2k46bZF2e&gcU4WyA3'

# Chemins
BACKEND_LOCAL="backend"
BACKEND_REMOTE="race8462/backend"

echo -e "${BLUE} Déploiement du Backend Symfony${NC}\n"

# Vérifier que nous sommes dans le bon répertoire
if [ ! -d "$BACKEND_LOCAL" ]; then
    echo -e "${RED} Erreur: Le dossier 'backend' n'existe pas${NC}"
    exit 1
fi

# Vérifier que composer est installé
if ! command -v composer &> /dev/null; then
    echo -e "${RED} Composer n'est pas installé${NC}"
    exit 1
fi

# Demander confirmation (sauter si --yes est passé)
if [ "$1" != "--yes" ]; then
    echo -e "${YELLOW}  Ce script va:${NC}"
    echo -e "  1. Uploader les fichiers backend (sans vendor, var, .env)"
    echo -e "  2. Uploader vendor (seulement les nouveaux fichiers)"
    echo -e "  3. Vérifier la configuration${NC}\n"
    read -p "Continuer le déploiement ? (oui/non): " confirm
    if [ "$confirm" != "oui" ] && [ "$confirm" != "o" ] && [ "$confirm" != "O" ]; then
        echo -e "${RED} Déploiement annulé${NC}"
        exit 1
    fi
else
    echo -e "${GREEN} Confirmation automatique activée${NC}\n"
fi

# Vérifier que lftp est installé
if ! command -v lftp &> /dev/null; then
    echo -e "${RED} lftp n'est pas installé. Veuillez l'installer:${NC}"
    echo -e "  macOS: brew install lftp"
    echo -e "  Linux: sudo apt-get install lftp"
    exit 1
fi

# Étape 1: Upload des fichiers backend (sans vendor, var, .env)
echo -e "\n${YELLOW} Étape 1/3: Upload des fichiers backend...${NC}"

lftp -c "
set ftp:ssl-allow no
set ftp:passive-mode yes
set net:timeout 30
set net:max-retries 3
open -p ${FTP_PORT} -u '${FTP_USER}','${FTP_PASS}' ${FTP_HOST}
cd ${BACKEND_REMOTE}
mirror -R --only-newer --verbose \
    --exclude-glob='.git*' \
    --exclude-glob='.env*' \
    --exclude-glob='var/' \
    --exclude-glob='vendor/' \
    --exclude-glob='node_modules/' \
    --exclude-glob='*.log' \
    --exclude-glob='.DS_Store' \
    ${BACKEND_LOCAL}/ ./
bye
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN} Fichiers backend uploadés${NC}"
else
    echo -e "${RED} Erreur lors de l'upload des fichiers${NC}"
    exit 1
fi

# Étape 2: Upload de vendor (seulement les nouveaux fichiers)
echo -e "\n${YELLOW} Étape 2/3: Upload de vendor (seulement nouveaux fichiers)...${NC}"

if [ ! -d "$BACKEND_LOCAL/vendor" ]; then
    echo -e "${YELLOW}  Le dossier vendor n'existe pas localement${NC}"
    echo -e "${BLUE} Exécutez 'composer install' dans le dossier backend avant de déployer${NC}"
else
    lftp -c "
    set ftp:ssl-allow no
    set ftp:passive-mode yes
    set net:timeout 30
    set net:max-retries 3
    open -p ${FTP_PORT} -u '${FTP_USER}','${FTP_PASS}' ${FTP_HOST}
    cd ${BACKEND_REMOTE}/vendor
    mirror -R --only-newer --verbose \
        --exclude-glob='.git*' \
        ${BACKEND_LOCAL}/vendor/ ./
    bye
    "
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN} Vendor uploadé${NC}"
    else
        echo -e "${YELLOW}  Erreur lors de l'upload de vendor (peut être normal si déjà à jour)${NC}"
    fi
fi

# Étape 3: Vérification
echo -e "\n${YELLOW} Étape 3/3: Vérification...${NC}"
echo -e "${BLUE}Test de l'API backend...${NC}"

# Tester l'endpoint /api/courses (route publique)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://academy.clouddevfusion.com/api/courses" || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN} API backend accessible (HTTP $HTTP_CODE)${NC}"
elif [ "$HTTP_CODE" = "401" ] || [ "$HTTP_CODE" = "403" ]; then
    echo -e "${YELLOW}  API retourne HTTP $HTTP_CODE (authentification requise, normal)${NC}"
elif [ "$HTTP_CODE" = "500" ]; then
    echo -e "${RED}  API retourne HTTP 500 (erreur serveur, vérifiez les logs)${NC}"
    echo -e "${BLUE} Vous pouvez vider le cache via:${NC}"
    echo -e "   https://academy.clouddevfusion.com/backend/public/clear-cache-complete.php"
else
    echo -e "${YELLOW}  API retourne HTTP $HTTP_CODE${NC}"
fi

echo -e "\n${GREEN} Déploiement du backend terminé !${NC}"
echo -e "${BLUE} URL API: https://academy.clouddevfusion.com/api${NC}"
echo -e "${YELLOW}  Note: Assurez-vous que le fichier .env est configuré sur le serveur${NC}"
echo -e "${YELLOW}  Note: Exécutez les migrations si nécessaire${NC}\n"
