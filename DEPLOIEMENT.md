# Déploiement en production

| Élément | Valeur |
|---|---|
| Adresse | https://jardins.relaisconsignes.fr |
| Hébergement | Hostinger, compte `u183108258` |
| Dossier serveur | `domains/relaisconsignes.fr/public_html/lesjardinsdacote/` |
| Racine web du sous-domaine | `…/lesjardinsdacote/public_html` |
| Base MySQL | `u183108258_jardins` (hôte `localhost`) |
| Configuration | `app/config.php` sur le serveur uniquement (jamais dans Git) |
| Tâche planifiée | toutes les 5 min : `/usr/bin/php /home/u183108258/domains/relaisconsignes.fr/public_html/lesjardinsdacote/app/cron/run.php` |

## Mettre à jour le site

1. Pousser les modifications sur la branche `main` de ce dépôt.
2. Sur le serveur, le dossier est un clone Git : un nouveau déploiement (hPanel → Avancé → Git → Déployer, ou demande à Claude) fait un `git pull`.
   Les fichiers non suivis par Git sont conservés : `app/config.php`, `app/storage/`, photos envoyées dans `public_html/uploads/`.

## Changer d'adresse plus tard

Créer le domaine dans hPanel en le faisant pointer sur le même dossier (`lesjardinsdacote/public_html`), puis modifier `base_url` dans `app/config.php`.
