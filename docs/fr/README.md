# Extension webhooks

Envoie un webhook sortant à chaque ajout, modification ou suppression d'une fiche
bazar.

## Configuration

Aller sur la page principale de BazaR : la section Webhooks est en bas.

- À gauche, un menu déroulant choisit le format : `Raw`, `Mattermost`, `Slack`.
- Au milieu, un menu déroulant choisit les fiches concernées. Par défaut, le webhook
  part pour l'ajout, la modification et la suppression de toutes les fiches.
- À droite, l'URL à appeler.

Chaque clic sur Mettre à jour ajoute une ligne. Pour supprimer un webhook, vider son
URL et mettre à jour.

## Mattermost

1. Se connecter à Mattermost comme administrateur.
2. Dans le menu principal, cliquer sur « Integration », visible seulement pour les
   administrateurs.
3. Choisir « Incoming webhooks ».
4. Créer un webhook avec les réglages voulus.
5. Sur la page BazaR, choisir « Mattermost » et coller l'URL fournie par Mattermost.

| Clé de `wakka.config.php` | Rôle |
|---|---|
| `webhooks_bot_name` | nom du bot qui publie sur Mattermost |
| `webhooks_bot_icon` | icône de ce bot |

La configuration de Mattermost doit autoriser la surcharge du nom et de l'icône.

## Slack

Suivre le [guide Slack](https://api.slack.com/incoming-webhooks), puis choisir « Slack »
sur la page BazaR et coller l'URL fournie.

## Raw

Le format `Raw` envoie en POST toutes les informations de la fiche, au format JSON.

| Champ | Contenu |
|---|---|
| `action` | type d'action : `add`, `edit` ou `delete` |
| `user` | nom de la personne qui a agi |
| `text` | texte décrivant l'action, celui envoyé à Mattermost et Slack |
| `data_type` | vaut toujours `bazar` pour l'instant |
| `bazar_form_id` | identifiant du formulaire bazar |
| `data` | toutes les données de la fiche |

## ActivityPub

Le format ActivityPub ne fonctionne que si les objets bazar sont définis
sémantiquement. Voir [la documentation
BazarSemantique](https://yeswiki.net/documentation/?BazarSemantique). Le contexte
principal doit être `https://www.w3.org/ns/activitystreams`, d'autres contextes peuvent
s'y ajouter.

```json
{
  "@context": "https://www.w3.org/ns/activitystreams",
  "type": "Create",
  "to": [
    "http://localhost/?WikiAdmin/followers",
    "https://www.w3.org/ns/activitystreams#Public"
  ],
  "actor": "http://localhost/?WikiAdmin",
  "object": {
    "id": "http://localhost/?MyComment",
    "type": "Note",
    "attributedTo": "http://localhost/?WikiAdmin",
    "to": [
      "http://localhost/?WikiAdmin/followers",
      "https://www.w3.org/ns/activitystreams#Public"
    ],
    "title": "My comment",
    "content": "Thank you for reading my comment",
    "published": "2019-10-21T14:56:32Z",
    "updated": "2019-10-21T14:56:32Z"
  }
}
```

Pour une activité `Delete`, seul l'URI de l'objet est envoyé.

`published` et `updated` sont renseignés automatiquement, sauf si un champ est marqué
explicitement avec ce contexte.

### Acteurs ActivityPub

Par défaut l'acteur qui publie est la personne connectée. Marquer un champ avec le
contexte `https://www.w3.org/ns/activitystreams#author` remplace cette valeur.

| Clé de `wakka.config.php` | Rôle |
|---|---|
| `webhooks_activitypub_default_actor` | acteur utilisé pour **toutes** les activités |
| `webhooks_activitypub_actors_base_url` | URL de base des acteurs |

Avec `webhooks_activitypub_actors_base_url` à `https://activitypub.server/actor/`,
l'URI de WikiAdmin devient `https://activitypub.server/actor/WikiAdmin` au lieu de
`http://localhost/?WikiAdmin`.

## Tester un webhook entrant

Pour voir les données envoyées, utiliser l'URL de test :
`http://VOTRE_URL/?BazaR&vue=test-webhook`. Tout ce qui y est envoyé en POST est inséré
dans la table `yeswiki_triples`.
