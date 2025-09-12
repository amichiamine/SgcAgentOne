/**
 * SGC-AgentOne - Template JavaScript
 * Script JavaScript vanilla pour interactions de base
 */

// Configuration globale
const SGCAgent = {
    apiUrl: '{{API_URL}}',
    apiKey: '{{API_KEY}}',
    baseUrl: '{{BASE_URL}}',
    
    // Configuration de la page (sera mise à jour dynamiquement)
    pageConfig: {},
    
    // Configuration finale (fusion des configs)
    getConfig() {
        return {
            apiUrl: this.pageConfig.apiUrl || this.apiUrl,
            apiKey: this.pageConfig.apiKey || this.apiKey,
            baseUrl: this.pageConfig.baseUrl || this.baseUrl
        };
    },
    
    // {{COMMENT}}
    init() {
        console.log('🤖 SGC-AgentOne JavaScript initialisé');
        this.setupEventListeners();
        this.initializeInterface();
    },
    
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            // Boutons clay avec effet
            this.initClayButtons();
            
            // Gestion des formulaires
            this.initForms();
            
            // {{CODE}}
        });
    },
    
    initClayButtons() {
        const buttons = document.querySelectorAll('.clay-button');
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                // Effet visuel de clic
                button.classList.add('active');
                setTimeout(() => {
                    button.classList.remove('active');
                }, 150);
                
                // Action personnalisée si définie
                const action = button.dataset.action;
                if (action && this[action]) {
                    this[action](button, e);
                }
            });
        });
    },
    
    initForms() {
        const forms = document.querySelectorAll('form[data-sgc-form]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(form);
            });
        });
    },
    
    async handleFormSubmit(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await this.apiCall(data.message || 'Action depuis le formulaire');
            this.displayResponse(response);
        } catch (error) {
            this.showError('Erreur lors de l\'envoi: ' + error.message);
        }
    },
    
    async apiCall(message, blind = false) {
        const config = this.getConfig();
        const response = await fetch(config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': config.apiKey
            },
            body: JSON.stringify({
                message: message,
                projectPath: '.',
                blind: blind
            })
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        return await response.json();
    },
    
    displayResponse(response) {
        console.log('Réponse de l\'API:', response);
        
        // Affichage dans un élément si présent
        const responseElement = document.querySelector('.response-display');
        if (responseElement) {
            responseElement.innerHTML = `
                <div class="clay-panel">
                    <h3>Réponse de l'Assistant IA</h3>
                    <p>${response.response || 'Action effectuée avec succès'}</p>
                    ${response.actions ? `
                        <div class="actions-list">
                            <h4>Actions:</h4>
                            <ul>
                                ${response.actions.map(action => `
                                    <li>${action.action}: ${action.result}</li>
                                `).join('')}
                            </ul>
                        </div>
                    ` : ''}
                </div>
            `;
        }
    },
    
    showError(message) {
        console.error('Erreur SGC-AgentOne:', message);
        
        const errorElement = document.querySelector('.error-display');
        if (errorElement) {
            errorElement.innerHTML = `
                <div class="clay-panel" style="border-left: 4px solid #ef4444;">
                    <h3 style="color: #ef4444;">Erreur</h3>
                    <p>${message}</p>
                </div>
            `;
            
            // Masquer l'erreur après 5 secondes
            setTimeout(() => {
                errorElement.innerHTML = '';
            }, 5000);
        }
    },
    
    initializeInterface() {
        // Animation d'entrée pour les éléments clay
        const clayElements = document.querySelectorAll('.clay-panel, .clay-button');
        clayElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
};

// Initialisation automatique
SGCAgent.init();