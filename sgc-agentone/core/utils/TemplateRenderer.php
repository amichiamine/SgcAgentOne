<?php
/**
 * SGC-AgentOne - Template Rendering Utility
 * Gère le rendu des templates avec substitution sécurisée des variables {{TOKEN}}
 */

class TemplateRenderer {
    
    /**
     * Rend un template en remplaçant les tokens {{TOKEN}} par les valeurs fournies
     * 
     * @param string $templateContent Contenu du template
     * @param array $variables Variables à substituer (clé => valeur)
     * @param bool $escapeHtml Échapper les caractères HTML (défaut: true)
     * @return string Template rendu
     */
    public static function render($templateContent, $variables = [], $escapeHtml = true) {
        if (empty($templateContent)) {
            return '';
        }
        
        $rendered = $templateContent;
        
        // Substitution des variables
        foreach ($variables as $key => $value) {
            $token = '{{' . strtoupper($key) . '}}';
            
            // Conversion des valeurs en string et échappement si nécessaire
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $value = (string) $value;
            }
            
            // Échappement HTML si requis
            if ($escapeHtml && !self::isHtmlContent($key)) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            
            $rendered = str_replace($token, $value, $rendered);
        }
        
        return $rendered;
    }
    
    /**
     * Charge et rend un fichier template
     * 
     * @param string $templatePath Chemin vers le fichier template
     * @param array $variables Variables à substituer
     * @param bool $escapeHtml Échapper les caractères HTML
     * @return string|false Template rendu ou false en cas d'erreur
     */
    public static function renderFile($templatePath, $variables = [], $escapeHtml = true) {
        $projectRoot = getcwd();
        
        // Vérification du chemin sécurisé
        if (strpos($templatePath, '..') !== false) {
            return false;
        }
        
        // Chemin complet
        if (!str_starts_with($templatePath, '/')) {
            $fullPath = $projectRoot . '/sgc-agentone/core/templates/' . $templatePath;
        } else {
            $fullPath = $projectRoot . $templatePath;
        }
        
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return false;
        }
        
        $templateContent = file_get_contents($fullPath);
        if ($templateContent === false) {
            return false;
        }
        
        return self::render($templateContent, $variables, $escapeHtml);
    }
    
    /**
     * Génère les variables par défaut du système
     * 
     * @return array Variables système
     */
    public static function getSystemVariables() {
        return [
            'APP_NAME' => 'SGC-AgentOne',
            'VERSION' => '1.0.0',
            'TIMESTAMP' => date('Y-m-d H:i:s'),
            'DATE' => date('Y-m-d'),
            'TIME' => date('H:i:s'),
            'API_URL' => '/api/chat',
            'BASE_URL' => self::getBaseUrl(),
            'PROJECT_ROOT' => getcwd(),
            'ENVIRONMENT' => 'development'
        ];
    }
    
    /**
     * Détermine si une clé contient du contenu HTML (ne doit pas être échappé)
     * 
     * @param string $key Nom de la variable
     * @return bool True si c'est du contenu HTML
     */
    private static function isHtmlContent($key) {
        $htmlKeys = [
            'content', 'html', 'body', 'code', 'script', 'style',
            'comment', 'description_html', 'message_html'
        ];
        
        $key = strtolower($key);
        foreach ($htmlKeys as $htmlKey) {
            if (strpos($key, $htmlKey) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtient l'URL de base du serveur
     * 
     * @return string URL de base
     */
    private static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:5000';
        return $protocol . '://' . $host;
    }
    
    /**
     * Valide et nettoie les variables d'entrée
     * 
     * @param array $variables Variables à valider
     * @return array Variables nettoyées
     */
    public static function sanitizeVariables($variables) {
        $clean = [];
        
        foreach ($variables as $key => $value) {
            // Nettoyage de la clé
            $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if (empty($cleanKey)) {
                continue;
            }
            
            // Limitation de la taille des valeurs
            if (is_string($value) && strlen($value) > 10000) {
                $value = substr($value, 0, 10000) . '...';
            }
            
            $clean[$cleanKey] = $value;
        }
        
        return $clean;
    }
    
    /**
     * Fusionne les variables système avec les variables utilisateur
     * 
     * @param array $userVariables Variables utilisateur
     * @return array Variables fusionnées
     */
    public static function mergeVariables($userVariables = []) {
        $systemVars = self::getSystemVariables();
        $cleanUserVars = self::sanitizeVariables($userVariables);
        
        // Les variables utilisateur écrasent les variables système
        return array_merge($systemVars, $cleanUserVars);
    }
    
    /**
     * Rendu rapide avec variables système automatiques
     * 
     * @param string $templateContent Contenu du template
     * @param array $userVariables Variables utilisateur
     * @return string Template rendu
     */
    public static function quickRender($templateContent, $userVariables = []) {
        $allVariables = self::mergeVariables($userVariables);
        return self::render($templateContent, $allVariables);
    }
    
    /**
     * Rendu rapide de fichier avec variables système
     * 
     * @param string $templatePath Chemin du template
     * @param array $userVariables Variables utilisateur
     * @return string|false Template rendu
     */
    public static function quickRenderFile($templatePath, $userVariables = []) {
        $allVariables = self::mergeVariables($userVariables);
        return self::renderFile($templatePath, $allVariables);
    }
}
?>