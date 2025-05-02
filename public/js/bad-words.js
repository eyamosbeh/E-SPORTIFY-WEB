const badWords = [
    // Insultes et grossièretés en français
    'merde', 'putain', 'connard', 'connasse', 'salaud', 'salope', 'enculé',
    'pute', 'bordel', 'crétin', 'débile', 'idiot', 'imbécile', 'con',
    'abruti', 'andouille', 'bâtard', 'couillon', 'ducon', 'emmerdeur',
    'enfoiré', 'fumier', 'ordure', 'pourriture', 'salopard', 'trou du cul',
    'va te faire', 'vtf', 'ntm', 'fdp', 'pd', 'tg', 'stfu',
    // Mots discriminatoires
    'négro', 'negro', 'nègre', 'youpin', 'bougnoule', 'bicot', 'chinetoque',
    'pédé', 'tantouze', 'tafiole', 'gouine', 'tapette', 'travelo',
    // Versions avec variations orthographiques
    'm3rde', 'k0nard', 'p*tain', 'put@in', 'sal0pe', 'enc*le'
];

// Fonction pour vérifier si un texte contient des mots interdits
function containsBadWords(text) {
    // Convertir le texte en minuscules pour la comparaison
    const lowerText = text.toLowerCase();
    
    // Créer un tableau pour stocker les mots interdits trouvés
    const foundBadWords = [];
    
    // Vérifier chaque mot interdit
    badWords.forEach(word => {
        // Utiliser une expression régulière pour trouver le mot, même au sein d'autres mots
        const regex = new RegExp(`\\b${word}\\b|${word}`, 'gi');
        if (regex.test(lowerText)) {
            foundBadWords.push(word);
        }
    });
    
    return {
        hasBadWords: foundBadWords.length > 0,
        foundWords: foundBadWords
    };
} 