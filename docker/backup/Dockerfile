# Étape 1: Définir une image de base standard et fiable
FROM debian:bullseye-slim

# Métadonnées de l'image
LABEL maintainer="Ecoride backup"
LABEL description="Conteneur pour sauvegardes automatiques de la base de données MySQL."

# Variables pour la configuration du dépôt MySQL
ENV MYSQL_APT_CONFIG_VERSION=0.8.29-1

# Étape 2: Installer le client MySQL 8 officiel depuis les dépôts MySQL
# On ajoute lsb-release qui est une pré-dépendance pour le paquet mysql-apt-config
# On ajoute aussi BASH pour notre script
RUN apt-get update && apt-get install -y --no-install-recommends gnupg wget ca-certificates lsb-release bash && \
    # Télécharger le paquet de configuration du dépôt MySQL
    wget https://dev.mysql.com/get/mysql-apt-config_${MYSQL_APT_CONFIG_VERSION}_all.deb && \
    # Installer le paquet (en mode non-interactif)
    DEBIAN_FRONTEND=noninteractive dpkg -i mysql-apt-config_${MYSQL_APT_CONFIG_VERSION}_all.deb && \
    # Mettre à jour la liste des paquets qui inclut maintenant ceux de MySQL
    apt-get update && \
    # Installer le client MySQL et cron
    apt-get install -y --no-install-recommends mysql-community-client cron && \
    # Nettoyer
    rm -f mysql-apt-config_${MYSQL_APT_CONFIG_VERSION}_all.deb && \
    rm -rf /var/lib/apt/lists/*

# Étape 3: Créer un répertoire pour notre application
RUN mkdir /app
WORKDIR /app

# Étape 4: Copier les fichiers nécessaires dans l'image
COPY backup.sh .
RUN chmod +x backup.sh

COPY crontab /etc/cron.d/backup-cron
RUN chmod 0644 /etc/cron.d/backup-cron

# Étape 5: Définir la commande à exécuter au démarrage du conteneur
CMD ["cron", "-f"]
