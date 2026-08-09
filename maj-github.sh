#!/bin/bash

# Récupère le message de commit passé en argument, ou utilise un message par défaut
COMMIT_MSG="${1:-Mise à jour automatique}"

echo "=== 🚀 Lancement de la mise à jour GitHub ==="

# 1. Ajout des modifications
echo "👉 Ajout des fichiers (git add)..."
git add .

# 2. Commit des modifications
echo "👉 Création du commit : \"$COMMIT_MSG\"..."
git commit -m "$COMMIT_MSG"

# 3. Détection de la branche active
BRANCH=$(git branch --show-current)
if [ -z "$BRANCH" ]; then
    BRANCH="main"
fi

# 4. Push vers GitHub
echo "👉 Envoi vers GitHub (branche : $BRANCH)..."
git push origin "$BRANCH"

echo "=== ✨ Terminé avec succès ! ==="
