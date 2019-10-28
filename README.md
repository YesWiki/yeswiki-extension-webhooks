# yeswiki-extension-webhooks
Outgoing webhooks at each addition/modification/deletion of a Bazar item.

## Installation

Put this repository inside the `/tools/webhooks` directory of YesWiki.

## Configuration

Go to the BazaR main page, at the bottom you have a Webhooks section.

- On the left you have a dropdown menu to select the format: `Raw`, `Mattermost`, `Slack`
- In the middle, you have a dropdown menu to select for which Bazar items the webhooks will be called. By default, the webhooks will be called on the addition/modification/deletion of all Bazar items.
- On the right you must enter the URL of the webhook to call

Every time you click on Update, a new row will be added. If you wish to delete a webhook, just use a blank URL and it will be removed on update.

### Mattermost

To integrate the webhook with a Mattermost chatroom, follow this guideline:

- Log into your Mattermost chatroom as an admin
- On the main menu, click on "Integration" (you only see it if you're an admin)
- Choose "Incoming webhooks"
- Create a webhook with the configurations you wish to use
- On the YesWiki BazaR page, choose "Mattermost" and copy the URL provided by Mattermost

You can define `webhooks_bot_name` and `webhooks_bot_icon` in `wakka.config.php` if you want to change the look of the bot which will post on Mattermost (you must allow this in the Mattermost configurations).

### Slack

To let Slack handle an incoming webhook, follow the [guide](https://api.slack.com/incoming-webhooks) here.

On the YesWiki BazaR page, choose "Slack" and copy the URL provided by Slack.

### Raw

If you choose the "Raw" format, all informations about the given Bazar item will be POSTed to the given URLs in a JSON format.

The data will be sent formatted like this:

- `action`: action type (add/edit/delete)
- `user`: name of the user who performed the action
- `text`: formatted text describing the action done (this is what is sent to Mattermost/Slack)
- `data_type`: for now the value will be always "bazar". In the future we may support regular pages.
- `bazar_form_id`: the ID of the Bazar item
- `data`: all the data of the Bazar item

### ActivityPub

The ActivityPub format only works if the Bazar objects are semantically defined. Please see [the documentation](https://yeswiki.net/documentation/?BazarSemantique) (in French) to know how to do that. You must use `https://www.w3.org/ns/activitystreams` as the main context, but other contexts can be added as well.

The data will be formatted like this:

```json
{
  "@context": "https://www.w3.org/ns/activitystreams",
  "type":"Create",
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
    "title":"My comment",
    "content":"Thank you for reading my comment",
    "published":"2019-10-21T14:56:32Z",
    "updated":"2019-10-21T14:56:32Z"
  }
}
```

For the `Delete` activity, only the object URI is sent.

The `published` and `updated` fields are set automatically, unless you mark a field explicitely with this context.

#### ActivityPub actors
By default, the actor posting the activity will be the logged-in user. You can also mark a field with the context `https://www.w3.org/ns/activitystreams#author` to override this value.

You can set two parameters in the `wakka.config.php` file :

- `webhooks_activitypub_default_actor`: The URL you associate with this parameter will be used as an actor for **all** ActivityPub activities.

- `webhooks_activitypub_actors_base_url`: The base URL to use for all actors. For example if you use `https://activitypub.server/actor/`, then the URI of user WikiAdmin will be `https://activitypub.server/actor/WikiAdmin` instead of `http://localhost/?WikiAdmin`.


## Test incoming webhook

If you wish to see the data returned by the webhook, you can use the URL of the test incoming webhook: `http://YOUR_URL/?BazaR&vue=test-webhook`. All data POSTed to this URL will be inserted into the `yeswiki_triples` table.
