<?php

return [
    // Module
    'Web Scheduler' => 'Web Scheduler',
    'Trigger Symfony Console commands via signed HTTP URLs. Adapts automatically to the hosting capabilities.' => 'Déclenchez des commandes Symfony Console via des URLs HTTP signées. Le module s\'adapte automatiquement aux capacités de l\'hébergement.',

    // Navigation
    'Tasks' => 'Tâches',
    'Task' => 'Tâche',
    'Executions' => 'Exécutions',
    'Execution' => 'Exécution',
    'Diagnostic' => 'Diagnostic',
    'Back' => 'Retour',
    'Cancel' => 'Annuler',
    'Save' => 'Enregistrer',
    'Actions' => 'Actions',
    'Details' => 'Détails',
    'Yes' => 'Oui',
    'No' => 'Non',

    // Task list
    'Create a task' => 'Créer une tâche',
    'Edit task' => 'Modifier la tâche',
    'No task yet.' => 'Aucune tâche pour le moment.',
    'Delete this task?' => 'Supprimer cette tâche ?',
    'Trigger now' => 'Déclencher maintenant',
    'Copy URL' => 'Copier l\'URL',
    'Last triggered' => 'Dernier déclenchement',
    'Trigger URL' => 'URL de déclenchement',

    // Task form fields
    'Title' => 'Titre',
    'Command' => 'Commande',
    'Arguments' => 'Arguments',
    'Strategy' => 'Stratégie',
    'Enabled' => 'Activée',
    'Minimum interval (seconds)' => 'Intervalle minimum (secondes)',
    'Maximum runtime (seconds)' => 'Durée maximale (secondes)',
    'IP allowlist' => 'Liste blanche d\'IP',
    'CLI-style arguments, space separated. Quote values that contain spaces.' => 'Arguments au format CLI, séparés par des espaces. Entourez de guillemets les valeurs contenant des espaces.',
    'Auto picks the best strategy supported by the hosting.' => 'Auto choisit la meilleure stratégie supportée par l\'hébergement.',
    'Minimum seconds between two triggers. 0 disables rate limiting.' => 'Nombre minimum de secondes entre deux déclenchements. 0 désactive la limitation.',
    'Hard timeout for external process execution. 0 means no limit.' => 'Délai maximum d\'exécution pour un processus externe. 0 = aucune limite.',
    'One CIDR or IP per line. Empty = any IP allowed.' => 'Un CIDR ou une IP par ligne. Vide = toutes les IP autorisées.',

    // Task form secret / URL
    'Secret is shown only once.' => 'Le secret n\'est affiché qu\'une seule fois.',
    'Store it safely. Anyone holding this secret can trigger the task.' => 'Conservez-le en sécurité. Toute personne possédant ce secret peut déclencher la tâche.',
    'Paste this URL in your hosting scheduled-tasks panel (Infomaniak, etc.). Each call generates its own signature at trigger time.' => 'Collez cette URL dans le panneau de tâches planifiées de votre hébergement (Infomaniak, etc.). Une nouvelle signature est générée à chaque affichage.',
    'Regenerate secret' => 'Régénérer le secret',
    'Regenerate the secret? This invalidates the current URL.' => 'Régénérer le secret ? Cela invalide l\'URL actuelle.',
    'Save the task first to generate its trigger URL.' => 'Enregistrez la tâche pour générer son URL de déclenchement.',

    // Execution list / detail
    'No execution yet.' => 'Aucune exécution pour le moment.',
    'Triggered at' => 'Déclenchée à',
    'Started at' => 'Démarrée à',
    'Finished at' => 'Terminée à',
    'Trigger IP' => 'IP de déclenchement',
    'IP' => 'IP',
    'Duration' => 'Durée',
    'Status' => 'Statut',
    'Exit code' => 'Code de sortie',
    'Output' => 'Sortie',
    'Execution not found.' => 'Exécution introuvable.',
    'Task not found.' => 'Tâche introuvable.',

    // Diagnostic
    'Hosting capabilities' => 'Capacités de l\'hébergement',
    'Execution strategies' => 'Stratégies d\'exécution',
    'Capability' => 'Capacité',
    'Available' => 'Disponible',
    'Unavailable' => 'Indisponible',
    'Supported' => 'Supportée',
    'Priority' => 'Priorité',
    'Last check:' => 'Dernier contrôle :',
    'Refresh capabilities' => 'Rafraîchir les capacités',
    'Elected (auto)' => 'Choisie (auto)',
    'The green-highlighted strategy is the one picked by tasks set to \'Auto\'. A task can override this by selecting a specific strategy.' => 'La stratégie surlignée en vert est celle sélectionnée par les tâches en mode Auto. Chaque tâche peut forcer une autre stratégie.',
    '(none)' => '(aucune)',

    // Strategy labels (ExecutionStrategyEnum::label())
    'Auto' => 'Auto',
    'CLI fork' => 'Fork CLI',
    'FastCGI finish' => 'FastCGI détaché',
    'Synchronous' => 'Synchrone',

    // Strategy raw values (displayed in lists)
    'auto' => 'auto',
    'cli_fork' => 'fork CLI',
    'fastcgi_finish' => 'FastCGI détaché',
    'sync' => 'synchrone',

    // Execution status raw values
    'pending' => 'en attente',
    'running' => 'en cours',
    'success' => 'succès',
    'failed' => 'échouée',
    'timeout' => 'timeout',
    'skipped_locked' => 'ignorée (verrou)',
    'unauthorized' => 'non autorisée',
    'rate_limited' => 'limite atteinte',
    'ip_denied' => 'IP refusée',
    'disabled' => 'désactivée',

    // Errors
    'Invalid signature.' => 'Signature invalide.',
    'Rate limit exceeded.' => 'Limite de débit dépassée.',
    'IP not allowed.' => 'IP non autorisée.',
    'Task is disabled.' => 'Tâche désactivée.',
    'Execution is already running.' => 'Une exécution est déjà en cours.',
];
