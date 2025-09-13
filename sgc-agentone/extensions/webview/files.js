/**
 * SGC-AgentOne Files Explorer
 * Gestion de l'interface d'exploration des fichiers avec navigation entre dossiers
 */

// Fonction de détection automatique du chemin de base de l'API
function getApiBase() {
    const currentPath = window.location.pathname;
    // Détecter le chemin de base en cherchant les marqueurs de structure
    let basePath = '';
    
    // Chercher le premier segment qui indique la structure du projet
    const markers = ['/extensions/', '/deployment/', '/core/'];
    for (const marker of markers) {
        if (currentPath.includes(marker)) {
            basePath = currentPath.substring(0, currentPath.indexOf(marker));
            break;
        }
    }
    
    // Si aucun marqueur trouvé et chemin commence par /segment/, utiliser ce segment
    if (!basePath && currentPath.match(/^\/[^/]+\//)) {
        const segments = currentPath.split('/');
        if (segments.length > 1 && segments[1]) {
            basePath = '/' + segments[1];
        }
    }
    
    // Normaliser le chemin final
    return basePath.replace(/\/+$/, '') + '/api/';
}

class SGCFiles {
    constructor() {
        this.currentPath = '.';
        this.isLoading = false;
        this.selectedFiles = [];
        this.history = ['.'];
        this.historyIndex = 0;
        this.apiToken = null;
        
        this.initializeElements();
        this.setupEventListeners();
        this.initializeAuth();
        
        console.log('SGC Files Explorer initialisé');
    }

    initializeElements() {
        // Navigation elements
        this.parentBtn = document.getElementById('parent-btn');
        this.refreshBtn = document.getElementById('refresh-btn');
        this.homeBtn = document.getElementById('home-btn');
        
        // Path elements
        this.pathInput = document.getElementById('path-input');
        this.changeFolderBtn = document.getElementById('change-folder-btn');
        this.currentPathDisplay = document.getElementById('current-path-display');
        this.filesCountDisplay = document.getElementById('files-count');
        
        // File actions
        this.newFileBtn = document.getElementById('new-file-btn');
        this.newFolderBtn = document.getElementById('new-folder-btn');
        this.viewTreeBtn = document.getElementById('view-tree-btn');
        
        // Content areas
        this.fileTree = document.getElementById('file-tree');
        this.loadingIndicator = document.getElementById('loading-indicator');
        this.errorDisplay = document.getElementById('error-display');
        this.errorMessage = document.getElementById('error-message');
        this.statusText = document.getElementById('status-text');
        this.selectionCount = document.getElementById('selection-count');
        this.totalSize = document.getElementById('total-size');
        
        // Modal elements
        this.newFileModal = document.getElementById('new-file-modal');
        this.newFolderModal = document.getElementById('new-folder-modal');
        this.newFileName = document.getElementById('new-file-name');
        this.newFileContent = document.getElementById('new-file-content');
        this.newFolderName = document.getElementById('new-folder-name');
        
        // Update path input with current path
        this.pathInput.value = this.currentPath;
    }

    setupEventListeners() {
        // Navigation buttons
        this.parentBtn?.addEventListener('click', () => this.navigateToParent());
        this.refreshBtn?.addEventListener('click', () => this.refreshCurrentDirectory());
        this.homeBtn?.addEventListener('click', () => this.navigateToHome());
        
        // Path input and change folder
        this.changeFolderBtn?.addEventListener('click', () => this.changeFolder());
        this.pathInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.changeFolder();
            }
        });
        
        // File actions
        this.newFileBtn?.addEventListener('click', () => this.showNewFileModal());
        this.newFolderBtn?.addEventListener('click', () => this.showNewFolderModal());
        
        // Modal handlers
        this.setupModalHandlers();
        
        // Error recovery buttons
        document.getElementById('retry-btn')?.addEventListener('click', () => this.refreshCurrentDirectory());
        document.getElementById('home-error-btn')?.addEventListener('click', () => this.navigateToHome());
    }

    setupModalHandlers() {
        // Close modal buttons
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modalId = btn.dataset.modal;
                if (modalId) {
                    this.closeModal(modalId);
                }
            });
        });
        
        // Create file button
        document.getElementById('create-file-btn')?.addEventListener('click', () => this.createNewFile());
        
        // Create folder button
        document.getElementById('create-folder-btn')?.addEventListener('click', () => this.createNewFolder());
        
        // Close modals on background click
        [this.newFileModal, this.newFolderModal].forEach(modal => {
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.closeModal(modal.id);
                    }
                });
            }
        });
        
        // Enter key handlers for modals
        this.newFileName?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.createNewFile();
        });
        
        this.newFolderName?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.createNewFolder();
        });
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
                this.loadCurrentDirectory();
            } else {
                this.showError('Erreur d\'authentification');
            }
        } catch (error) {
            console.error('Erreur d\'authentification:', error);
            this.showError('Impossible de s\'authentifier');
        }
    }

    async loadCurrentDirectory() {
        if (this.isLoading || !this.apiToken) return;
        
        this.setLoading(true);
        this.hideError();
        this.updateStatus('Chargement du dossier...');
        
        try {
            const headers = { 'Content-Type': 'application/json' };
            if (this.apiToken) {
                headers['X-API-Key'] = this.apiToken;
            }
            
            const response = await fetch(`/api/files/list?dir=${encodeURIComponent(this.currentPath)}`, {
                headers: headers
            });
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            const files = await response.json();
            this.displayFiles(files);
            this.updateUI();
            this.updateStatus('Prêt');
            
        } catch (error) {
            console.error('Erreur lors du chargement du dossier:', error);
            this.showError(`Impossible de charger le dossier: ${error.message}`);
            this.updateStatus('Erreur de chargement');
        } finally {
            this.setLoading(false);
        }
    }

    displayFiles(files) {
        if (!this.fileTree) return;
        
        // Sort files: directories first, then files alphabetically
        files.sort((a, b) => {
            if (a.type === 'directory' && b.type !== 'directory') return -1;
            if (a.type !== 'directory' && b.type === 'directory') return 1;
            return a.name.localeCompare(b.name);
        });
        
        this.fileTree.innerHTML = files.map(file => this.createFileItem(file)).join('');
        
        // Add click handlers
        this.fileTree.querySelectorAll('.file-item').forEach(item => {
            item.addEventListener('click', () => this.selectFile(item));
            item.addEventListener('dblclick', () => this.openFile(item));
        });
        
        // Update file count and total size
        const totalSize = files.reduce((sum, file) => sum + (file.size || 0), 0);
        this.filesCountDisplay.textContent = `${files.length} éléments`;
        this.totalSize.textContent = this.formatFileSize(totalSize);
    }

    createFileItem(file) {
        const isDirectory = file.type === 'directory';
        const icon = isDirectory ? 'fas fa-folder' : this.getFileIcon(file.name);
        const size = isDirectory ? '' : this.formatFileSize(file.size || 0);
        const modified = file.modified ? new Date(file.modified * 1000).toLocaleDateString('fr-FR') : '';
        
        return `
            <div class="file-item ${isDirectory ? 'directory' : 'file'}" 
                 data-path="${file.path}" 
                 data-name="${file.name}" 
                 data-type="${file.type}">
                <i class="file-icon ${icon} ${isDirectory ? 'directory' : ''}"></i>
                <span class="file-name" title="${file.name}">${file.name}</span>
                <span class="file-size">${size}</span>
                <span class="file-modified">${modified}</span>
            </div>
        `;
    }

    getFileIcon(filename) {
        const ext = filename.split('.').pop()?.toLowerCase();
        const iconMap = {
            // Code files
            'js': 'fab fa-js-square',
            'ts': 'fas fa-code',
            'php': 'fab fa-php',
            'html': 'fab fa-html5',
            'css': 'fab fa-css3-alt',
            'json': 'fas fa-brackets-curly',
            'xml': 'fas fa-code',
            'py': 'fab fa-python',
            'java': 'fab fa-java',
            'cpp': 'fas fa-code',
            'c': 'fas fa-code',
            'cs': 'fas fa-code',
            'rb': 'fas fa-gem',
            'go': 'fas fa-code',
            'rust': 'fas fa-code',
            'sql': 'fas fa-database',
            
            // Documents
            'txt': 'fas fa-file-text',
            'md': 'fab fa-markdown',
            'pdf': 'fas fa-file-pdf',
            'doc': 'fas fa-file-word',
            'docx': 'fas fa-file-word',
            'xls': 'fas fa-file-excel',
            'xlsx': 'fas fa-file-excel',
            'ppt': 'fas fa-file-powerpoint',
            'pptx': 'fas fa-file-powerpoint',
            
            // Images
            'jpg': 'fas fa-image',
            'jpeg': 'fas fa-image',
            'png': 'fas fa-image',
            'gif': 'fas fa-image',
            'svg': 'fas fa-image',
            'ico': 'fas fa-image',
            'webp': 'fas fa-image',
            
            // Archives
            'zip': 'fas fa-file-archive',
            'rar': 'fas fa-file-archive',
            '7z': 'fas fa-file-archive',
            'tar': 'fas fa-file-archive',
            'gz': 'fas fa-file-archive',
            
            // Config
            'ini': 'fas fa-cog',
            'cfg': 'fas fa-cog',
            'conf': 'fas fa-cog',
            'yml': 'fas fa-cog',
            'yaml': 'fas fa-cog',
            'toml': 'fas fa-cog',
            
            // Media
            'mp4': 'fas fa-video',
            'avi': 'fas fa-video',
            'mov': 'fas fa-video',
            'mp3': 'fas fa-music',
            'wav': 'fas fa-music',
            'flac': 'fas fa-music'
        };
        
        return iconMap[ext] || 'fas fa-file';
    }

    selectFile(item) {
        // Clear previous selection
        this.fileTree.querySelectorAll('.file-item').forEach(el => el.classList.remove('selected'));
        
        // Select current item
        item.classList.add('selected');
        this.selectedFiles = [item.dataset.path];
        
        // Update selection count
        this.selectionCount.textContent = `1 fichier sélectionné`;
    }

    openFile(item) {
        const isDirectory = item.dataset.type === 'directory';
        
        if (isDirectory) {
            // Navigate to directory
            this.navigateToPath(item.dataset.path);
        } else {
            // Open file in editor (send message to parent)
            if (window.parent !== window) {
                window.parent.postMessage({
                    type: 'openFile',
                    path: item.dataset.path,
                    name: item.dataset.name
                }, window.location.origin);
            }
        }
    }

    async navigateToPath(newPath) {
        // Normalize path
        const normalizedPath = this.normalizePath(newPath);
        
        // Update history
        if (normalizedPath !== this.currentPath) {
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(normalizedPath);
            this.historyIndex = this.history.length - 1;
        }
        
        this.currentPath = normalizedPath;
        this.pathInput.value = this.currentPath;
        
        await this.loadCurrentDirectory();
    }

    navigateToParent() {
        if (this.currentPath === '.' || this.currentPath === '/') return;
        
        const parentPath = this.currentPath.split('/').slice(0, -1).join('/') || '.';
        this.navigateToPath(parentPath);
    }

    navigateToHome() {
        this.navigateToPath('.');
    }

    async changeFolder() {
        const newPath = this.pathInput.value.trim();
        if (newPath && newPath !== this.currentPath) {
            await this.navigateToPath(newPath);
        }
    }

    refreshCurrentDirectory() {
        this.loadCurrentDirectory();
    }

    normalizePath(path) {
        if (!path || path === '') return '.';
        if (path === '/') return '.';
        
        // Remove leading/trailing slashes and normalize
        return path.replace(/^\/+|\/+$/g, '') || '.';
    }

    updateUI() {
        // Update current path display
        this.currentPathDisplay.textContent = this.currentPath;
        this.pathInput.value = this.currentPath;
        
        // Update parent button state
        const canGoUp = this.currentPath !== '.' && this.currentPath !== '/';
        this.parentBtn.disabled = !canGoUp;
        
        // Clear selection
        this.selectedFiles = [];
        this.selectionCount.textContent = 'Aucune sélection';
    }

    // Modal management
    showNewFileModal() {
        this.showModal('new-file-modal');
        this.newFileName.focus();
    }

    showNewFolderModal() {
        this.showModal('new-folder-modal');
        this.newFolderName.focus();
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            
            // Clear form inputs
            if (modalId === 'new-file-modal') {
                this.newFileName.value = '';
                this.newFileContent.value = '';
            } else if (modalId === 'new-folder-modal') {
                this.newFolderName.value = '';
            }
        }
    }

    async createNewFile() {
        const fileName = this.newFileName.value.trim();
        const fileContent = this.newFileContent.value;
        
        if (!fileName) {
            alert('Veuillez entrer un nom de fichier');
            return;
        }
        
        try {
            const filePath = this.currentPath === '.' ? fileName : `${this.currentPath}/${fileName}`;
            
            const headers = { 'Content-Type': 'application/json' };
            if (this.apiToken) {
                headers['X-API-Key'] = this.apiToken;
            }
            
            const response = await fetch(getApiBase() + 'files/create', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    path: filePath,
                    content: fileContent
                })
            });
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            this.closeModal('new-file-modal');
            this.refreshCurrentDirectory();
            this.updateStatus(`Fichier "${fileName}" créé avec succès`);
            
        } catch (error) {
            console.error('Erreur lors de la création du fichier:', error);
            alert(`Erreur lors de la création du fichier: ${error.message}`);
        }
    }

    async createNewFolder() {
        const folderName = this.newFolderName.value.trim();
        
        if (!folderName) {
            alert('Veuillez entrer un nom de dossier');
            return;
        }
        
        try {
            const folderPath = this.currentPath === '.' ? folderName : `${this.currentPath}/${folderName}`;
            
            // Create folder by creating a temporary file then removing it
            // (since the API doesn't have a specific create folder endpoint)
            const tempFilePath = `${folderPath}/.sgc-temp`;
            
            const headers = { 'Content-Type': 'application/json' };
            if (this.apiToken) {
                headers['X-API-Key'] = this.apiToken;
            }
            
            const response = await fetch(getApiBase() + 'files/create', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    path: tempFilePath,
                    content: 'temp'
                })
            });
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            // Remove the temporary file
            const deleteHeaders = { 'Content-Type': 'application/json' };
            if (this.apiToken) {
                deleteHeaders['X-API-Key'] = this.apiToken;
            }
            
            await fetch(getApiBase() + 'files/delete', {
                method: 'DELETE',
                headers: deleteHeaders,
                body: JSON.stringify({
                    path: tempFilePath
                })
            });
            
            this.closeModal('new-folder-modal');
            this.refreshCurrentDirectory();
            this.updateStatus(`Dossier "${folderName}" créé avec succès`);
            
        } catch (error) {
            console.error('Erreur lors de la création du dossier:', error);
            alert(`Erreur lors de la création du dossier: ${error.message}`);
        }
    }

    // Utility methods
    formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
    }

    setLoading(loading) {
        this.isLoading = loading;
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = loading ? 'flex' : 'none';
        }
    }

    showError(message) {
        if (this.errorDisplay && this.errorMessage) {
            this.errorMessage.textContent = message;
            this.errorDisplay.style.display = 'flex';
        }
    }

    hideError() {
        if (this.errorDisplay) {
            this.errorDisplay.style.display = 'none';
        }
    }

    updateStatus(status) {
        if (this.statusText) {
            this.statusText.textContent = status;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Wait a bit for other scripts to load
    setTimeout(() => {
        window.sgcFiles = new SGCFiles();
    }, 100);
});