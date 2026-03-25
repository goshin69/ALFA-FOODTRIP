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
        'puta', 'puto', 'putas', 'putos', 'pendejo', 'pendeja', 'pendejos', 'pendejas',
        'cabron', 'cabrona', 'cabrones', 'cabronas', 'verga', 'vergas', 'vergon',
        'chingar', 'chinga', 'chingue', 'chingada', 'chinguen', 'chingadera', 'chingaderas',
        'culero', 'culera', 'culeros', 'culeras', 'culo', 'culos',
        'hijueputa', 'hijueputas', 'hijo de puta', 'hija de puta',
        'malparido', 'malparida', 'malparidos', 'malparidas',
        'gonorrea', 'gonorreas', 'sapo', 'sapa', 'sapos', 'sapas',
        'marica', 'marico', 'maricon', 'maricona', 'maricones', 'mariconas',
        'guey', 'wey', 'güey', 'güeyes', 'weyes',
        'pendejada', 'pendejadas', 'pendejeando', 'pendejear',
        'madre', 'madres', 'madrecita', 'madresita',
        'coño', 'coños', 'carajo', 'carajos', 'mierda', 'mierdas',
        'joder', 'jodido', 'jodida', 'jodidos', 'jodidas', 'jodiendo',
        'follar', 'follada', 'follado', 'follados', 'folladas', 'follando',
        'coger', 'cogiendo', 'cogida', 'cogido', 'cogidos', 'cogidas',
        'putear', 'puteando', 'putazo', 'putazos',
        'pedo', 'pedos', 'pedorra', 'pedorro', 'pedorros', 'pedorras',
        'joto', 'jota', 'jotos', 'jotas',
        'mamada', 'mamadas', 'mamar', 'mame', 'mames',
        'chupar', 'chupada', 'chupadas', 'chupete', 'chupetear',
        'caca', 'cacas', 'cagada', 'cagadas', 'cagar', 'cago', 'cagan',
        'pito', 'pitos', 'pipi', 'popo',
        'tetas', 'teta', 'nalgas', 'nalga',
        'zorra', 'zorro', 'zorras', 'zorros',
        'perra', 'perro', 'perras', 'perros',
        'marimacha', 'marimacho', 'machorra', 'machorro',
        'travesti', 'travieso', 'traviesa',
        'enano', 'enana', 'enanito', 'enanita',
        'gordo', 'gorda', 'gordos', 'gordas',
        'flaco', 'flaca', 'flacos', 'flacas',
        'indio', 'india', 'indios', 'indias',
        'negro', 'negra', 'negros', 'negras',
        'prieto', 'prieta', 'prietos', 'prietas',
        'chino', 'china', 'chinos', 'chinas',
        'moro', 'mora', 'moros', 'moras',
        'judío', 'judía', 'judíos', 'judías',
        'putañero', 'putañera', 'putañeros', 'putañeras',
        'prostituta', 'prostituto', 'prostitutas', 'prostitutos',
        'escoria', 'escorias', 'basura', 'basuras',
        'desgraciado', 'desgraciada', 'desgraciados', 'desgraciadas',
        'infeliz', 'infelices', 'maldito', 'maldita', 'malditos', 'malditas',
        'condenado', 'condenada', 'condenados', 'condenadas'
    ];

    $texto = normalizarTexto($texto);
    foreach ($groserias as $palabra) {
        if (preg_match('/\b' . preg_quote($palabra, '/') . '\b/u', $texto)) {
            return true;
        }
    }
    return false;
}
?>