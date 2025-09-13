# 🚀 Installation SGC-AgentOne sur Hébergement Mutualisé

## 📋 Instructions d'installation

### 1️⃣ Structure à créer sur votre hébergement

```
public_html/                    <- Votre racine web
├── .htaccess                  <- Copier depuis ce dossier
├── index.php                  <- Copier depuis ce dossier  
├── sgc-agentone/              <- Copier tout le dossier sgc-agentone
└── data/                      <- Créer pour la base de données
    └── .htaccess              <- Copier depuis ce dossier
```

### 2️⃣ Étapes d'installation

1. **Uploadez le dossier sgc-agentone complet** dans public_html/
2. **Copiez .htaccess** de ce dossier vers public_html/.htaccess
3. **Copiez index.php** de ce dossier vers public_html/index.php
4. **Créez le dossier data/** dans public_html/
5. **Copiez data/.htaccess** vers public_html/data/.htaccess

### 3️⃣ Permissions fichiers (via gestionnaire de fichiers)

- `public_html/` : 755
- `data/` : 755  
- `sgc-agentone/` : 755
- `.htaccess` : 644
- `index.php` : 644

### 4️⃣ Accès à l'application

- **Interface principale :** `https://votre-site.com/`
- **Chat direct :** `https://votre-site.com/sgc-agentone/extensions/vscode/src/webview/chat.html`
- **API :** `https://votre-site.com/api/chat`

### 5️⃣ Test de compatibilité

Créez un fichier `test-sqlite.php` :

```php
<?php
if (class_exists('SQLite3')) {
    echo "✅ SQLite3 disponible";
} else {
    echo "❌ Contactez votre hébergeur pour activer SQLite3";
}
?>
```

## ✅ L'installation est prête !

SGC-AgentOne fonctionnera exactement comme sur Replit, avec toutes ses fonctionnalités :
- Chat intelligent multilingue
- Gestionnaire de fichiers  
- Éditeur Monaco intégré
- Base de données SQLite
- 7 vues complètes

🎯 **Aucune modification de code nécessaire - juste copier-coller !**