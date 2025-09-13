/**
 * SGC-AgentOne Browser - Navigateur web intégré
 * Fonctionnalités: Navigation web, preview de projet, marque-pages
 */

// Fonction universelle de détection automatique du projet
function getProjectInfo() {
    const currentPath = window.location.pathname;
    const hostname = window.location.hostname;
    
    // Détection automatique du nom de dossier projet
    let projectName = '';
    
    // Extraire le nom depuis la structure des chemins
    if (currentPath.includes('/extensions/')) {
        const beforeExtensions = currentPath.substring(0, currentPath.indexOf('/extensions/'));
        projectName = beforeExtensions.split('/').filter(p => p).pop() || '';
    } else if (currentPath.includes('/deployment/')) {
        const beforeDeployment = currentPath.substring(0, currentPath.indexOf('/deployment/'));
        projectName = beforeDeployment.split('/').filter(p => p).pop() || '';
    } else if (currentPath.includes('/core/')) {
        const beforeCore = currentPath.substring(0, currentPath.indexOf('/core/'));
        projectName = beforeCore.split('/').filter(p => p).pop() || '';
    }
    
    // Si pas trouvé et qu'on est dans un sous-dossier
    if (!projectName && currentPath.match(/^\/[^/]+\//)) {
        const segments = currentPath.split('/').filter(p => p);
        if (segments.length > 0) {
            projectName = segments[0];
        }
    }
    
    // Détection spéciale Replit vs XAMPP/hébergement
    const isReplit = hostname.includes('.replit.dev') || hostname === 'localhost';
    
    return {
        name: projectName || 'sgc-agentone', // fallback par défaut
        isReplit: isReplit,
        basePath: projectName ? `/${projectName}` : '',
        themePath: projectName ? `/${projectName}/theme` : '/theme',
        apiPath: projectName ? `/${projectName}/api` : '/api'
    };
}

// Fonction de détection automatique du chemin de base de l'API
function getApiBase() {
    const projectInfo = getProjectInfo();
    return projectInfo.apiPath + '/';
}

class SGCBrowser {
    constructor() {
        this.currentUrl = '';
        this.history = [];
        this.historyIndex = -1;
        this.bookmarks = this.loadBookmarks();
        this.isLoading = false;
        this.isFullscreen = false;
        this.apiToken = null;
        
        this.initializeElements();
        this.setupEventListeners();
        this.initializeBookmarks();
        this.showQuickAccess();
        this.initializeAuth();
        
        console.log('SGC Browser initialisé');
    }

    async initializeAuth() {
        try {
            const response = await fetch(getApiBase() + 'auth/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    client_type: 'webview'
                })
            });
            
            if (response.ok) {
                const authData = await response.json();
                this.apiToken = authData.token;
            } else {
                console.error('Erreur d\'authentification browser');
            }
        } catch (error) {
            console.error('Erreur d\'authentification browser:', error);
        }
    }

    initializeElements() {
        // Navigation elements
        this.backBtn = document.getElementById('back-btn');
        this.forwardBtn = document.getElementById('forward-btn');
        this.refreshBtn = document.getElementById('refresh-btn');
        this.homeBtn = document.getElementById('home-btn');
        
        // Address bar
        this.urlInput = document.getElementById('url-input');
        this.goBtn = document.getElementById('go-btn');
        this.securityIcon = document.getElementById('security-icon');
        
        // Action buttons
        this.previewBtn = document.getElementById('preview-btn');
        this.bookmarksBtn = document.getElementById('bookmarks-btn');
        this.fullscreenBtn = document.getElementById('fullscreen-btn');
        
        // Content elements
        this.browserFrame = document.getElementById('browser-frame');
        this.loadingIndicator = document.getElementById('loading-indicator');
        this.errorPage = document.getElementById('error-page');
        this.quickAccess = document.getElementById('quick-access');
        
        // Status elements
        this.statusText = document.getElementById('status-text');
        this.connectionStatus = document.getElementById('connection-status');
        this.zoomLevel = document.getElementById('zoom-level');
        
        // Modal elements
        this.bookmarksModal = document.getElementById('bookmarks-modal');
        this.bookmarksList = document.getElementById('bookmarks-list');
    }

    setupEventListeners() {
        // Navigation controls
        this.backBtn.addEventListener('click', () => this.goBack());
        this.forwardBtn.addEventListener('click', () => this.goForward());
        this.refreshBtn.addEventListener('click', () => this.refresh());
        this.homeBtn.addEventListener('click', () => this.goHome());
        
        // Address bar
        this.urlInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.navigate(this.urlInput.value);
            }
        });
        
        this.goBtn.addEventListener('click', () => {
            this.navigate(this.urlInput.value);
        });
        
        // Action buttons
        this.previewBtn.addEventListener('click', () => this.showPreviewOptions());
        this.bookmarksBtn.addEventListener('click', () => this.showBookmarks());
        this.fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
        
        // Quick access
        document.getElementById('hide-quick-access').addEventListener('click', () => {
            this.hideQuickAccess();
        });
        
        // Quick links
        document.querySelectorAll('.quick-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const url = link.dataset.url;
                const type = link.dataset.type;
                
                if (url) {
                    this.navigate(url);
                } else if (type) {
                    this.handlePreviewType(type);
                }
            });
        });
        
        // Modal handling
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = document.getElementById(btn.dataset.modal);
                this.closeModal(modal);
            });
        });
        
        // Error page buttons
        document.getElementById('retry-btn').addEventListener('click', () => {
            this.refresh();
        });
        
        document.getElementById('home-error-btn').addEventListener('click', () => {
            this.goHome();
        });
        
        // Bookmarks
        document.getElementById('add-bookmark-btn').addEventListener('click', () => {
            this.addBookmark();
        });
        
        // Frame load events
        this.browserFrame.addEventListener('load', () => {
            this.onFrameLoad();
        });
        
        this.browserFrame.addEventListener('error', () => {
            this.onFrameError();
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'r':
                        e.preventDefault();
                        this.refresh();
                        break;
                    case 'l':
                        e.preventDefault();
                        this.urlInput.focus();
                        this.urlInput.select();
                        break;
                    case 'd':
                        e.preventDefault();
                        this.addBookmark();
                        break;
                    case 'f':
                        e.preventDefault();
                        this.toggleFullscreen();
                        break;
                }
            }
            
            if (e.key === 'F11') {
                e.preventDefault();
                this.toggleFullscreen();
            }
        });
    }

    navigate(url) {
        if (!url) return;
        
        // Clean and validate URL
        url = this.cleanUrl(url);
        
        if (!this.isValidUrl(url)) {
            // Treat as search query
            url = `https://www.google.com/search?q=${encodeURIComponent(url)}`;
        }
        
        this.setLoading(true);
        this.hideError();
        this.hideQuickAccess();
        
        this.currentUrl = url;
        this.urlInput.value = url;
        this.updateSecurity(url);
        
        // Add to history
        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }
        this.history.push(url);
        this.historyIndex = this.history.length - 1;
        this.updateNavigationButtons();
        
        // Navigate iframe with enhanced error handling
        try {
            this.browserFrame.src = url;
            this.statusText.textContent = `Chargement de ${this.getDomain(url)}...`;
            
            // Set a timeout to detect frame-busting
            this.frameTimeout = setTimeout(() => {
                try {
                    // Try to access the iframe content to detect if it loaded
                    const frameDoc = this.browserFrame.contentDocument || this.browserFrame.contentWindow.document;
                    if (!frameDoc || frameDoc.location.href === 'about:blank') {
                        this.onFrameError(`Cette page ne peut pas être affichée dans un frame. <a href="${url}" target="_blank">Ouvrir dans un nouvel onglet</a>`);
                    }
                } catch (e) {
                    // Cross-origin or frame-busting detected
                    this.onFrameError(`Cette page ne peut pas être affichée dans un frame. <a href="${url}" target="_blank">Ouvrir dans un nouvel onglet</a>`);
                }
            }, 5000);
            
        } catch (error) {
            this.onFrameError('Erreur lors du chargement de la page');
        }
    }

    cleanUrl(url) {
        url = url.trim();
        
        // Add protocol if missing
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            // Check if it looks like a domain
            if (url.includes('.') && !url.includes(' ')) {
                url = 'https://' + url;
            }
        }
        
        return url;
    }

    isValidUrl(url) {
        // Accepter les URLs relatives (commencent par /)
        if (url.startsWith('/')) {
            return true;
        }
        
        // Vérifier les URLs absolues
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    getDomain(url) {
        try {
            return new URL(url).hostname;
        } catch {
            return url;
        }
    }

    updateSecurity(url) {
        const isSecure = url.startsWith('https://');
        
        if (isSecure) {
            this.securityIcon.className = 'fas fa-lock url-security';
            this.connectionStatus.className = 'connection-secure';
            this.connectionStatus.innerHTML = '<i class="fas fa-shield-alt"></i> Sécurisé';
        } else if (url.startsWith('http://')) {
            this.securityIcon.className = 'fas fa-unlock url-security';
            this.connectionStatus.className = 'connection-insecure';
            this.connectionStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Non sécurisé';
        } else {
            this.securityIcon.className = 'fas fa-globe url-security';
            this.connectionStatus.className = '';
            this.connectionStatus.innerHTML = '<i class="fas fa-info-circle"></i> Local';
        }
    }

    goBack() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            const url = this.history[this.historyIndex];
            this.loadFromHistory(url);
        }
    }

    goForward() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            const url = this.history[this.historyIndex];
            this.loadFromHistory(url);
        }
    }

    loadFromHistory(url) {
        this.setLoading(true);
        this.hideError();
        
        this.currentUrl = url;
        this.urlInput.value = url;
        this.updateSecurity(url);
        this.updateNavigationButtons();
        
        this.browserFrame.src = url;
        this.statusText.textContent = `Chargement de ${this.getDomain(url)}...`;
    }

    refresh() {
        if (this.currentUrl) {
            this.setLoading(true);
            this.hideError();
            this.browserFrame.src = this.currentUrl;
            this.statusText.textContent = `Actualisation de ${this.getDomain(this.currentUrl)}...`;
        }
    }

    goHome() {
        this.showQuickAccess();
        this.currentUrl = '';
        this.urlInput.value = '';
        this.browserFrame.src = 'about:blank';
        this.statusText.textContent = 'Accueil';
        this.setLoading(false);
        this.hideError();
        this.updateSecurity('');
    }

    updateNavigationButtons() {
        this.backBtn.disabled = this.historyIndex <= 0;
        this.forwardBtn.disabled = this.historyIndex >= this.history.length - 1;
    }

    setLoading(loading) {
        this.isLoading = loading;
        this.loadingIndicator.classList.toggle('hidden', !loading);
        
        if (loading) {
            this.refreshBtn.innerHTML = '<i class="fas fa-times"></i>';
            this.refreshBtn.title = 'Arrêter';
        } else {
            this.refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
            this.refreshBtn.title = 'Actualiser';
        }
    }

    onFrameLoad() {
        this.setLoading(false);
        this.hideError();
        
        // Clear the frame timeout if the page loaded successfully
        if (this.frameTimeout) {
            clearTimeout(this.frameTimeout);
            this.frameTimeout = null;
        }
        
        try {
            const frameUrl = this.browserFrame.contentWindow.location.href;
            if (frameUrl && frameUrl !== 'about:blank') {
                this.statusText.textContent = `Chargé: ${this.getDomain(frameUrl)}`;
            }
        } catch (error) {
            // Cross-origin restriction
            this.statusText.textContent = `Chargé: ${this.getDomain(this.currentUrl)}`;
        }
    }

    onFrameError(message = 'Erreur de chargement') {
        this.setLoading(false);
        this.showError(message);
    }

    showError(message) {
        this.errorPage.style.display = 'flex';
        document.getElementById('error-message').innerHTML = message;
        this.statusText.textContent = 'Erreur de chargement';
    }

    hideError() {
        this.errorPage.style.display = 'none';
    }

    showQuickAccess() {
        this.quickAccess.classList.remove('hidden');
    }

    hideQuickAccess() {
        this.quickAccess.classList.add('hidden');
    }

    showPreviewOptions() {
        // Show preview options in quick access
        this.showQuickAccess();
        
        // Scroll to preview section
        const previewSection = document.querySelector('.link-category:nth-child(2)');
        if (previewSection) {
            previewSection.scrollIntoView({ behavior: 'smooth' });
        }
    }

    handlePreviewType(type) {
        let url = '';
        
        switch (type) {
            case 'local':
                // Preview the main SGC-AgentOne interface
                url = '/';
                break;
            case 'server':
                // Preview the plug & play deployment
                url = '/deployment/plugandplay/';
                break;
            case 'files':
                this.showHtmlFiles();
                return;
            default:
                return;
        }
        
        this.navigate(url);
    }

    async showHtmlFiles() {
        try {
            // Get API token first if not already available
            if (!this.apiToken) {
                await this.initializeAuth();
            }
            
            const headers = {};
            if (this.apiToken) {
                headers['X-API-Key'] = this.apiToken;
            }
            
            // Get HTML files from the files API
            const response = await fetch(getApiBase() + 'files/list', {
                headers: headers
            });
            
            if (response.ok) {
                const files = await response.json();
                const htmlFiles = files.filter(file => 
                    file.name.endsWith('.html') || file.name.endsWith('.htm')
                );
                
                if (htmlFiles.length > 0) {
                    // Show selection dialog or navigate to first HTML file
                    const firstFile = htmlFiles[0];
                    const fileUrl = `${window.location.origin}/api/files/read?path=${encodeURIComponent(firstFile.path)}`;
                    this.navigate(fileUrl);
                } else {
                    this.statusText.textContent = 'Aucun fichier HTML trouvé';
                }
            }
        } catch (error) {
            console.error('Erreur lors de la récupération des fichiers HTML:', error);
            this.statusText.textContent = 'Erreur lors de la récupération des fichiers';
        }
    }

    // Bookmarks management
    loadBookmarks() {
        try {
            return JSON.parse(localStorage.getItem('sgc-browser-bookmarks') || '[]');
        } catch {
            return [];
        }
    }

    saveBookmarks() {
        localStorage.setItem('sgc-browser-bookmarks', JSON.stringify(this.bookmarks));
    }

    addBookmark() {
        if (!this.currentUrl || this.currentUrl === 'about:blank') {
            this.statusText.textContent = 'Aucune page à ajouter aux marque-pages';
            return;
        }
        
        const title = prompt('Titre du marque-page:', this.getDomain(this.currentUrl));
        if (title) {
            const bookmark = {
                id: Date.now(),
                title: title,
                url: this.currentUrl,
                date: new Date().toISOString()
            };
            
            this.bookmarks.unshift(bookmark);
            this.saveBookmarks();
            this.updateBookmarksList();
            this.statusText.textContent = 'Marque-page ajouté';
        }
    }

    removeBookmark(id) {
        this.bookmarks = this.bookmarks.filter(bookmark => bookmark.id !== id);
        this.saveBookmarks();
        this.updateBookmarksList();
        this.statusText.textContent = 'Marque-page supprimé';
    }

    initializeBookmarks() {
        this.updateBookmarksList();
    }

    updateBookmarksList() {
        this.bookmarksList.innerHTML = '';
        
        if (this.bookmarks.length === 0) {
            this.bookmarksList.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--sgc-text-secondary);">
                    <i class="fas fa-bookmark" style="font-size: 24px; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>Aucun marque-page enregistré</p>
                </div>
            `;
            return;
        }
        
        this.bookmarks.forEach(bookmark => {
            const item = document.createElement('div');
            item.className = 'bookmark-item';
            item.innerHTML = `
                <div class="bookmark-info" onclick="browser.navigate('${bookmark.url}'); browser.closeModal(browser.bookmarksModal);">
                    <i class="fas fa-bookmark"></i>
                    <div class="bookmark-details">
                        <div class="bookmark-title">${this.escapeHtml(bookmark.title)}</div>
                        <div class="bookmark-url">${this.escapeHtml(bookmark.url)}</div>
                    </div>
                </div>
                <button class="bookmark-actions-btn" onclick="browser.removeBookmark(${bookmark.id})" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            this.bookmarksList.appendChild(item);
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showBookmarks() {
        this.showModal(this.bookmarksModal);
    }

    showModal(modal) {
        modal.classList.add('active');
        
        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal(modal);
            }
        });
    }

    closeModal(modal) {
        modal.classList.remove('active');
    }

    toggleFullscreen() {
        this.isFullscreen = !this.isFullscreen;
        document.body.classList.toggle('browser-fullscreen', this.isFullscreen);
        
        if (this.isFullscreen) {
            this.fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
            this.fullscreenBtn.title = 'Quitter le plein écran';
        } else {
            this.fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
            this.fullscreenBtn.title = 'Plein écran';
        }
    }
}

// Initialize browser when page loads
let browser;
document.addEventListener('DOMContentLoaded', () => {
    browser = new SGCBrowser();
});

// Global functions for HTML onclick handlers
window.browser = browser;