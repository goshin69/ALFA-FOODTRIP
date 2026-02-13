/**
 * Módulo de censura para detectar y filtrar malas palabras.
 * Adaptado para español de México.
 * 
 * @module censura
 */

// Lista de malas palabras y terminos ofensivos en español
const badWordsList = [
  'pendejo', 'pendeja', 'pendejos', 'pendejas',
  'puto', 'puta', 'putos', 'putas',
  'chinga', 'chingue', 'chingar', 'chingada', 'chingadera',
  'cabron', 'cabrona', 'cabrones', 'cabronas',
  'verga', 'vergas', 'vrga', 'vergon', 'vergones',
  'mierda', 'mierdas', 'mrd', 'mrda', 'mrdas', 'mierdilla', 'mierditas', 'mierdillas', 'mierditos',,
  'Nigga', 'negger', 'negritos','negro',
  'culero', 'culera', 'culeros', 'culeras', 'culeritos',
  'hijo de puta', 'hija de puta', 'hijos de puta', 'hijas de puta', 'hijueputa', 'hdpt',
  'madre', 'tu madre', 'su madre',
  'wey', 'güey', 'weyes', 'güeyes',
  'naco', 'naca', 'nacos', 'nacas',
  'joto', 'jota', 'jotos', 'jotas',
  'maricon', 'maricona', 'maricones', 'mariconas',
  'putazo', 'putazos',
  'chingon', 'chingona', 'chingones', 'chingonas',
  'carajo', 'carajos',
  'coño', 'coños',
  'joder', 'jodiendo',
  'estupido', 'estupida', 'estupidos', 'estupidas',
  'idiota', 'idiotas',
  'imbecil', 'imbeciles',
  'tonto', 'tonta', 'tontos', 'tontas',
  'asno', 'asna',
  'malparido', 'malparida', 'malparidos', 'malparidas',
  'hijueputa', 'hijueputas'
];

// Palabras que contienen ofensivas pero pueden ser falsos positivos (por ejemplo, "esputo")
const ignoreWords = ['esputo', 'disputo', 'computo']; // ejemplos, ajustar según necesidad

/**
 * Normaliza el texto: elimina acentos y convierte a minúsculas para comparación.
 * @param {string} text - Texto a normalizar.
 * @returns {string} Texto normalizado.
 */
function normalizeText(text) {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // eliminar diacríticos
    .replace(/[^\w\s]/g, ''); // eliminar puntuación (opcional)
}

/**
 * Detecta si el texto contiene malas palabras.
 * @param {string} text - Texto a analizar.
 * @returns {Object} Resultado del análisis.
 * @property {boolean} hasBadWords - Indica si se encontraron malas palabras.
 * @property {Array} foundWords - Lista de malas palabras encontradas.
 */
function containsBadWords(text) {
  const normalized = normalizeText(text);
  const words = normalized.split(/\s+/);
  const found = [];
  
  // Buscar coincidencias exactas de palabras completas
  words.forEach(word => {
    // Verificar si la palabra está en la lista negra y no está en la lista de ignoradas
    if (badWordsList.includes(word) && !ignoreWords.includes(word)) {
      if (!found.includes(word)) found.push(word);
    }
  });

  // También buscar frases completas que contengan espacios (ej: "hijo de puta")
  badWordsList.forEach(badPhrase => {
    if (badPhrase.includes(' ')) {
      if (normalized.includes(badPhrase)) {
        if (!found.includes(badPhrase)) found.push(badPhrase);
      }
    }
  });

  return {
    hasBadWords: found.length > 0,
    foundWords: found
  };
}

/**
 * Censura el texto reemplazando las malas palabras por asteriscos.
 * @param {string} text - Texto original.
 * @param {string} [replacement='***'] - Caracter o string de reemplazo.
 * @param {boolean} [showWarning=false] - Si es true, muestra una alerta en consola.
 * @returns {string} Texto censurado.
 */
function censorText(text, replacement = '***', showWarning = false) {
  let censored = text;
  const { hasBadWords, foundWords } = containsBadWords(text);
  
  if (hasBadWords) {
    if (showWarning) {
      console.warn(`[CENSURA] Se detectaron palabras inapropiadas: ${foundWords.join(', ')}`);
    }
    
    // Crear expresión regular para encontrar las palabras (con límites de palabra)
    foundWords.forEach(badWord => {
      // Escapar caracteres especiales en la palabra
      const escapedWord = badWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      // Crear regex global, insensible a mayúsculas y con límites de palabra
      const regex = new RegExp(`\\b${escapedWord}\\b`, 'gi');
      censored = censored.replace(regex, replacement);
    });
    
    // También manejar frases con espacios (no se pueden usar límites de palabra fácilmente)
    // Para frases, buscamos directamente en el texto original
    foundWords.forEach(badPhrase => {
      if (badPhrase.includes(' ')) {
        const escapedPhrase = badPhrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(escapedPhrase, 'gi');
        censored = censored.replace(regex, replacement);
      }
    });
  }
  
  return censored;
}

/**
 * Valida un campo de entrada y muestra un mensaje de advertencia si contiene malas palabras.
 * @param {HTMLInputElement|HTMLTextAreaElement} inputElement - Elemento de entrada.
 * @param {Function} callback - Función a ejecutar después de la validación (recibe un booleano: true = válido).
 * @param {string} [warningMessage='El texto contiene lenguaje inapropiado. Por favor, edítalo.'] - Mensaje de advertencia.
 */
function validateInput(inputElement, callback, warningMessage) {
  const text = inputElement.value;
  const { hasBadWords } = containsBadWords(text);
  
  if (hasBadWords) {
    // Mostrar advertencia visual (puedes personalizarlo)
    if (!warningMessage) {
      warningMessage = 'El texto contiene lenguaje inapropiado. Por favor, edítalo.';
    }
    inputElement.classList.add('input-error');
    // Mostrar mensaje de error (asumiendo que hay un elemento hermano para mostrar error)
    let errorSpan = inputElement.nextElementSibling;
    if (!errorSpan || !errorSpan.classList.contains('error-message')) {
      errorSpan = document.createElement('span');
      errorSpan.classList.add('error-message');
      errorSpan.style.color = 'red';
      errorSpan.style.fontSize = '12px';
      errorSpan.style.marginTop = '4px';
      errorSpan.style.display = 'block';
      inputElement.parentNode.insertBefore(errorSpan, inputElement.nextSibling);
    }
    errorSpan.textContent = warningMessage;
    
    if (callback) callback(false);
    return false;
  } else {
    // Limpiar advertencia si existe
    inputElement.classList.remove('input-error');
    const errorSpan = inputElement.nextElementSibling;
    if (errorSpan && errorSpan.classList.contains('error-message')) {
      errorSpan.textContent = '';
    }
    if (callback) callback(true);
    return true;
  }
}

/**
 * Agrega un listener de censura en tiempo real a un campo de entrada.
 * @param {string} inputId - ID del elemento de entrada.
 * @param {Function} onValid - Función a llamar cuando el texto es válido.
 * @param {Function} onInvalid - Función a llamar cuando el texto es inválido.
 */
function attachCensorshipListener(inputId, onValid, onInvalid) {
  const input = document.getElementById(inputId);
  if (!input) return;
  
  input.addEventListener('input', function() {
    const isValid = validateInput(input, (valid) => {
      if (valid && onValid) onValid();
      if (!valid && onInvalid) onInvalid();
    });
    return isValid;
  });
}

// Exportar funciones si se usa en un entorno de modulos (Node.js o ES6)
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    badWordsList,
    containsBadWords,
    censorText,
    validateInput,
    attachCensorshipListener,
    normalizeText
  };
} else {
  // Para uso en navegador sin modulos, exponer globalmente
  window.censura = {
    badWordsList,
    containsBadWords,
    censorText,
    validateInput,
    attachCensorshipListener,
    normalizeText
  };
}