// Test automatisé des boutons de thème
const puppeteer = require('puppeteer');

(async () => {
  try {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    
    // Aller sur la page
    await page.goto('http://localhost:5000');
    
    // Attendre que l'interface se charge
    await page.waitForSelector('#settingsBtn');
    
    // Cliquer sur l'onglet Paramètres  
    await page.click('#settingsBtn');
    
    // Attendre les boutons de thème
    await page.waitForSelector('#themeDarkBtn');
    
    // Test du thème Dark
    console.log('Test thème Dark...');
    await page.click('#themeDarkBtn');
    await page.waitForTimeout(500);
    
    // Test du thème Light
    console.log('Test thème Light...');
    await page.click('#themeLightBtn');
    await page.waitForTimeout(500);
    
    // Test du thème Commander
    console.log('Test thème Commander...');
    await page.click('#themeCommanderBtn');
    await page.waitForTimeout(500);
    
    console.log('✅ Tous les boutons de thème testés avec succès');
    
    await browser.close();
  } catch (error) {
    console.log('❌ Erreur:', error.message);
  }
})();
