#!/bin/bash

# Active le "pipefail" : si une commande dans un pipe échoue, le code de sortie du pipe sera celui de la commande échouée
set -o pipefail

# Variables d'environnement qui seront fournies par docker-compose
DB_HOST=${DB_HOST}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}

# Dossier de sauvegarde à l'intérieur du conteneur
BACKUP_DIR="/backups"

# Format de la date pour le nom du fichier
DATE_FORMAT=$(date +"%Y-%m-%d_%H-%M-%S")

# Nom complet du fichier de sauvegarde
BACKUP_FILE="$BACKUP_DIR/${DB_NAME}_${DATE_FORMAT}.sql.gz"

# Message de log
echo "Début de la sauvegarde de la base de données '$DB_NAME' vers $BACKUP_FILE..."

# Commande mysqldump - la version officielle de l'image mysql:8.0
# On compresse la sortie à la volée avec gzip pour économiser de l'espace
mysqldump -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME | gzip > $BACKUP_FILE

# Vérification du succès de la commande
if [ $? -eq 0 ]; then
  echo "Sauvegarde réussie."
else
  echo "ERREUR : La sauvegarde a échoué."
  exit 1
fi

# Suppression des sauvegardes de plus de 7 jours
echo "Suppression des sauvegardes de plus de 7 jours..."
find $BACKUP_DIR -type f -name "*.sql.gz" -mtime +7 -exec rm {} \;

echo "Opération de sauvegarde terminée."
