#!/bin/bash

# Script de déploiement du Frontend Angular
# Usage: ./deploy-frontend.sh [--yes]

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
FRONTEND_LOCAL="frontend"
FRONTEND_REMOTE="race8462"

echo -e "${BLUE} Déploiement du Frontend Angular${NC}\n"

# Vérifier que nous sommes dans le bon répertoire
if [ ! -d "$FRONTEND_LOCAL" ]; then
    echo -e "${RED} Erreur: Le dossier 'frontend' n'existe pas${NC}"
    exit 1
fi

# Vérifier que npm est installé
if ! command -v npm &> /dev/null; then
    echo -e "${RED} npm n'est pas installé${NC}"
    exit 1
fi

# Demander confirmation (sauter si --yes est passé)
if [ "$1" != "--yes" ]; then
    echo -e "${YELLOW}  Ce script va:${NC}"
    echo -e "  1. Construire le frontend (npm run build)"
    echo -e "  2. Uploader les fichiers vers le serveur O2Switch"
    echo -e "  3. Écraser les fichiers existants sur le serveur\n"
    read -p "Continuer le déploiement ? (oui/non): " confirm
    if [ "$confirm" != "oui" ] && [ "$confirm" != "o" ] && [ "$confirm" != "O" ]; then
        echo -e "${RED} Déploiement annulé${NC}"
        exit 1
    fi
else
    echo -e "${GREEN} Confirmation automatique activée${NC}\n"
fi

# Étape 1: Build du frontend
echo -e "\n${YELLOW} Étape 1/3: Construction du frontend...${NC}"
cd "$FRONTEND_LOCAL"

if [ ! -f "package.json" ]; then
    echo -e "${RED} Erreur: package.json introuvable${NC}"
    exit 1
fi

# Installer les dépendances si nécessaire
if [ ! -d "node_modules" ]; then
    echo -e "${BLUE}📥 Installation des dépendances...${NC}"
    npm install
fi

# Build de production
echo -e "${BLUE}🔨 Build de production...${NC}"
npm run build

if [ ! -d "dist" ]; then
    echo -e "${RED} Erreur: Le dossier 'dist' n'a pas été créé${NC}"
    exit 1
fi

cd ..

# Étape 2: Upload vers le serveur
echo -e "\n${YELLOW}📤 Étape 2/3: Upload des fichiers vers le serveur...${NC}"

# Vérifier que lftp est installé
if ! command -v lftp &> /dev/null; then
    echo -e "${RED} lftp n'est pas installé. Veuillez l'installer:${NC}"
    echo -e "  macOS: brew install lftp"
    echo -e "  Linux: sudo apt-get install lftp"
    exit 1
fi

lftp -c "
set ftp:ssl-allow no
set ftp:passive-mode yes
set net:timeout 30
set net:max-retries 3
open -p ${FTP_PORT} -u '${FTP_USER}','${FTP_PASS}' ${FTP_HOST}
cd ${FRONTEND_REMOTE}
mirror -R --delete --verbose --exclude-glob='.git*' --exclude-glob='node_modules' --exclude-glob='.angular' --exclude-glob='*.map' ${FRONTEND_LOCAL}/dist/ ./
bye
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN} Upload réussi${NC}"
else
    echo -e "${RED} Erreur lors de l'upload${NC}"
    exit 1
fi

# Étape 3: Vérification
echo -e "\n${YELLOW}🔍 Étape 3/3: Vérification...${NC}"
echo -e "${BLUE}Test de l'API frontend...${NC}"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://academy.clouddevfusion.com" || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "301" ] || [ "$HTTP_CODE" = "302" ]; then
    echo -e "${GREEN} Frontend accessible (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${YELLOW}  Frontend retourne HTTP $HTTP_CODE (peut être normal)${NC}"
fi

echo -e "\n${GREEN} Déploiement du frontend terminé avec succès !${NC}"
echo -e "${BLUE} URL: https://academy.clouddevfusion.com${NC}\n"
