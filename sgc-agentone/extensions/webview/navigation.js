/**
 * SGC-AgentOne Navigation System
 * Gestion centralisée de la navigation entre les vues
 */

// Configuration des vues disponibles
const views = {
    chat: {
        name: 'Chat',
        icon: 'fas fa-comments',
        description: 'Assistant IA conversationnel'
    },
    files: {
        name: 'Files',
        icon: 'fas fa-folder',
        description: 'Gestionnaire de fichiers'
    },
    database: {
        name: 'Database',
        icon: 'fas fa-database',
        description: 'Base de données SQLite'
    },
    prompts: {
        name: 'Prompts',
        icon: 'fas fa-magic',
        description: 'Constructeur de prompts'
    },
    settings: {
        name: 'Settings',
        icon: 'fas fa-cog',
        description: 'Configuration système'
    },
    server: {
        name: 'Server',
        icon: 'fas fa-server',
        description: 'Monitoring serveur'
    },
    editor: {
        name: 'Editor',
        icon: 'fas fa-code',
        description: 'Éditeur de code Monaco'
    },
    browser: {
        name: 'Browser',
        icon: 'fas fa-globe',
        description: 'Navigateur web intégré'
    }
};

// État actuel de la navigation
let currentView = 'chat';
let navigationHistory = ['chat'];
let historyIndex = 0;

/**
 * Navigation vers une vue spécifique
 */
function navigateToView(viewId) {
    if (!views[viewId]) {
        console.error(`Vue "${viewId}" non trouvée`);
        return false;
    }
    
    console.log(`Navigation vers: ${viewId}`);
    
    // Mettre à jour l'historique
    if (currentView !== viewId) {
        // Supprimer l'historique futur si on navigue depuis le milieu
        if (historyIndex < navigationHistory.length - 1) {
            navigationHistory = navigationHistory.slice(0, historyIndex + 1);
        }
        
        navigationHistory.push(viewId);
        historyIndex = navigationHistory.length - 1;
        
        // Limiter l'historique à 50 entrées
        if (navigationHistory.length > 50) {
            navigationHistory = navigationHistory.slice(-50);
            historyIndex = navigationHistory.length - 1;
        }
    }
    
    // Mettre à jour l'état
    const previousView = currentView;
    currentView = viewId;
    
    // Mettre à jour l'interface
    updateNavigationUI();
    
    // Déclencher l'événement de changement de vue
    document.dispatchEvent(new CustomEvent('viewChanged', {
        detail: {
            from: previousView,
            to: viewId,
            viewConfig: views[viewId]
        }
    }));
    
    return true;
}

/**
 * Navigation dans l'historique
 */
function goBack() {
    if (historyIndex > 0) {
        historyIndex--;
        const viewId = navigationHistory[historyIndex];
        currentView = viewId;
        updateNavigationUI();
        
        // Utiliser la fonction handleTabSwitch existante
        if (typeof handleTabSwitch === 'function') {
            handleTabSwitch(viewId);
        }
        
        console.log(`Navigation arrière vers: ${viewId}`);
        return true;
    }
    return false;
}

function goForward() {
    if (historyIndex < navigationHistory.length - 1) {
        historyIndex++;
        const viewId = navigationHistory[historyIndex];
        currentView = viewId;
        updateNavigationUI();
        
        // Utiliser la fonction handleTabSwitch existante
        if (typeof handleTabSwitch === 'function') {
            handleTabSwitch(viewId);
        }
        
        console.log(`Navigation avant vers: ${viewId}`);
        return true;
    }
    return false;
}

/**
 * Mise à jour de l'interface de navigation
 */
function updateNavigationUI() {
    // Mettre à jour les boutons de navigation
    const navButtons = document.querySelectorAll('.nav-button');
    navButtons.forEach(button => {
        const tabId = button.dataset.tab;
        button.classList.toggle('active', tabId === currentView);
    });
    
    // Mettre à jour le titre de la page si présent
    const pageTitle = document.querySelector('title');
    if (pageTitle) {
        const viewName = views[currentView]?.name || currentView;
        pageTitle.textContent = `SGC-AgentOne - ${viewName}`;
    }
    
    // Mettre à jour les indicateurs d'historique
    updateHistoryIndicators();
}

/**
 * Mise à jour des indicateurs d'historique
 */
function updateHistoryIndicators() {
    const backBtn = document.querySelector('#back-navigation-btn');
    const forwardBtn = document.querySelector('#forward-navigation-btn');
    
    if (backBtn) {
        backBtn.disabled = historyIndex <= 0;
        backBtn.title = historyIndex > 0 ? 
            `Retour vers ${views[navigationHistory[historyIndex - 1]]?.name}` : 
            'Aucun historique précédent';
    }
    
    if (forwardBtn) {
        forwardBtn.disabled = historyIndex >= navigationHistory.length - 1;
        forwardBtn.title = historyIndex < navigationHistory.length - 1 ? 
            `Avant vers ${views[navigationHistory[historyIndex + 1]]?.name}` : 
            'Aucun historique suivant';
    }
}

/**
 * Initialisation du système de navigation
 */
function initializeNavigation() {
    console.log('Initialisation du système de navigation SGC-AgentOne');
    
    // Ajouter les gestionnaires d'événements pour les boutons de navigation
    document.addEventListener('DOMContentLoaded', () => {
        const navButtons = document.querySelectorAll('.nav-button');
        navButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const viewId = button.dataset.tab;
                if (viewId) {
                    navigateToView(viewId);
                    // Empêcher la gestion par handleTabSwitch directement
                    e.stopImmediatePropagation();
                }
            });
        });
        
        // Boutons d'historique si présents
        const backBtn = document.querySelector('#back-navigation-btn');
        const forwardBtn = document.querySelector('#forward-navigation-btn');
        
        if (backBtn) {
            backBtn.addEventListener('click', goBack);
        }
        
        if (forwardBtn) {
            forwardBtn.addEventListener('click', goForward);
        }
        
        // Raccourcis clavier
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case '[':
                        e.preventDefault();
                        goBack();
                        break;
                    case ']':
                        e.preventDefault();
                        goForward();
                        break;
                }
            }
            
            // Raccourcis pour les vues (Ctrl+1, Ctrl+2, etc.)
            if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '8') {
                e.preventDefault();
                const viewKeys = Object.keys(views);
                const index = parseInt(e.key) - 1;
                if (index < viewKeys.length) {
                    navigateToView(viewKeys[index]);
                }
            }
        });
        
        // Initialiser l'interface
        updateNavigationUI();
    });
    
    // Écouter les événements de changement de vue
    document.addEventListener('viewChanged', (e) => {
        console.log('Vue changée:', e.detail);
    });
}

/**
 * Obtenir des informations sur la vue actuelle
 */
function getCurrentView() {
    return {
        id: currentView,
        config: views[currentView],
        history: [...navigationHistory],
        historyIndex: historyIndex
    };
}

/**
 * Obtenir la liste de toutes les vues disponibles
 */
function getAvailableViews() {
    return { ...views };
}

/**
 * Ajouter une nouvelle vue au système
 */
function registerView(id, config) {
    if (views[id]) {
        console.warn(`Vue "${id}" déjà enregistrée, écrasement`);
    }
    
    views[id] = {
        name: config.name || id,
        icon: config.icon || 'fas fa-circle',
        description: config.description || ''
    };
    
    console.log(`Vue "${id}" enregistrée:`, views[id]);
}

/**
 * Supprimer une vue du système
 */
function unregisterView(id) {
    if (!views[id]) {
        console.warn(`Vue "${id}" non trouvée`);
        return false;
    }
    
    // Ne pas supprimer si c'est la vue actuelle
    if (currentView === id) {
        console.warn(`Impossible de supprimer la vue active "${id}"`);
        return false;
    }
    
    delete views[id];
    
    // Nettoyer l'historique
    navigationHistory = navigationHistory.filter(viewId => viewId !== id);
    historyIndex = Math.min(historyIndex, navigationHistory.length - 1);
    
    console.log(`Vue "${id}" supprimée`);
    return true;
}

// Initialiser automatiquement
if (typeof window !== 'undefined') {
    initializeNavigation();
}

// Export pour utilisation dans d'autres scripts
if (typeof window !== 'undefined') {
    window.SGCNavigation = {
        navigateToView,
        goBack,
        goForward,
        getCurrentView,
        getAvailableViews,
        registerView,
        unregisterView,
        views
    };
}