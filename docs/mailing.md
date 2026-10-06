# Vérification de l'inscription par code

Après saisie du formulaire, SolarShare envoie un code de six chiffres. Le compte est créé et connecté uniquement après validation du code. Le code expire au bout de dix minutes, avec cinq tentatives maximum et une minute entre les renvois. La session conserve le mot de passe et le code sous forme de hash. Un échec d'envoi bloque la création du compte et affiche une erreur. Les comptes existants ne sont pas modifiés.

## Test local sans identifiants

Dans `.env`, conserver `MAIL_MAILER=log`. Après inscription, l'e-mail apparaît dans `storage/logs/laravel.log`. Aucun e-mail n'est livré à une boîte réelle. Aucun worker de queue n'est nécessaire.

## Envoi réel

Configurer dans `.env` les valeurs SMTP fournies par votre service :

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=serveur-fourni-par-le-service
MAIL_PORT=587
MAIL_USERNAME=identifiant-fourni-par-le-service
MAIL_PASSWORD=secret-fourni-par-le-service
MAIL_FROM_ADDRESS=adresse-expediteur-autorisee
MAIL_FROM_NAME="SolarShare"
```

Utiliser le port et le schéma indiqués par votre fournisseur (par exemple `smtps` pour TLS implicite sur le port 465). Configurer `APP_URL` avec l'adresse de votre application pour le bouton de l'e-mail. Exécuter `php artisan config:clear` après modification.

Ne jamais committer `.env` ou des identifiants SMTP. Chaque collègue peut conserver le mode `log` ou utiliser ses propres identifiants dans son `.env`.

## Validation

`php artisan test --filter=RegistrationMailTest` vérifie la création après validation, le rejet des mauvais codes, l'expiration, le renvoi, la limitation des tentatives et les erreurs de livraison.
