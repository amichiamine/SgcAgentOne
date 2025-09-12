# 🚀 **MODE D'EMPLOI - SGC-AGENTONE AVEC TOUS LES IDE**

## 🎯 **COMMENT ÇA FONCTIONNE**

SGC-AgentOne utilise une **architecture serveur-client** :
- **Serveur local** : Interface web sur `http://localhost:5000`
- **Chat IA** : Interprète vos commandes en langage naturel
- **Actions directes** : Modifie vos fichiers de projet automatiquement

## 📋 **ÉTAPES D'UTILISATION**

### **1. Démarrage du serveur**
```bash
# Dans votre terminal
cd votre-projet/
php sgc-agentone/core/server.php
# ✅ Serveur SGC-AgentOne prêt sur http://0.0.0.0:5000
```

### **2. Interface utilisateur**
- **Ouvrez** votre navigateur → `http://localhost:5000`
- **5 onglets** disponibles : Chat, Files, Database, **Prompts**, Settings

### **3. Workflow pratique avec IDE**

**Configuration idéale :**
```
┌─────────────────┬─────────────────┐
│                 │                 │
│   VOTRE IDE     │   SGC-AGENTONE  │
│   (VSCode,      │   (Navigateur   │
│    WebStorm,    │    localhost:   │
│    Sublime...)  │      5000)      │
│                 │                 │
└─────────────────┴─────────────────┘
```

## 💬 **COMMANDES CHAT DISPONIBLES**

### **📁 Création de fichiers**
```
• "crée un fichier index.html"
• "génère le fichier style.css" 
• "ajoute un fichier script.js"
• "create file api.php"
```

### **✏️ Modification de fichiers**
```
• "modifie le fichier index.html"
• "update file style.css"
• "change le contenu script.js"
```

### **👀 Lecture de fichiers**
```
• "lis le fichier package.json"
• "read file config.php"
• "affiche le contenu README.md"
```

### **🗄️ Base de données**
```
• "connecte à la base users.db"
• "create database"
• "exécute la requête SELECT * FROM users"
• "lance la requête SQL CREATE TABLE..."
```

## 🎨 **VUE PROMPTS - PERSONNALISER LES COMMANDES**

### **Accès :** Cliquez sur l'onglet ✨ **Prompts**

### **Fonctionnalités :**
- **Visualiser** tous les patterns existants (16 par défaut)
- **Rechercher** et filtrer par action
- **Créer** de nouveaux patterns personnalisés
- **Tester** vos commandes en temps réel
- **Templates** rapides pour démarrer

### **Exemple personnalisation :**
```json
{
  "pattern": "génère une API REST pour *",
  "action": "create file"
}
```

**Usage :** `"génère une API REST pour utilisateurs"`
→ Créera un fichier API pour la gestion des utilisateurs

## 🔧 **COMPATIBILITÉ IDE**

### **✅ Compatible avec TOUS les IDE :**
- **VSCode** (Microsoft)
- **WebStorm** (JetBrains) 
- **Sublime Text**
- **Atom**
- **Vim/Neovim**
- **Emacs**
- **NotePad++**
- **Visual Studio**
- **Android Studio**
- **Eclipse**

### **💡 Avantages universels :**
- ❌ **Aucune extension** à installer
- ✅ **Interface web** accessible partout
- ✅ **Actions directes** sur vos fichiers
- ✅ **Multi-langues** (français/anglais)
- ✅ **Personnalisable** via vue Prompts

## 🚀 **FLUX DE TRAVAIL RECOMMANDÉ**

### **Démarrage rapide :**
1. **Lancez** SGC-AgentOne server
2. **Ouvrez** votre IDE favori
3. **Ouvrez** `localhost:5000` dans un navigateur
4. **Disposez** les fenêtres côte à côte
5. **Commencez** à coder avec l'assistant !

### **Exemple session :**
```
👤 "crée un fichier components/Header.jsx"
🤖 ✅ Fichier créé avec structure React

👤 "génère le fichier styles/header.css"  
🤖 ✅ Fichier CSS créé avec styles de base

👤 "modifie le fichier App.js pour importer Header"
🤖 ✅ Import ajouté automatiquement

👤 "lis le fichier package.json"
🤖 📋 Affiche le contenu du package.json
```

## 🎯 **UTILISATION AVANCÉE**

### **Vue Files** 📁
- Gestionnaire de fichiers graphique
- Actions rapides (HTML, CSS, JS, PHP)
- Navigation dans l'arborescence

### **Vue Database** 🗄️
- Éditeur SQL intégré  
- Requêtes prédéfinies
- État de la base en temps réel

### **Vue Settings** ⚙️
- **3 thèmes** : SGC-Commander, Sombre, Clair
- Configuration sécurité
- Actions système

## 📊 **RÉSUМÉ DES AVANTAGES**

| Critère | SGC-AgentOne |
|---------|--------------|
| **Installation** | ❌ Aucune extension |
| **Compatibilité** | ✅ Tous les IDE |
| **Interface** | 🎨 Web moderne |
| **Personnalisation** | ✨ Vue Prompts |
| **Sécurité** | 🔐 API key + CORS |
| **Multilingue** | 🌍 FR + EN |

**SGC-AgentOne fonctionne comme un assistant IA universel qui s'adapte à votre environnement de développement, quel qu'il soit !** 🚀

## 🔧 **CONFIGURATION TECHNIQUE**

### **Prérequis :**
- PHP 8.1+ avec serveur built-in
- Navigateur web moderne
- Votre IDE préféré

### **Structure des commandes :**
SGC-AgentOne utilise un système de reconnaissance de patterns défini dans `core/config/rules.json`. Chaque pattern associe une phrase en langage naturel à une action spécifique.

### **Sécurité :**
- Authentification par clé API (configurable)
- CORS sécurisé pour origines autorisées  
- Validation stricte des actions autorisées
- Mode "blind execution" désactivé par défaut

### **Personnalisation :**
Utilisez la **Vue Prompts** pour :
- Ajouter vos propres patterns de commandes
- Adapter le vocabulaire à votre façon de travailler  
- Créer des raccourcis pour vos tâches répétitives
- Exporter/importer des configurations

---

*Documentation générée pour SGC-AgentOne - Assistant IA universel pour développeurs*