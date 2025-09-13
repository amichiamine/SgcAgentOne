# 🚀 Installation SGC-AgentOne sur Hébergement Mutualisé

## 📋 Instructions d'installation

### 1️⃣ Structure à créer sur votre hébergement

```
public_html/
└── sgc-agentone/              <- Tout SGC-AgentOne dans ce dossier
    ├── .htaccess              <- Copier depuis deployment/shared-hosting/
    ├── index.php              <- Copier depuis deployment/shared-hosting/
    ├── data/                  <- Créer pour la base de données
    │   └── .htaccess          <- Protection base données
    ├── core/                  <- Code SGC-AgentOne existant
    ├── extensions/            <- Interface SGC-AgentOne existante
    └── ...                    <- Reste du code SGC-AgentOne
```

### 2️⃣ Étapes d'installation

1. **Uploadez le dossier sgc-agentone complet** dans public_html/sgc-agentone/
2. **Dans le dossier sgc-agentone/** copiez :
   - `.htaccess` depuis deployment/shared-hosting/.htaccess
   - `index.php` depuis deployment/shared-hosting/index.php
3. **Créez le dossier sgc-agentone/data/**
4. **Copiez data/.htaccess** vers sgc-agentone/data/.htaccess

### 3️⃣ Permissions fichiers (via gestionnaire de fichiers)

- `sgc-agentone/` : 755
- `sgc-agentone/data/` : 755  
- `sgc-agentone/core/` : 755
- `.htaccess` : 644
- `index.php` : 644

### 4️⃣ Accès à l'application

- **Interface principale :** `https://votre-site.com/sgc-agentone/`
- **Chat direct :** `https://votre-site.com/sgc-agentone/extensions/vscode/src/webview/chat.html`
- **API :** `https://votre-site.com/sgc-agentone/api/chat`

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