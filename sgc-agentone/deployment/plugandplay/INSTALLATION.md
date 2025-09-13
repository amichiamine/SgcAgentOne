# 🔌 SGC-AgentOne Plug & Play - Installation Universelle

**Compatible avec tous les environnements Apache/PHP :**
- 🌐 **Hébergement web mutualisé** (OVH, 1&1, Hostinger, GoDaddy...)
- 🖥️ **XAMPP** (Windows) 
- 🐧 **LAMP** (Linux)
- 🍎 **MAMP** (Mac)
- 🚀 **Serveurs Apache** avec PHP/SQLite

## 📋 Instructions d'installation

### 1️⃣ Structure universelle

**Pour hébergement web :**
```
public_html/
└── sgc-agentone/              <- Tout SGC-AgentOne dans ce dossier
```

**Pour XAMPP Windows :**
```
C:\xampp\htdocs\
└── sgc-agentone/              <- Tout SGC-AgentOne dans ce dossier
```

**Pour LAMP/MAMP :**
```
/var/www/html/                 <- ou /Applications/MAMP/htdocs/
└── sgc-agentone/              <- Tout SGC-AgentOne dans ce dossier
```

**Structure interne :**
```
sgc-agentone/
├── .htaccess              <- Copier depuis deployment/plugandplay/
├── index.php              <- Copier depuis deployment/plugandplay/
├── data/                  <- Créer pour la base de données
│   └── .htaccess          <- Protection base données
├── core/                  <- Code SGC-AgentOne existant
├── extensions/            <- Interface SGC-AgentOne existante
└── ...                    <- Reste du code SGC-AgentOne
```

### 2️⃣ Étapes d'installation

#### 🤖 **Installation automatique (recommandée)** :

**Hébergement web :**
1. **Uploadez le dossier sgc-agentone complet** dans public_html/sgc-agentone/
2. **Visitez** `https://votre-site.com/sgc-agentone/deployment/plugandplay/install.php`
3. **Le script configure automatiquement** tous les fichiers nécessaires
4. **Supprimez le dossier deployment/** après installation (optionnel)

**XAMPP Windows :**
1. **Copiez le dossier sgc-agentone** dans C:\xampp\htdocs\sgc-agentone\
2. **Démarrez XAMPP** (Apache)
3. **Visitez** `http://localhost/sgc-agentone/deployment/plugandplay/install.php`
4. **Le script configure automatiquement** tous les fichiers nécessaires

**LAMP/MAMP :**
1. **Copiez le dossier sgc-agentone** dans le répertoire web (ex: /var/www/html/)
2. **Visitez** `http://localhost/sgc-agentone/deployment/plugandplay/install.php`
3. **Le script configure automatiquement** tous les fichiers nécessaires

#### 📋 **Installation manuelle** :
1. **Placez le dossier sgc-agentone** dans le bon répertoire selon votre environnement
2. **Dans le dossier sgc-agentone/** copiez :
   - `.htaccess` depuis deployment/plugandplay/.htaccess
   - `index.php` depuis deployment/plugandplay/index.php
3. **Créez le dossier sgc-agentone/data/**
4. **Copiez data/.htaccess** vers sgc-agentone/data/.htaccess

### 3️⃣ Permissions fichiers

**Hébergement web (via gestionnaire de fichiers) :**
- `sgc-agentone/` : 755
- `sgc-agentone/data/` : 755  
- `sgc-agentone/core/` : 755
- `.htaccess` : 644
- `index.php` : 644

**XAMPP/LAMP/MAMP :**
- Permissions automatiques, aucune configuration nécessaire

### 4️⃣ Accès à l'application

**Hébergement web :**
- **Interface principale :** `https://votre-site.com/sgc-agentone/`
- **Chat direct :** `https://votre-site.com/sgc-agentone/extensions/webview/chat.html`
- **API :** `https://votre-site.com/sgc-agentone/api/chat`

**XAMPP/LAMP/MAMP :**
- **Interface principale :** `http://localhost/sgc-agentone/`
- **Chat direct :** `http://localhost/sgc-agentone/extensions/webview/chat.html`
- **API :** `http://localhost/sgc-agentone/api/chat`

### 5️⃣ Test de compatibilité

Le script `install.php` effectue automatiquement ces vérifications :
- ✅ Version PHP >= 7.4
- ✅ Extension SQLite3 disponible
- ✅ Support PDO SQLite
- ✅ Module Apache mod_rewrite

**Test manuel (optionnel) :**
```php
<?php
if (class_exists('SQLite3')) {
    echo "✅ SQLite3 disponible";
    $version = SQLite3::version();
    echo " - Version: " . $version['versionString'];
} else {
    echo "❌ SQLite3 non disponible";
}
?>
```

## 🎯 Cas d'usage

### 🖥️ **Développement local**
**XAMPP/LAMP/MAMP :** Parfait pour tester et développer sans contraintes
- Accès instantané via `http://localhost/sgc-agentone/`
- Debugging facile avec logs Apache/PHP
- Aucune limite de temps d'exécution

### 🌐 **Production web**
**Hébergement mutualisé :** Déploiement simple et sécurisé
- URLs propres avec nom de domaine
- Protection automatique des fichiers sensibles
- Compatible avec tous les hébergeurs PHP

### 🔄 **Workflow hybride**
- **Développement :** XAMPP en local
- **Test :** Hébergement de staging
- **Production :** Hébergement principal
- **Même code, transfert simple !**

## ✅ L'installation est prête !

SGC-AgentOne fonctionnera à l'identique sur tous les environnements :
- 💬 **Chat intelligent multilingue** (français/anglais)
- 📁 **Gestionnaire de fichiers** complet avec arborescence
- 🖊️ **Éditeur Monaco** professionnel intégré
- 🗄️ **Base de données SQLite** sécurisée
- 🎨 **7 vues complètes** avec thème SGC-Commander
- 🔧 **API REST** pour intégrations externes
- 🛡️ **Sécurité** avec authentification et protection fichiers

## 🚀 Avantages Plug & Play

- 🔌 **Installation simple** : Script automatique avec diagnostic
- 🌐 **Compatibilité universelle** : Tous environnements Apache/PHP
- 📋 **Prêt à l'emploi** : Aucune configuration complexe
- 🎯 **Auto-diagnostic** : Vérification environnement intégrée
- 🔄 **Transfert facile** : Même code partout
- 🛡️ **Sécurisé** : Protection automatique des données

🎯 **Aucune modification de code nécessaire - vraiment Plug & Play !**