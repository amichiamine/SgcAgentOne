# 💬 **FONCTIONNEMENT DU CHAT SGC-AgentOne**

Guide complet du système de chat intelligent pour la génération et modification de fichiers via commandes en langage naturel.

## 🔄 **ARCHITECTURE DU SYSTÈME DE CHAT**

### **1. FLUX DE COMMUNICATION**
```
Utilisateur → Interface Chat → API REST → Interpréteur → Actions → Réponse
```

**Interface utilisateur** :
- Chat HTML/JS dans l'extension VS Code ou interface web
- Envoi via API REST (`/api/chat`) avec message JSON
- Authentification par token webview ou clé API

**API backend** :
- PHP REST endpoint sécurisé avec CORS
- Validation des permissions et logs automatiques
- Traitement asynchrone des commandes

---

## 🧠 **SYSTÈME D'INTERPRÉTATION INTELLIGENT**

### **2. RECONNAISSANCE DE PATTERNS**

**Fichier de règles** (`core/config/rules.json`) :
```json
{
  "pattern": "crée un fichier *",
  "action": "create file"
},
{
  "pattern": "modifie le fichier *", 
  "action": "update file"
}
```

**Algorithme de matching** :
- **Mots-clés flous** : Au moins 50% des mots doivent correspondre
- **Wildcards** : `*` pour capturer noms de fichiers/paramètres  
- **Support multilingue** : Français ET anglais
- **Extraction intelligente** : Paramètres automatiquement extraits

### **3. ACTIONS DISPONIBLES**

**5 actions principales** :
- 📝 **create file** : Création avec templates automatiques
- ✏️ **update file** : Modification de contenu  
- 👁️ **read file** : Lecture et affichage
- 🗃️ **create database** : Initialisation SQLite
- 🔍 **execute query** : Requêtes SQL directes

**Fichiers d'actions** (`core/agents/actions/`) :
- `createFile.php` - Création de fichiers avec templates
- `updateFile.php` - Modification de contenu existant
- `readFile.php` - Lecture et affichage de fichiers
- `createDB.php` - Initialisation base de données SQLite
- `executeQuery.php` - Exécution de requêtes SQL

---

## ⚙️ **PERSONNALISATION DES PROMPTS**

### **4. CONFIGURATION DES RÈGLES**

**Ajout de nouvelles commandes** dans `core/config/rules.json` :
```json
{
  "pattern": "génère une page d'accueil *",
  "action": "create file"
},
{
  "pattern": "analyse le code de *",
  "action": "read file"
}
```

**Personnalisation avancée** :
- **Patterns flexibles** : Expressions régulières ET mots-clés
- **Actions personnalisées** : Ajout de nouveaux modules dans `/actions/`
- **Templates dynamiques** : Variables et contenu généré
- **Multilingue** : Français, anglais, facilement extensible

### **5. SYSTÈME DE TEMPLATES**

**Templates automatiques par type** (`core/templates/`) :
- `.html` → Structure HTML5 complète
- `.css` → Reset CSS + structure de base
- `.js` → Console.log + structure de départ
- `.php` → Structure PHP avec echo
- `.json` → Structure JSON valide

**Variables dynamiques** :
```php
$content = TemplateRenderer::quickRender($template, [
    'title' => 'Mon Projet',
    'author' => 'SGC-AgentOne'
]);
```

---

## 🔒 **SÉCURITÉ ET CONTRÔLES**

### **6. SYSTÈME DE WHITELIST**

**Mode Blind-Exec** (optionnel) :
- Actions limitées à une liste approuvée (`core/config/whitelist.json`)
- Prévention de l'exécution de code malveillant
- Logs complets de toutes les actions (`core/logs/`)

**Authentification** (`core/config/settings.json`) :
- Tokens webview pour VS Code
- Clés API pour intégrations externes
- CORS sécurisé pour les domaines autorisés

**Configuration de sécurité** :
```json
{
  "security": {
    "api_key": "sgc-agent-dev-key-2024",
    "allowed_origins": ["http://localhost:5000", "https://*.replit.dev"],
    "require_auth": true
  }
}
```

---

## 📝 **EXEMPLES CONCRETS**

### **Commandes naturelles** :
- *"Crée un fichier style.css avec des variables CSS"*
- *"Génère le fichier index.html avec un titre moderne"*
- *"Modifie le fichier script.js pour ajouter une fonction"*
- *"Connecte à la base et crée une table utilisateurs"*
- *"Exécute la requête SELECT * FROM users"*

### **Réponses intelligentes** :
- ✅ **Succès** : *"Fichier créé : style.css (156 octets)"*
- ❌ **Erreur** : *"Impossible d'écrire le fichier : permissions"*
- 🤔 **Incompréhension** : *"Pouvez-vous reformuler votre demande ?"*

---

## 🚀 **EXTENSION ET PERSONNALISATION**

### **7. AJOUTER DE NOUVELLES FONCTIONNALITÉS**

**Étapes pour une nouvelle action** :

1. **Nouveau pattern** dans `core/config/rules.json` :
```json
{
  "pattern": "compile le projet *",
  "action": "compile project"
}
```

2. **Nouvelle action** dans `core/agents/actions/compileProject.php` :
```php
<?php
function executeAction_compileproject($params, $projectPath) {
    // Logique de compilation
    return ['success' => true, 'response' => 'Projet compilé avec succès'];
}
?>
```

3. **Mapping dans** `core/api/chat.php` (ligne 218) :
```php
$actionFiles = [
    'create file' => 'createFile.php',
    'compile project' => 'compileProject.php'  // Ajouter ici
];
```

### **8. STRUCTURE DES FICHIERS**

```
sgc-agentone/
├── core/
│   ├── agents/
│   │   ├── actions/          # Actions exécutables
│   │   └── interpreter.php   # Moteur d'interprétation
│   ├── api/
│   │   └── chat.php         # API REST principale
│   ├── config/
│   │   ├── rules.json       # Patterns de commandes
│   │   ├── settings.json    # Configuration générale
│   │   └── whitelist.json   # Actions autorisées
│   ├── templates/           # Templates par type de fichier
│   ├── logs/               # Historique des actions
│   └── utils/              # Utilitaires (TemplateRenderer)
└── extensions/
    └── vscode/             # Extension VS Code
        └── src/webview/    # Interface chat
```

---

## 🛠️ **DÉVELOPPEMENT ET DEBUG**

### **9. LOGS ET MONITORING**

**Fichiers de logs** (`core/logs/`) :
- `chat.log` : Historique des conversations
- `actions.log` : Détail des actions exécutées

**Format des logs** :
```
[2025-09-12 21:46:40] USER: "crée un fichier test.html"
[2025-09-12 21:46:40] AI: "Fichier créé : test.html (245 octets)"
[2025-09-12 21:46:40] ACTION: create file | PARAMS: {"filename":"test.html"}
```

### **10. API ENDPOINTS**

**POST /api/chat** :
```json
{
  "message": "crée un fichier style.css",
  "projectPath": ".",
  "blind": false
}
```

**Réponse** :
```json
{
  "response": "Fichier créé : style.css (156 octets)",
  "actions": [{
    "action": "create file",
    "params": {"filename": "style.css"},
    "result": "success"
  }]
}
```

---

## 🎯 **CARACTÉRISTIQUES TECHNIQUES**

**Le système est conçu pour être** :
- 🎯 **Intuitif** : Commandes en langage naturel
- 🔧 **Extensible** : Nouvelles actions facilement ajoutables
- 🛡️ **Sécurisé** : Contrôles d'accès et validation
- 📊 **Traçable** : Logs complets de toutes les interactions
- 🌍 **Multilingue** : Support français et anglais natif
- ⚡ **Performant** : Traitement rapide des commandes
- 🔄 **Intégré** : Prêt pour VS Code et interfaces web

**SGC-AgentOne peut comprendre et exécuter des commandes complexes en français ou anglais, avec une architecture robuste prête pour l'intégration VS Code !** 🌟

---

## 📚 **RESSOURCES SUPPLÉMENTAIRES**

- **Configuration** : `core/config/settings.json`
- **Règles personnalisées** : `core/config/rules.json`
- **Templates** : `core/templates/`
- **Interface web** : `extensions/vscode/src/webview/chat.html`
- **Serveur de développement** : `core/server.php`

*Documentation générée automatiquement - Version SGC-AgentOne 1.0*