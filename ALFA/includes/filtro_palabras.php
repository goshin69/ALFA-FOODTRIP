<?php
function normalizarTexto($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = str_replace(
        ['á','é','í','ó','ú','ü','ñ'],
        ['a','e','i','o','u','u','n'],
        $texto
    );
    return preg_replace('/[^a-z0-9\s]/', '', $texto);
}

function contienePalabraProhibida($texto) {
    $groserias = [
        'puta', 'puto', 'pendejo', 'pendeja', 'cabron', 'cabrona', 'verga',
        'vergas', 'chingar', 'chinga', 'chingue', 'chingada', 'chinguen',
        'culero', 'culera', 'culeros', 'culeras', 'hijueputa', 'hijueputas',
        'malparido', 'malparida', 'gonorrea', 'sapo', 'sapa', 'marica',
        'marico', 'maricon', 'maricona', 'maricones', 'guey', 'wey', 'güey',
        'pendejada', 'pendejadas', 'chingadera', 'chingaderas', 'madre',
        'madres', 'coño', 'coños', 'carajo', 'carajos', 'mierda', 'mierdas',
        'joder', 'jodido', 'jodida', 'jodidos', 'jodidas', 'follar', 'follada',
        'follado', 'follados', 'folladas', 'coger', 'cogiendo', 'cogida',
        'cogido', 'putear', 'puteando', 'putazo', 'putazos', 'culo', 'culos',
        'pedo', 'joto', 'pedos', 'pedorra', 'pedorro', 'pendejeando', 'pendejear'
    ];
    $textoNormalizado = normalizarTexto($texto);
    foreach ($groserias as $palabra) {
        if (strpos($textoNormalizado, $palabra) !== false) {
            return true;
        }
    }
    return false;
}
?>