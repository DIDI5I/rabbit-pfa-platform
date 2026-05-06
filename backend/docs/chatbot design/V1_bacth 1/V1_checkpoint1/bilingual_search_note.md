# Bilingual Search Note

## Issue

Many database product names/descriptions are stored in French.

Example:

```text
pompe
hydraulique
filtre
moteur
courroie
roulement
joint
```

A user may ask in English:

```text
do you have pumps?
```

If the chatbot searches only:

```text
pump
```

it may miss French product names, unless the SKU contains `PUMP`.

## V1 Solution

Use a simple synonym map in `TextIntentUtils::normalizeSearchTerm()`.

Examples:

```php
$map = [
    'pump' => 'pompe',
    'pumps' => 'pompe',
    'hydraulic' => 'hydraulique',
    'hydraulics' => 'hydraulique',
    'maintenance kit' => 'kit maintenance',
    'spare parts' => 'pièces',
    'seal' => 'joint',
    'seals' => 'joint',
    'bearing' => 'roulement',
    'bearings' => 'roulement',
    'belt' => 'courroie',
    'belts' => 'courroie',
    'motor' => 'moteur',
    'motors' => 'moteur',
    'filter' => 'filtre',
    'filters' => 'filtre',
];
```

## Future Better Solution

Later, implement multi-term synonym search:

```text
pump → pump OR pompe
hydraulic → hydraulic OR hydraulique
bearing → bearing OR roulement OR palier
```

That requires repository/query support for multiple OR search terms.
